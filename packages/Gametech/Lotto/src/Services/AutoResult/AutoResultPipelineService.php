<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Exceptions\ResultParseException;
use Gametech\Lotto\Exceptions\ResultValidationException;
use Gametech\Lotto\Exceptions\TemplateRenderException;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultFetchLog;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\AutoResultV2\LottoResultPipelineRunner;
use Gametech\Lotto\Services\AutoResultHardeningService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class AutoResultPipelineService
{
    private const RETRYABLE_STATUS = 'NOT_READY';
    private const TERMINAL_DRAW_STATUSES = ['APPLIED', 'CONFLICT', 'EXHAUSTED'];
    private const CANONICAL_STATUSES = [
        'NO_SOURCE',
        'HTTP_ERROR',
        'NOT_READY',
        'PARSE_ERROR',
        'VALIDATION_ERROR',
        'TEMPLATE_ERROR',
        'CONFLICT',
        'RATE_LIMITED',
        'EXHAUSTED',
        'APPLIED',
    ];

    public function __construct(
        private ResultSourceResolver $resolver,
        private ResultRequestBuilder $requestBuilder,
        private ResultFetcher $fetcher,
        private ResultParser $parser,
        private ResultCandidateSelector $selector,
        private ResultMapper $mapper,
        private ResultValidator $validator,
        private ResultApplier $applier,
        private AutoResultHardeningService $hardening
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function processDraw(
        LottoDraw $draw,
        bool $dryRun = false,
        bool $isManualRetry = false,
        ?string $runId = null,
        ?string $expectedDrawDate = null
    ): array {
        $draw = $draw->fresh(['market']);
        $runId = $runId ?: ('run_' . $draw->id . '_' . now()->format('YmdHisv'));
        $expectedDrawDate = $expectedDrawDate ?: ($draw->draw_date ? $draw->draw_date->format('Y-m-d') : null);

        $sources = $this->resolver->resolveAll($draw);
        if ($sources === []) {
            return $this->markWithoutLog($draw, [
                'status' => 'NO_SOURCE',
                'attempt_no' => max(1, ((int) ($draw->result_fetch_attempts ?? 0)) + 1),
                'run_id' => $runId,
                'pipeline_stage' => 'resolve',
                'is_dry_run' => $dryRun,
                'is_manual_retry' => $isManualRetry,
                'error_message' => 'ไม่พบ source ที่ active ตามเงื่อนไขเวลา',
            ]);
        }

        $fallbackTried = false;
        $skippedRateLimit = false;
        foreach ($sources as $source) {
            $this->resolver->persistSnapshot($draw, $source);
            if (! $isManualRetry) {
                $state = $this->sourceRetryState($draw, $source);
                if ($state['exhausted']) {
                    $fallbackTried = true;
                    $this->logSourceExhaustedIfNeeded($draw, $source, $runId, (int) $state['attempts']);
                    continue;
                }

                if (! $state['retry_due']) {
                    return [
                        'status' => 'SKIPPED_BACKOFF',
                        'source_id' => (int) $source->id,
                        'skipped_backoff' => true,
                    ];
                }
            }

            if (! $this->hardening->acquireSourceRateLimit((int) $source->id)) {
                $skippedRateLimit = true;
                continue;
            }

            $result = $this->processSourceAttempt(
                $draw,
                $source,
                $dryRun,
                $isManualRetry,
                $runId,
                $expectedDrawDate
                );

            if ((string) ($result['status'] ?? '') === self::RETRYABLE_STATUS) {
                if (! $isManualRetry) {
                    $stateAfter = $this->sourceRetryState($draw->fresh(), $source);
                    if ($stateAfter['exhausted']) {
                        $fallbackTried = true;
                        $this->logSourceExhaustedIfNeeded($draw, $source, $runId, (int) $stateAfter['attempts']);
                        continue;
                    }
                }
            }

            return $result;
        }

        if ($fallbackTried) {
            $this->markExhausted($draw->fresh());

            return [
                'status' => 'EXHAUSTED',
                'run_id' => $runId,
                'attempt_no' => (int) ($draw->result_fetch_attempts ?? 0),
                'error_message' => 'all active sources exhausted retry policy',
            ];
        }

        if ($skippedRateLimit) {
            return [
                'status' => 'SKIPPED_RATE_LIMIT',
                'skipped_rate_limit' => true,
            ];
        }

        return $this->markWithoutLog($draw, [
            'status' => 'NO_SOURCE',
            'attempt_no' => max(1, ((int) ($draw->result_fetch_attempts ?? 0)) + 1),
            'run_id' => $runId,
            'pipeline_stage' => 'resolve',
            'is_dry_run' => $dryRun,
            'is_manual_retry' => $isManualRetry,
            'error_message' => 'ไม่พบ source ที่พร้อม retry ตาม policy',
        ]);
    }

    /**
     * @return array{attempts:int,retry_due:bool,exhausted:bool}
     */
    private function sourceRetryState(LottoDraw $draw, LottoResultSource $source): array
    {
        $maxAttempts = max(1, (int) config('lotto_auto_result.retry.max_attempts', 27));
        $query = LottoResultFetchLog::query()
            ->where('draw_id', (int) $draw->id)
            ->where('source_id', (int) $source->id)
            ->where('is_dry_run', false)
            ->where('status', self::RETRYABLE_STATUS);

        $attempts = (int) (clone $query)->count();
        $exhausted = $attempts >= $maxAttempts;
        if ($exhausted) {
            return ['attempts' => $attempts, 'retry_due' => false, 'exhausted' => true];
        }

        $last = (clone $query)->orderByDesc('id')->first();
        if (! $last || ! $last->created_at instanceof Carbon) {
            return ['attempts' => $attempts, 'retry_due' => true, 'exhausted' => false];
        }

        $baseSeconds = max(5, (int) config('lotto_auto_result.retry.base_backoff_seconds', 10));
        $maxSeconds = max(60, (int) config('lotto_auto_result.retry.max_backoff_seconds', 300));
        $pow = min(6, max(0, $attempts - 1));
        $next = $last->created_at->copy()->addSeconds(min($maxSeconds, $baseSeconds * (2 ** $pow)));

        return [
            'attempts' => $attempts,
            'retry_due' => now()->gte($next),
            'exhausted' => false,
        ];
    }

    private function logSourceExhaustedIfNeeded(LottoDraw $draw, LottoResultSource $source, string $runId, int $attempts): void
    {
        return;
    }

    /**
     * @return array<string,mixed>
     */
    private function processSourceAttempt(
        LottoDraw $draw,
        LottoResultSource $source,
        bool $dryRun,
        bool $isManualRetry,
        string $runId,
        ?string $expectedDrawDate
    ): array {
        $shadowResult = null;
        if ($this->isV2ShadowEnabled($source)) {
            try {
                $shadowResult = (new LottoResultPipelineRunner())->run($draw, $source, [
                    'run_id' => $runId,
                    'expected_draw_date' => $expectedDrawDate,
                ]);
            } catch (\Throwable $e) {
                $shadowResult = [
                    'shadow_compare' => [
                        'shadow_compare_status' => 'ERROR',
                        'error_message' => $e->getMessage(),
                    ],
                ];
            }
        }

        if ($this->isV2CutoverEnabled($source)) {
            $this->incrementAttempt($draw);
            $attemptNo = max(1, (int) ($draw->result_fetch_attempts ?? 1));
            try {
                $v2Result = $this->runV2CutoverPipeline($draw, $source, $runId, $expectedDrawDate);
            } catch (\Throwable $e) {
                return $this->markAndLog($draw, [
                    'status' => 'VALIDATION_ERROR',
                    'attempt_no' => $attemptNo,
                    'run_id' => $runId,
                    'pipeline_stage' => 'v2_cutover',
                    'source_id' => (int) $source->id,
                    'is_dry_run' => $dryRun,
                    'is_manual_retry' => $isManualRetry,
                    'trace_json' => [
                        'exception_class' => get_class($e),
                        'message' => $e->getMessage(),
                    ],
                    'error_code' => 'UNHANDLED_EXCEPTION',
                    'error_stage' => 'PIPELINE',
                    'error_message' => $e->getMessage() !== '' ? $e->getMessage() : 'Unhandled V2 cutover exception',
                ], 'VALIDATION_ERROR');
            }

            if ((string) ($v2Result['status'] ?? '') !== 'VALID') {
                $failure = $this->resolveV2CutoverFailureOutcome(
                    (string) ($v2Result['status'] ?? ''),
                    (string) ($v2Result['error_code'] ?? ''),
                    (string) ($v2Result['error_message'] ?? '')
                );

                return $this->markAndLog($draw, [
                    'status' => $failure['status'],
                    'attempt_no' => $attemptNo,
                    'run_id' => $runId,
                    'pipeline_stage' => 'v2_cutover',
                    'source_id' => (int) $source->id,
                    'is_dry_run' => $dryRun,
                    'is_manual_retry' => $isManualRetry,
                    'trace_json' => $v2Result['trace_json'] ?? null,
                    'error_code' => (string) ($v2Result['error_code'] ?? 'VALIDATION_ERROR'),
                    'error_stage' => (string) ($v2Result['error_stage'] ?? 'READINESS'),
                    'error_message' => $failure['error_message'],
                ], (string) ($failure['draw_status_override'] ?? null));
            }

            $validated = (array) ($v2Result['validated'] ?? []);
            $applyResult = $this->applier->apply($draw, $validated, [
                'selection' => $v2Result['selection'] ?? [],
                'mapped' => $v2Result['mapped'] ?? [],
                'v2_trace' => $v2Result['trace_json'] ?? [],
            ], $dryRun);

            $status = $this->normalizeStatus((string) ($applyResult['status'] ?? 'APPLIED'));

            return $this->markAndLog($draw, [
                'status' => $status,
                'attempt_no' => $attemptNo,
                'run_id' => $runId,
                'pipeline_stage' => 'apply_v2',
                'source_id' => (int) $source->id,
                'normalized_result_json' => $validated,
                'trace_json' => $v2Result['trace_json'] ?? null,
                'error_code' => (string) ($v2Result['error_code'] ?? null),
                'error_stage' => (string) ($v2Result['error_stage'] ?? null),
                'shadow_compare_status' => $shadowResult['shadow_compare']['shadow_compare_status'] ?? null,
                'shadow_diff_json' => $shadowResult['shadow_compare']['mismatches'] ?? null,
                'legacy_result_json' => $shadowResult['canonical_outcome'] ?? null,
                'v2_result_json' => $v2Result['validated'] ?? null,
                'is_dry_run' => $dryRun,
                'is_manual_retry' => $isManualRetry,
                'error_message' => $status === 'CONFLICT' ? 'Result conflict' : null,
            ], $status === 'APPLIED' ? null : $status);
        }

        try {
            $built = $this->requestBuilder->build($draw, $source);
        } catch (TemplateRenderException $e) {
            return $this->markWithoutLog($draw, [
                'status' => 'TEMPLATE_ERROR',
                'attempt_no' => max(1, ((int) ($draw->result_fetch_attempts ?? 0)) + 1),
                'run_id' => $runId,
                'pipeline_stage' => 'request_build',
                'source_id' => (int) $source->id,
                'is_dry_run' => $dryRun,
                'is_manual_retry' => $isManualRetry,
                'error_message' => $e->getMessage(),
            ]);
        }

        $this->incrementAttempt($draw);
        $attemptNo = max(1, (int) ($draw->result_fetch_attempts ?? 1));

        $fetched = $this->fetcher->fetch($source, $built);
        if ((string) $fetched['status'] === 'HTTP_ERROR') {
            return $this->markAndLog($draw, [
                'status' => 'HTTP_ERROR',
                'attempt_no' => $attemptNo,
                'run_id' => $runId,
                'pipeline_stage' => 'fetch',
                'source_id' => (int) $source->id,
                'request_url' => (string) $built['url'],
                'response_http_status' => $fetched['http_status'],
                'response_body' => $fetched['response_body'],
                'duration_ms' => (int) $fetched['duration_ms'],
                'is_dry_run' => $dryRun,
                'is_manual_retry' => $isManualRetry,
                'error_message' => (string) ($fetched['error_message'] ?? 'HTTP_ERROR'),
            ]);
        }

        $responseBody = (string) ($fetched['response_body'] ?? '');

        try {
            $parsed = $this->parser->parse((string) $source->parser_type, (array) ($source->parser_config_json ?? []), $responseBody);
        } catch (ResultParseException $e) {
            return $this->markAndLog($draw, [
                'status' => 'PARSE_ERROR',
                'attempt_no' => $attemptNo,
                'run_id' => $runId,
                'pipeline_stage' => 'parse',
                'source_id' => (int) $source->id,
                'request_url' => (string) $built['url'],
                'response_http_status' => $fetched['http_status'],
                'response_body' => $responseBody,
                'duration_ms' => (int) $fetched['duration_ms'],
                'is_dry_run' => $dryRun,
                'is_manual_retry' => $isManualRetry,
                'error_message' => $e->getMessage(),
            ]);
        }

        $selection = $this->selector->select(
            $parsed,
            (array) ($source->parser_config_json ?? []),
            (array) ($source->validation_config_json ?? []),
            new ResultParseContext($expectedDrawDate)
        );
        $selectionWithParserDebug = $selection;
        if (isset($parsed['_debug']) && is_array($parsed['_debug'])) {
            $selectionWithParserDebug['parser_debug'] = $parsed['_debug'];
        }

        if ((string) ($selection['decision'] ?? '') !== 'selected') {
            return $this->markAndLog($draw, [
                'status' => 'VALIDATION_ERROR',
                'attempt_no' => $attemptNo,
                'run_id' => $runId,
                'pipeline_stage' => 'select',
                'source_id' => (int) $source->id,
                'request_url' => (string) $built['url'],
                'response_http_status' => $fetched['http_status'],
                'response_body' => $responseBody,
                'parsed_payload_json' => $parsed,
                'selection_debug_json' => $selectionWithParserDebug,
                'duration_ms' => (int) $fetched['duration_ms'],
                'is_dry_run' => $dryRun,
                'is_manual_retry' => $isManualRetry,
                'error_message' => 'VALIDATION_ERROR: selection rejected (' . (string) ($selection['rejection_reason'] ?? 'unknown') . ')',
            ]);
        }

        $selectedFields = (array) (($selection['selected_candidate']['fields'] ?? []));

        try {
            $mapped = $this->mapper->map($selectedFields, (array) ($source->mapping_config_json ?? []));
            $validated = $this->validator->validate(
                $mapped,
                (array) ($source->validation_config_json ?? []),
                $expectedDrawDate
            );
        } catch (ResultValidationException $e) {
            $message = $e->getMessage();
            $status = str_contains($message, 'NOT_READY') ? 'NOT_READY' : 'VALIDATION_ERROR';

            return $this->markAndLog($draw, [
                'status' => $status,
                'attempt_no' => $attemptNo,
                'run_id' => $runId,
                'pipeline_stage' => 'validate',
                'source_id' => (int) $source->id,
                'request_url' => (string) $built['url'],
                'response_http_status' => $fetched['http_status'],
                'response_body' => $responseBody,
                'parsed_payload_json' => $parsed,
                'selection_debug_json' => $selectionWithParserDebug,
                'normalized_result_json' => $mapped ?? [],
                'duration_ms' => (int) $fetched['duration_ms'],
                'is_dry_run' => $dryRun,
                'is_manual_retry' => $isManualRetry,
                'error_message' => $message,
            ]);
        }

        $applyResult = $this->applier->apply($draw, $validated, [
            'request' => $built,
            'response_http_status' => $fetched['http_status'],
            'response_body' => $responseBody,
            'parsed' => $parsed,
            'selection' => $selectionWithParserDebug,
            'mapped' => $mapped,
            'shadow' => $shadowResult,
        ], $dryRun);

        $status = $this->normalizeStatus((string) ($applyResult['status'] ?? 'APPLIED'));

        return $this->markAndLog($draw, [
            'status' => $status,
            'attempt_no' => $attemptNo,
            'run_id' => $runId,
            'pipeline_stage' => 'apply',
            'source_id' => (int) $source->id,
            'request_url' => (string) $built['url'],
            'response_http_status' => $fetched['http_status'],
            'response_body' => $responseBody,
            'parsed_payload_json' => $parsed,
            'selection_debug_json' => $selectionWithParserDebug,
            'normalized_result_json' => $validated,
            'shadow_compare_status' => $shadowResult['shadow_compare']['shadow_compare_status'] ?? null,
            'shadow_diff_json' => $shadowResult['shadow_compare']['mismatches'] ?? null,
            'legacy_result_json' => $shadowResult['canonical_outcome'] ?? null,
            'v2_result_json' => $shadowResult['v2_shadow_result']['validated'] ?? null,
            'duration_ms' => (int) $fetched['duration_ms'],
            'is_dry_run' => $dryRun,
            'is_manual_retry' => $isManualRetry,
            'error_message' => $status === 'CONFLICT' ? 'Result conflict' : null,
        ], $status === 'APPLIED' ? null : $status);
    }

    public function shouldRetryNow(LottoDraw $draw, Carbon $now): bool
    {
        if ((string) $draw->result_fetch_status !== self::RETRYABLE_STATUS) {
            return false;
        }

        $attempts = (int) ($draw->result_fetch_attempts ?? 0);
        $maxAttempts = max(1, (int) config('lotto_auto_result.retry.max_attempts', 27));
        if ($attempts >= $maxAttempts) {
            return false;
        }

        $last = $draw->result_fetched_at ? Carbon::parse((string) $draw->result_fetched_at) : null;
        if (! $last) {
            return true;
        }

        $baseSeconds = max(5, (int) config('lotto_auto_result.retry.base_backoff_seconds', 10));
        $maxSeconds = max(60, (int) config('lotto_auto_result.retry.max_backoff_seconds', 300));
        $pow = min(6, max(0, $attempts - 1));
        $next = $last->copy()->addSeconds(min($maxSeconds, $baseSeconds * (2 ** $pow)));

        return $now->gte($next);
    }

    public function markExhausted(LottoDraw $draw): void
    {
        if ($this->canWriteDrawFetchFields()) {
            $draw->forceFill([
                'result_fetch_status' => 'EXHAUSTED',
                'result_fetch_error' => 'retry attempts exhausted',
                'result_exhausted_at' => now(),
                'result_fetched_at' => now(),
            ])->saveQuietly();
        }

        $this->hardening->notifyExhaustedDraw(
            $draw->fresh(['market']),
            (int) ($draw->result_fetch_attempts ?? 0),
            null
        );
    }

    /**
     * @param array<string,mixed> $logPayload
     * @return array<string,mixed>
     */
    private function markAndLog(LottoDraw $draw, array $logPayload, ?string $drawStatusOverride = null): array
    {
        $status = $this->normalizeStatus((string) ($logPayload['status'] ?? 'VALIDATION_ERROR'));

        $this->hardening->insertFetchLog(array_merge([
            'draw_id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'is_manual_settle' => false,
            'created_at' => now()->toDateTimeString(),
        ], $logPayload));

        $isManualRetry = (bool) ($logPayload['is_manual_retry'] ?? false);

        if ($this->canWriteDrawFetchFields() && ($isManualRetry || ! in_array((string) $draw->result_fetch_status, self::TERMINAL_DRAW_STATUSES, true))) {
            $draw->forceFill([
                'result_fetch_status' => $this->normalizeStatus((string) ($drawStatusOverride ?? $status)),
                'result_fetch_error' => $logPayload['error_message'] ?? null,
                'result_fetched_at' => now(),
            ])->saveQuietly();
        }

        return [
            'status' => $status,
            'run_id' => $logPayload['run_id'] ?? null,
            'attempt_no' => (int) ($logPayload['attempt_no'] ?? 1),
            'error_message' => $logPayload['error_message'] ?? null,
        ];
    }

    private function markWithoutLog(LottoDraw $draw, array $payload, ?string $drawStatusOverride = null): array
    {
        $status = $this->normalizeStatus((string) ($payload['status'] ?? 'VALIDATION_ERROR'));
        $isManualRetry = (bool) ($payload['is_manual_retry'] ?? false);

        if ($this->canWriteDrawFetchFields() && ($isManualRetry || ! in_array((string) $draw->result_fetch_status, self::TERMINAL_DRAW_STATUSES, true))) {
            $draw->forceFill([
                'result_fetch_status' => $this->normalizeStatus((string) ($drawStatusOverride ?? $status)),
                'result_fetch_error' => $payload['error_message'] ?? null,
                'result_fetched_at' => now(),
            ])->saveQuietly();
        }

        return [
            'status' => $status,
            'run_id' => $payload['run_id'] ?? null,
            'attempt_no' => (int) ($payload['attempt_no'] ?? 1),
            'error_message' => $payload['error_message'] ?? null,
        ];
    }

    private function incrementAttempt(LottoDraw $draw): void
    {
        if (! Schema::hasColumn('lotto_draws', 'result_fetch_attempts')) {
            return;
        }

        $draw->forceFill([
            'result_fetch_attempts' => ((int) ($draw->result_fetch_attempts ?? 0)) + 1,
        ])->saveQuietly();
    }

    private function canWriteDrawFetchFields(): bool
    {
        return Schema::hasColumn('lotto_draws', 'result_fetch_status')
            && Schema::hasColumn('lotto_draws', 'result_fetch_error')
            && Schema::hasColumn('lotto_draws', 'result_fetched_at');
    }

    private function isV2ShadowEnabled(LottoResultSource $source): bool
    {
        return false;
    }

    private function isV2CutoverEnabled(LottoResultSource $source): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    protected function runV2CutoverPipeline(
        LottoDraw $draw,
        LottoResultSource $source,
        string $runId,
        ?string $expectedDrawDate
    ): array {
        return (new LottoResultPipelineRunner())->run($draw, $source, [
            'run_id' => $runId,
            'expected_draw_date' => $expectedDrawDate,
        ]);
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, self::CANONICAL_STATUSES, true) ? $status : 'VALIDATION_ERROR';
    }

    /**
     * @return array{status:string,draw_status_override:?string,error_message:string}
     */
    private function resolveV2CutoverFailureOutcome(string $status, string $errorCode, string $errorMessage): array
    {
        $status = strtoupper(trim($status));
        $errorCode = strtoupper(trim($errorCode));
        $message = trim($errorMessage) !== '' ? trim($errorMessage) : 'V2 cutover validation failed';

        if (
            $status === 'NOT_READY'
            || str_starts_with($errorCode, 'NOT_READY')
            || $status === 'FETCH_DEFERRED'
            || $this->isRetryableNetworkErrorCode($errorCode)
        ) {
            return [
                'status' => 'NOT_READY',
                'draw_status_override' => 'NOT_READY',
                'error_message' => $message,
            ];
        }

        if ($status === 'APP_SHELL_ONLY' || $errorCode === 'APP_SHELL_ONLY') {
            return [
                'status' => 'VALIDATION_ERROR',
                'draw_status_override' => 'VALIDATION_ERROR',
                'error_message' => $message !== '' ? $message : 'APP_SHELL_ONLY',
            ];
        }

        return [
            'status' => 'VALIDATION_ERROR',
            'draw_status_override' => 'VALIDATION_ERROR',
            'error_message' => $message,
        ];
    }

    private function isRetryableNetworkErrorCode(string $errorCode): bool
    {
        if ($errorCode === '') {
            return false;
        }

        if (str_starts_with($errorCode, 'HTTP_STATUS_')) {
            $statusCode = (int) str_replace('HTTP_STATUS_', '', $errorCode);

            return $statusCode >= 500 || $statusCode === 429;
        }

        return in_array($errorCode, [
            'FETCH_DEFERRED',
            'NETWORK_ERROR',
            'FETCH_EXCEPTION',
            'BROWSER_TIMEOUT',
            'BROWSER_RUNTIME_EXCEPTION',
        ], true);
    }
}
