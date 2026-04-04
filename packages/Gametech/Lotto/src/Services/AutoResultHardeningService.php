<?php

namespace Gametech\Lotto\Services;

use App\Jobs\SendTelegramBot;
use Gametech\Lotto\Models\LottoDraw;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class AutoResultHardeningService
{
    public const STATUS_APPLIED = 'APPLIED';
    public const STATUS_CONFLICT = 'CONFLICT';
    public const STATUS_EXHAUSTED = 'EXHAUSTED';
    public const STATUS_RATE_LIMITED = 'RATE_LIMITED';

    /**
     * @var string[]
     */
    private const TERMINAL_DRAW_FETCH_STATUSES = [
        self::STATUS_APPLIED,
        self::STATUS_CONFLICT,
        self::STATUS_EXHAUSTED,
    ];

    /**
     * @var string[]|null
     */
    private $fetchLogColumns;

    public function handleExhaustedTransition(LottoDraw $draw): void
    {
        if ((string) $draw->result_fetch_status !== self::STATUS_EXHAUSTED) {
            return;
        }

        if (! (bool) config('lotto_auto_result.hardening.alerts.enabled', true)) {
            return;
        }

        if ($draw->exhausted_alerted_at !== null) {
            return;
        }

        $dedupeSeconds = max(1, (int) config('lotto_auto_result.hardening.alerts.dedupe_seconds', 21600));
        $rateKey = sprintf('lotto:auto-result:exhausted-alert:draw:%d', (int) $draw->id);

        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            return;
        }

        RateLimiter::hit($rateKey, $dedupeSeconds);

        $payload = [
            'draw_id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'status' => (string) $draw->status,
            'result_fetch_status' => (string) $draw->result_fetch_status,
            'result_fetch_attempts' => (int) ($draw->result_fetch_attempts ?? 0),
            'result_at' => $this->formatDateTime($draw->result_at),
            'result_fetched_at' => $this->formatDateTime($draw->result_fetched_at),
            'last_error' => (string) ($draw->result_fetch_error ?? ''),
        ];
        $draw->loadMissing('market:id,name');

        $logChannel = (string) config('lotto_auto_result.hardening.alerts.log_channel', 'daily');
        Log::channel($logChannel)->warning('LOTTO_AUTO_RESULT_EXHAUSTED', $payload);

        $marketName = (string) ($draw->market->name ?? ('Market #' . (int) $draw->market_id));
        $drawDate = $draw->draw_date ? $draw->draw_date->format('Y-m-d') : '-';
        $resultAt = $draw->result_at ? $draw->result_at->copy()->setTimezone($this->timezone())->format('Y-m-d H:i:s') : '-';
        $lastError = (string) ($draw->result_fetch_error ?? '-');

        $message = sprintf(
            'หวย%s งวดวันที่ %s เวลาออกผล %s ไม่สามารถดึงผลรางวัลได้' . PHP_EOL .
            'attempts=%d, error=%s',
            $marketName,
            $drawDate,
            $resultAt,
            (int) ($draw->result_fetch_attempts ?? 0),
            $lastError
        );

        $endpoint = (string) config('lotto_auto_result.hardening.alerts.telegram_endpoint', 'notify/send');
        $queue = (string) config('lotto_auto_result.hardening.alerts.telegram_queue', 'cashback');
        SendTelegramBot::dispatch($endpoint, $message)->onQueue($queue);

        if (Schema::hasTable('lotto_draws') && Schema::hasColumn('lotto_draws', 'exhausted_alerted_at')) {
            $draw->forceFill(['exhausted_alerted_at' => $this->now()])->saveQuietly();
        }
    }

    public function acquireSourceRateLimit(int $sourceId): bool
    {
        if (! (bool) config('lotto_auto_result.hardening.rate_limit.enabled', true)) {
            return true;
        }

        $maxAttempts = max(1, (int) config('lotto_auto_result.hardening.rate_limit.per_source_per_minute', 30));
        $windowSeconds = max(1, (int) config('lotto_auto_result.hardening.rate_limit.window_seconds', 60));
        $key = sprintf('lotto:auto-result:source:%d', $sourceId);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        RateLimiter::hit($key, $windowSeconds);

        return true;
    }

    public function recordRateLimited(LottoDraw $draw, ?int $sourceId, int $attemptNo, string $errorMessage = 'Per-source rate limit exceeded'): void
    {
        $this->insertFetchLog([
            'draw_id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'source_id' => $sourceId,
            'attempt_no' => max(1, $attemptNo),
            'status' => self::STATUS_RATE_LIMITED,
            'error_message' => $errorMessage,
            'created_at' => $this->now()->toDateTimeString(),
        ]);

        if (in_array((string) $draw->result_fetch_status, self::TERMINAL_DRAW_FETCH_STATUSES, true)) {
            return;
        }

        if (! $this->canUpdateDrawFetchFields()) {
            return;
        }

        $draw->forceFill([
            'result_fetch_status' => self::STATUS_RATE_LIMITED,
            'result_fetch_error' => $errorMessage,
            'result_fetched_at' => $this->now(),
        ])->saveQuietly();
    }

    /**
     * @param array<string,mixed> $context
     */
    public function recordUnhandledException(LottoDraw $draw, array $context = []): void
    {
        $sourceId = isset($context['source_id']) ? (int) $context['source_id'] : (isset($draw->result_source_id) ? (int) $draw->result_source_id : null);
        $attemptNo = max(1, (int) ($context['attempt_no'] ?? ($draw->result_fetch_attempts ?? 1)));
        $message = trim((string) ($context['error_message'] ?? 'Unhandled auto-result exception'));
        $exceptionClass = trim((string) ($context['exception_class'] ?? 'RuntimeException'));
        $pipelineStage = trim((string) ($context['pipeline_stage'] ?? 'process_draw_exception'));
        $errorStage = trim((string) ($context['error_stage'] ?? 'PIPELINE'));
        $runId = isset($context['run_id']) ? (string) $context['run_id'] : null;

        $this->insertFetchLog([
            'draw_id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'source_id' => $sourceId,
            'attempt_no' => $attemptNo,
            'status' => 'VALIDATION_ERROR',
            'pipeline_stage' => $pipelineStage,
            'run_id' => $runId,
            'error_code' => 'UNHANDLED_EXCEPTION',
            'error_stage' => $errorStage,
            'trace_json' => [
                'exception_class' => $exceptionClass,
                'message' => $message,
                'source_id' => $sourceId,
            ],
            'error_message' => $message,
            'created_at' => $this->now()->toDateTimeString(),
        ]);

        if (in_array((string) $draw->result_fetch_status, self::TERMINAL_DRAW_FETCH_STATUSES, true)) {
            return;
        }

        if (! $this->canUpdateDrawFetchFields()) {
            return;
        }

        $draw->forceFill([
            'result_fetch_status' => 'VALIDATION_ERROR',
            'result_fetch_error' => $message,
            'result_fetched_at' => $this->now(),
        ])->saveQuietly();
    }

    /**
     * @return array<string, mixed>
     */
    public function metrics(?Carbon $from = null, ?Carbon $to = null): array
    {
        if (! Schema::hasTable('lotto_result_fetch_logs')) {
            return [
                'window' => null,
                'timezone' => $this->timezone(),
                'successful_settles' => 0,
                'total_fetch_executions' => 0,
                'retry_count' => 0,
                'success_rate' => 0.0,
                'by_source' => [],
            ];
        }

        $end = $to ? $to->copy()->setTimezone($this->timezone()) : $this->now();
        $windowHours = max(1, (int) config('lotto_auto_result.hardening.metrics.default_window_hours', 24));
        $start = $from ? $from->copy()->setTimezone($this->timezone()) : $end->copy()->subHours($windowHours);

        $base = DB::table('lotto_result_fetch_logs')
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->where('is_dry_run', false)
            ->where('is_manual_settle', false);

        $successfulSettles = (clone $base)
            ->where('status', self::STATUS_APPLIED)
            ->count();

        $totalFetchExecutions = (clone $base)
            ->where('status', '!=', self::STATUS_RATE_LIMITED)
            ->count();

        $retryCount = (clone $base)
            ->where('attempt_no', '>', 1)
            ->count();

        $bySource = (clone $base)
            ->select([
                'source_id',
                DB::raw('COUNT(CASE WHEN status != "' . self::STATUS_RATE_LIMITED . '" THEN 1 END) as total_fetch_executions'),
                DB::raw('COUNT(CASE WHEN status = "' . self::STATUS_APPLIED . '" THEN 1 END) as successful_settles'),
                DB::raw('COUNT(CASE WHEN attempt_no > 1 THEN 1 END) as retry_count'),
            ])
            ->groupBy('source_id')
            ->orderBy('source_id')
            ->get()
            ->map(static function ($row): array {
                $total = (int) ($row->total_fetch_executions ?? 0);
                $success = (int) ($row->successful_settles ?? 0);

                return [
                    'source_id' => $row->source_id !== null ? (int) $row->source_id : null,
                    'total_fetch_executions' => $total,
                    'successful_settles' => $success,
                    'retry_count' => (int) ($row->retry_count ?? 0),
                    'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0.0,
                ];
            })
            ->values()
            ->all();

        return [
            'window' => [
                'from' => $start->toDateTimeString(),
                'to' => $end->toDateTimeString(),
            ],
            'timezone' => $this->timezone(),
            'successful_settles' => $successfulSettles,
            'total_fetch_executions' => $totalFetchExecutions,
            'retry_count' => $retryCount,
            'success_rate' => $totalFetchExecutions > 0
                ? round(($successfulSettles / $totalFetchExecutions) * 100, 2)
                : 0.0,
            'by_source' => $bySource,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function insertFetchLog(array $payload): void
    {
        if (! Schema::hasTable('lotto_result_fetch_logs')) {
            return;
        }

        $columns = $this->fetchLogColumns();
        if (empty($columns)) {
            return;
        }

        $row = [];
        $candidates = [
            'draw_id' => $payload['draw_id'] ?? null,
            'market_id' => $payload['market_id'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'attempt_no' => max(1, (int) ($payload['attempt_no'] ?? 1)),
            'status' => (string) ($payload['status'] ?? 'VALIDATION_ERROR'),
            'pipeline_stage' => $payload['pipeline_stage'] ?? null,
            'run_id' => $payload['run_id'] ?? null,
            'request_url' => $payload['request_url'] ?? null,
            'request_meta_json' => isset($payload['request_meta_json']) ? json_encode($payload['request_meta_json'], JSON_UNESCAPED_UNICODE) : null,
            'response_http_status' => $payload['response_http_status'] ?? null,
            'response_body' => $payload['response_body'] ?? null,
            'parsed_payload_json' => isset($payload['parsed_payload_json']) ? json_encode($payload['parsed_payload_json'], JSON_UNESCAPED_UNICODE) : null,
            'normalized_result_json' => isset($payload['normalized_result_json']) ? json_encode($payload['normalized_result_json'], JSON_UNESCAPED_UNICODE) : null,
            'selection_debug_json' => isset($payload['selection_debug_json']) ? json_encode($payload['selection_debug_json'], JSON_UNESCAPED_UNICODE) : null,
            'trace_json' => isset($payload['trace_json']) ? json_encode($payload['trace_json'], JSON_UNESCAPED_UNICODE) : null,
            'legacy_result_json' => isset($payload['legacy_result_json']) ? json_encode($payload['legacy_result_json'], JSON_UNESCAPED_UNICODE) : null,
            'v2_result_json' => isset($payload['v2_result_json']) ? json_encode($payload['v2_result_json'], JSON_UNESCAPED_UNICODE) : null,
            'shadow_diff_json' => isset($payload['shadow_diff_json']) ? json_encode($payload['shadow_diff_json'], JSON_UNESCAPED_UNICODE) : null,
            'error_code' => $payload['error_code'] ?? null,
            'error_stage' => $payload['error_stage'] ?? null,
            'shadow_compare_status' => $payload['shadow_compare_status'] ?? null,
            'is_dry_run' => (bool) ($payload['is_dry_run'] ?? false),
            'is_manual_settle' => (bool) ($payload['is_manual_settle'] ?? false),
            'is_manual_retry' => (bool) ($payload['is_manual_retry'] ?? false),
            'error_message' => $payload['error_message'] ?? null,
            'duration_ms' => $payload['duration_ms'] ?? null,
            'created_at' => $payload['created_at'] ?? $this->now()->toDateTimeString(),
        ];

        foreach ($candidates as $column => $value) {
            if (in_array($column, $columns, true)) {
                $row[$column] = $value;
            }
        }

        if (empty($row)) {
            return;
        }

        DB::table('lotto_result_fetch_logs')->insert($row);
    }

    /**
     * @return string[]
     */
    private function fetchLogColumns(): array
    {
        if ($this->fetchLogColumns !== null) {
            return $this->fetchLogColumns;
        }

        try {
            $this->fetchLogColumns = Schema::getColumnListing('lotto_result_fetch_logs');
        } catch (\Throwable $e) {
            $this->fetchLogColumns = [];
        }

        return $this->fetchLogColumns;
    }

    private function canUpdateDrawFetchFields(): bool
    {
        return Schema::hasTable('lotto_draws')
            && Schema::hasColumn('lotto_draws', 'result_fetch_status')
            && Schema::hasColumn('lotto_draws', 'result_fetch_error')
            && Schema::hasColumn('lotto_draws', 'result_fetched_at');
    }

    private function timezone(): string
    {
        return (string) config('lotto_auto_result.timezone', (string) config('app.timezone', 'Asia/Bangkok'));
    }

    private function now(): Carbon
    {
        return now($this->timezone());
    }

    private function formatDateTime($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse((string) $value)->setTimezone($this->timezone())->toDateTimeString();
    }
}
