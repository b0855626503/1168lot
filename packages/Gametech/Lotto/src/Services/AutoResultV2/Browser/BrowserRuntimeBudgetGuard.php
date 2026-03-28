<?php

namespace Gametech\Lotto\Services\AutoResultV2\Browser;

use Illuminate\Support\Facades\Cache;

class BrowserRuntimeBudgetGuard
{
    /**
     * @return array{ok:bool,error_code:?string,keys:array<int,string>}
     */
    public function acquire(int $sourceId, string $domain, int $ttlSeconds = 120): array
    {
        $domain = strtolower(trim($domain));
        $sourceId = max(0, $sourceId);
        $ttlSeconds = max(10, $ttlSeconds);

        $globalLimit = max(1, (int) config('lotto_auto_result.browser_runtime.concurrency.global', 5));
        $sourceLimit = max(1, (int) config('lotto_auto_result.browser_runtime.concurrency.per_source', 1));
        $domainLimit = max(1, (int) config('lotto_auto_result.browser_runtime.concurrency.per_domain', 2));

        $keys = [
            'lotto:auto-result:browser-runtime:budget:global',
            'lotto:auto-result:browser-runtime:budget:source:' . $sourceId,
            'lotto:auto-result:browser-runtime:budget:domain:' . ($domain !== '' ? $domain : 'unknown'),
        ];

        $limits = [$globalLimit, $sourceLimit, $domainLimit];
        $acquired = [];
        foreach ($keys as $idx => $key) {
            $count = Cache::increment($key);
            if ($count === 1) {
                Cache::put($key, 1, now()->addSeconds($ttlSeconds));
            } else {
                Cache::put($key, $count, now()->addSeconds($ttlSeconds));
            }

            if ((int) $count > $limits[$idx]) {
                $this->release($acquired);

                return [
                    'ok' => false,
                    'error_code' => match ($idx) {
                        0 => 'BROWSER_BUDGET_GLOBAL_EXCEEDED',
                        1 => 'BROWSER_BUDGET_SOURCE_EXCEEDED',
                        default => 'BROWSER_BUDGET_DOMAIN_EXCEEDED',
                    },
                    'keys' => [],
                ];
            }

            $acquired[] = $key;
        }

        return [
            'ok' => true,
            'error_code' => null,
            'keys' => $acquired,
        ];
    }

    /**
     * @param array<int,string> $keys
     */
    public function release(array $keys): void
    {
        foreach ($keys as $key) {
            $current = (int) Cache::get($key, 0);
            if ($current <= 1) {
                Cache::forget($key);
                continue;
            }

            Cache::put($key, $current - 1, now()->addSeconds(120));
        }
    }
}

