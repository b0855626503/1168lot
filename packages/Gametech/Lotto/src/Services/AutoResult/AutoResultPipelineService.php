<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Exceptions\ResultParseException;
use Gametech\Lotto\Exceptions\ResultValidationException;
use Gametech\Lotto\Exceptions\TemplateRenderException;
use Gametech\Lotto\Models\LottoDraw;
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
        $attemptNo = max(1, ((int) ($draw->result_fetch_attempts ?? 0)) + 1);
        $expectedDrawDate = $expectedDrawDate ?: ($draw->draw_date ? $draw->draw_date->format('Y-m-d') : null);

        $this->incrementAttempt($draw);

        $source = $this->resolver->resolve($draw);
        if (! $source) {
            return $this->markAndLog($draw, [
                'status' => 'NO_SOURCE',
                'attempt_no' => $attemptNo,
                'run_id' => $runId,
                'pipeline_stage' => 'resolve',
                'is_dry_run' => $dryRun,
                'is_manual_retry' => $isManualRetry,
                'error_message' => 'ไม่พบ source ที่ active ตามเงื่อนไขเวลา',
            ]);
        }

        if (! $this->hardening->acquireSourceRateLimit((int) $source->id)) {
            $this->hardening->recordRateLimited($draw, (int) $source->id, $attemptNo, 'RATE_LIMITED by source policy');

            return [
                'status' => 'RATE_LIMITED',
                'run_id' => $runId,
                'attempt_no' => $attemptNo,
            ];
        }

        try {
            $built = $this->requestBuilder->build($draw, $source);
        } catch (TemplateRenderException $e) {
            return $this->markAndLog($draw, [
                'status' => 'TEMPLATE_ERROR',
                'attempt_no' => $attemptNo,
                'run_id' => $runId,
                'pipeline_stage' => 'request_build',
                'source_id' => (int) $source->id,
                'is_dry_run' => $dryRun,
                'is_manual_retry' => $isManualRetry,
                'error_message' => $e->getMessage(),
            ]);
        }

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
        if ($attempts >= 27) {
            return false;
        }

        $last = $draw->result_fetched_at ? Carbon::parse((string) $draw->result_fetched_at) : null;
        if (! $last) {
            return true;
        }

        $next = $attempts < 15 ? $last->copy()->addMinute() : $last->copy()->addMinutes(5);

        return $now->gte($next);
    }

    public function markExhausted(LottoDraw $draw): void
    {
        if (! $this->canWriteDrawFetchFields()) {
            return;
        }

        $draw->forceFill([
            'result_fetch_status' => 'EXHAUSTED',
            'result_fetch_error' => 'retry attempts exhausted',
            'result_exhausted_at' => now(),
            'result_fetched_at' => now(),
        ])->save();

        $this->hardening->insertFetchLog([
            'draw_id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'attempt_no' => (int) ($draw->result_fetch_attempts ?? 0),
            'status' => 'EXHAUSTED',
            'pipeline_stage' => 'retry_policy',
            'error_message' => 'retry attempts exhausted',
            'created_at' => now()->toDateTimeString(),
        ]);
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

        if ($this->canWriteDrawFetchFields() && ! in_array((string) $draw->result_fetch_status, self::TERMINAL_DRAW_STATUSES, true)) {
            $draw->forceFill([
                'result_fetch_status' => $this->normalizeStatus((string) ($drawStatusOverride ?? $status)),
                'result_fetch_error' => $logPayload['error_message'] ?? null,
                'result_fetched_at' => now(),
            ])->save();
        }

        return [
            'status' => $status,
            'run_id' => $logPayload['run_id'] ?? null,
            'attempt_no' => (int) ($logPayload['attempt_no'] ?? 1),
            'error_message' => $logPayload['error_message'] ?? null,
        ];
    }

    private function incrementAttempt(LottoDraw $draw): void
    {
        if (! Schema::hasColumn('lotto_draws', 'result_fetch_attempts')) {
            return;
        }

        $draw->forceFill([
            'result_fetch_attempts' => ((int) ($draw->result_fetch_attempts ?? 0)) + 1,
        ])->save();
    }

    private function canWriteDrawFetchFields(): bool
    {
        return Schema::hasColumn('lotto_draws', 'result_fetch_status')
            && Schema::hasColumn('lotto_draws', 'result_fetch_error')
            && Schema::hasColumn('lotto_draws', 'result_fetched_at');
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, self::CANONICAL_STATUSES, true) ? $status : 'VALIDATION_ERROR';
    }
}
