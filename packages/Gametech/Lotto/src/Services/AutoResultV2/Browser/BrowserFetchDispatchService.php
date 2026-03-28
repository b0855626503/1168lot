<?php

namespace Gametech\Lotto\Services\AutoResultV2\Browser;

use Gametech\Lotto\Jobs\ExecuteRenderedBrowserFetchJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class BrowserFetchDispatchService
{
    private const LOCK_PREFIX = 'lotto:auto-result:browser-fetch:lock:';
    private const CACHE_PREFIX = 'lotto:auto-result:browser-fetch:';
    private const MAX_CAPTURE_PATTERNS = 12;

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function dispatch(array $payload): array
    {
        $receipt = $this->buildReceiptKey($payload);
        $request = is_array($payload['request'] ?? null) ? $payload['request'] : [];
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        $lockSeconds = max(10, (int) config('lotto_auto_result.browser_worker.lock_ttl_seconds', 120));

        if (! $this->acquireLock($receipt, $lockSeconds)) {
            return [
                'job_id' => $receipt,
                'receipt_key' => $receipt,
                'status' => 'FETCH_DEFERRED',
                'dispatched' => false,
                'reason' => 'LOCKED',
            ];
        }

        $job = new ExecuteRenderedBrowserFetchJob($request, $context, $receipt);

        Bus::dispatch($job);

        return [
            'job_id' => $receipt,
            'receipt_key' => $receipt,
            'status' => 'FETCH_DEFERRED',
            'dispatched' => true,
        ];
    }

    public function buildReceiptKey(array $payload): string
    {
        $request = is_array($payload['request'] ?? null) ? $payload['request'] : [];
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

        $normalized = $this->normalizeForStableHash([
            'request' => [
                'url' => $this->stringOrNull($request['url'] ?? null),
                'method' => strtoupper((string) ($request['method'] ?? 'GET')),
                'headers' => $this->normalizeForStableHash(is_array($request['headers'] ?? null) ? $request['headers'] : []),
                'query' => $this->normalizeForStableHash(is_array($request['query'] ?? null) ? $request['query'] : []),
                'body' => $this->normalizeForStableHash($request['body'] ?? null),
            ],
            'timeout_seconds' => max(1, (int) ($payload['timeout_seconds'] ?? 10)),
            'meta' => $this->normalizeForStableHash($this->sanitizeCaptureMeta($meta)),
            'context' => [
                'source_id' => (int) ($context['source_id'] ?? 0),
                'draw_id' => (int) ($context['draw_id'] ?? 0),
                'endpoint_url' => $this->stringOrNull($context['endpoint_url'] ?? null),
                'strategy' => strtoupper((string) ($context['strategy'] ?? 'RENDERED_BROWSER')),
                'parser_type' => strtoupper((string) ($context['parser_type'] ?? 'JSON_PATH')),
                'expected_draw_date' => $this->stringOrNull($context['expected_draw_date'] ?? null),
            ],
        ]);

        $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', $encoded === false ? '{}' : $encoded);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getCachedPayload(string $receipt): ?array
    {
        $cached = Cache::get($this->cacheKey($receipt));
        if (! is_array($cached)) {
            return null;
        }

        return $cached;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function putCachedPayload(string $receipt, array $payload): void
    {
        $cacheSeconds = max(15, (int) config('lotto_auto_result.browser_worker.cache_ttl_seconds', 180));
        Cache::put($this->cacheKey($receipt), $payload, now()->addSeconds($cacheSeconds));
    }

    public function releaseLock(string $receipt): void
    {
        Cache::forget($this->lockKey($receipt));
        try {
            Redis::del($this->lockKey($receipt));
        } catch (\Throwable) {
            // Intentionally ignore when Redis is unavailable and cache lock is already cleared.
        }
    }

    private function acquireLock(string $receipt, int $ttlSeconds): bool
    {
        $key = $this->lockKey($receipt);

        try {
            $result = Redis::set($key, (string) now()->getTimestamp(), 'EX', $ttlSeconds, 'NX');

            return $result === true || $result === 'OK';
        } catch (\Throwable) {
            return Cache::add($key, (string) now()->getTimestamp(), now()->addSeconds($ttlSeconds));
        }
    }

    private function cacheKey(string $receipt): string
    {
        return self::CACHE_PREFIX . trim($receipt);
    }

    private function lockKey(string $receipt): string
    {
        return self::LOCK_PREFIX . trim($receipt);
    }

    /**
     * @return array<string,mixed>
     */
    private function sanitizeCaptureMeta(array $meta): array
    {
        $capture = is_array($meta['capture'] ?? null) ? $meta['capture'] : [];
        $patterns = is_array($capture['url_patterns'] ?? null) ? $capture['url_patterns'] : [];
        $patterns = array_values(array_filter(array_map(static function ($pattern): string {
            return trim((string) $pattern);
        }, $patterns), static fn (string $pattern): bool => $pattern !== ''));

        if (count($patterns) > self::MAX_CAPTURE_PATTERNS) {
            $patterns = array_slice($patterns, 0, self::MAX_CAPTURE_PATTERNS);
        }

        $capture['url_patterns'] = $patterns;
        $meta['capture'] = $capture;

        return $meta;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function normalizeForStableHash(mixed $value): mixed
    {
        if (is_array($value)) {
            $isList = array_keys($value) === range(0, count($value) - 1);
            $normalized = [];
            foreach ($value as $key => $item) {
                if (! $isList && $this->isVolatileKey((string) $key)) {
                    continue;
                }
                $normalized[$key] = $this->normalizeForStableHash($item);
            }
            if (! $isList) {
                ksort($normalized);
            }

            return $normalized;
        }

        if (is_object($value)) {
            return $this->normalizeForStableHash((array) $value);
        }

        return $value;
    }

    private function isVolatileKey(string $key): bool
    {
        $normalized = strtolower(trim($key));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, [
            'run_id',
            'timestamp',
            'created_at',
            'updated_at',
            'nonce',
            'session_id',
            'request_id',
            'trace_id',
            'ui_state',
            'transient',
        ], true);
    }
}
