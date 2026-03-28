<?php

namespace Gametech\Lotto\Jobs;

use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserFetchDispatchService;
use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserRuntimeArtifactService;
use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserRuntimeBudgetGuard;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\RenderedBrowserFetchDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteRenderedBrowserFetchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 1;

    public function __construct(
        public array $request,
        public array $context = [],
        public string $receipt = ''
    ) {
        $hardTimeout = max(10, (int) config('lotto_auto_result.browser_worker.hard_timeout_seconds', 60));
        $requestedTimeoutMs = (int) (data_get($context, 'meta.browser_worker.timeout_ms', 0));
        if ($requestedTimeoutMs > 0) {
            $requestedSeconds = (int) ceil($requestedTimeoutMs / 1000);
            $hardTimeout = min(180, max($hardTimeout, $requestedSeconds + 5));
        }

        $this->timeout = $hardTimeout;
    }

    public function handle(RenderedBrowserFetchDriver $driver): void
    {
        $receipt = trim((string) $this->receipt);
        $dispatchService = new BrowserFetchDispatchService();
        $budgetGuard = new BrowserRuntimeBudgetGuard();
        $artifactService = new BrowserRuntimeArtifactService();
        $domain = parse_url((string) ($this->request['url'] ?? ''), PHP_URL_HOST);
        $budget = $budgetGuard->acquire((int) ($this->context['source_id'] ?? 0), is_string($domain) ? $domain : '', (int) $this->timeout + 30);

        if (! (bool) ($budget['ok'] ?? false)) {
            $failedPayload = [
                'status' => 'failed',
                'response_body' => null,
                'selected_endpoint' => null,
                'error_code' => (string) ($budget['error_code'] ?? 'BROWSER_BUDGET_GLOBAL_EXCEEDED'),
                'error_message' => 'browser runtime budget exceeded',
                'meta' => [
                    'duration_ms' => 0,
                    'content_type' => null,
                    'captured_count' => 0,
                    'selected_driver' => 'BROWSER_RUNTIME',
                ],
                'receipt' => $receipt,
            ];

            if ($receipt !== '') {
                $dispatchService->putCachedPayload($receipt, $failedPayload);
                $dispatchService->releaseLock($receipt);
            }

            return;
        }

        try {
            $result = $driver->performRuntimeFetch($this->request, $this->context);
            $artifact = $artifactService->persist($this->context, $result, $receipt);
            $result['meta'] = is_array($result['meta'] ?? null) ? $result['meta'] : [];
            $result['meta']['artifact_refs'] = $artifact;

            if ($receipt !== '') {
                $dispatchService->putCachedPayload($receipt, array_merge($result, ['receipt' => $receipt]));
            }

            Log::info('LOTTO_AUTO_RESULT_BROWSER_FETCH_DONE', [
                'receipt' => $receipt,
                'status' => $result['status'] ?? null,
                'selected_endpoint' => $result['selected_endpoint'] ?? null,
                'error_code' => $result['error_code'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $failedPayload = [
                'status' => 'failed',
                'response_body' => null,
                'selected_endpoint' => null,
                'error_code' => 'BROWSER_RUNTIME_EXCEPTION',
                'error_message' => $e->getMessage(),
                'meta' => [
                    'duration_ms' => 0,
                    'content_type' => null,
                    'captured_count' => 0,
                ],
                'receipt' => $receipt,
            ];

            if ($receipt !== '') {
                $dispatchService->putCachedPayload($receipt, $failedPayload);
            }

            Log::warning('LOTTO_AUTO_RESULT_BROWSER_FETCH_FAILED', [
                'receipt' => $receipt,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $budgetGuard->release((array) ($budget['keys'] ?? []));
            if ($receipt !== '') {
                $dispatchService->releaseLock($receipt);
            }
        }
    }
}
