<?php

namespace Gametech\Lotto\Services\AutoResultV2\Executors;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\FetchConfigData;
use Gametech\Lotto\Services\AutoResultV2\Browser\BrowserRuntimePolicyService;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\EmbeddedJsonFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\HtmlHttpFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\JsonHttpFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\ManualInputFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\RenderedBrowserFetchDriver;

class FetchExecutor
{
    private BrowserRuntimePolicyService $policyService;

    public function __construct(
        private ?JsonHttpFetchDriver $jsonHttp = null,
        private ?HtmlHttpFetchDriver $htmlHttp = null,
        private ?RenderedBrowserFetchDriver $renderedBrowser = null,
        private ?EmbeddedJsonFetchDriver $embeddedJson = null,
        private ?ManualInputFetchDriver $manualInput = null
    ) {
        $this->jsonHttp = $this->jsonHttp ?: new JsonHttpFetchDriver();
        $this->htmlHttp = $this->htmlHttp ?: new HtmlHttpFetchDriver();
        $this->renderedBrowser = $this->renderedBrowser ?: new RenderedBrowserFetchDriver();
        $this->embeddedJson = $this->embeddedJson ?: new EmbeddedJsonFetchDriver();
        $this->manualInput = $this->manualInput ?: new ManualInputFetchDriver();
        $this->policyService = new BrowserRuntimePolicyService();
    }

    /**
     * @param array<string,mixed> $runtimeContext
     * @return array<string,mixed>
     */
    public function execute(FetchConfigData $config, array $runtimeContext = []): array
    {
        $fetchConfig = [
            'request' => [
                'url' => $config->endpointUrl(),
                'method' => $config->httpMethod(),
                'headers' => $config->headers(),
                'query' => $config->query(),
                'body' => $config->body(),
            ],
            'timeout_seconds' => $config->timeoutSeconds(),
            'manual_payload' => $config->manualInput(),
            'meta' => $config->meta(),
            'context' => $runtimeContext,
        ];

        $result = match ($config->strategy()) {
            FetchConfigData::STRATEGY_JSON_HTTP => $this->jsonHttp->fetch($fetchConfig),
            FetchConfigData::STRATEGY_HTML_HTTP => $this->htmlHttp->fetch($fetchConfig),
            FetchConfigData::STRATEGY_RENDERED_BROWSER => $this->routeRenderedBrowser($config, $fetchConfig),
            FetchConfigData::STRATEGY_EMBEDDED_JSON => $this->embeddedJson->fetch($fetchConfig),
            FetchConfigData::STRATEGY_MANUAL_INPUT => $this->manualInput->fetch($fetchConfig),
            default => [
                'ok' => false,
                'status' => 'FETCH_FAILED',
                'error_message' => 'unsupported fetch strategy',
                'http_status' => null,
                'response_body' => null,
                'response_content_type' => null,
                'duration_ms' => 0,
            ],
        };

        if (! isset($result['selected_driver'])) {
            $result['selected_driver'] = match ($config->strategy()) {
                FetchConfigData::STRATEGY_JSON_HTTP => 'JSON_HTTP',
                FetchConfigData::STRATEGY_HTML_HTTP => 'HTML_HTTP',
                FetchConfigData::STRATEGY_EMBEDDED_JSON => 'EMBEDDED_JSON',
                FetchConfigData::STRATEGY_MANUAL_INPUT => 'MANUAL_INPUT',
                default => 'UNKNOWN',
            };
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $fetchConfig
     * @return array<string,mixed>
     */
    private function routeRenderedBrowser(FetchConfigData $config, array $fetchConfig): array
    {
        $meta = $config->meta();
        $runtime = is_array($meta['runtime'] ?? null) ? $meta['runtime'] : [];
        $capability = $this->policyService->normalizeCapability($runtime['fetch_capability'] ?? null);

        if ($capability === BrowserRuntimePolicyService::CAPABILITY_HTTP_ONLY) {
            $fallbackStrategy = strtoupper((string) ($runtime['http_fallback_strategy'] ?? FetchConfigData::STRATEGY_JSON_HTTP));
            $result = $fallbackStrategy === FetchConfigData::STRATEGY_HTML_HTTP
                ? $this->htmlHttp->fetch($fetchConfig)
                : $this->jsonHttp->fetch($fetchConfig);
            $result['selected_driver'] = 'HTTP_ONLY_ROUTE';
            $result['meta'] = is_array($result['meta'] ?? null) ? $result['meta'] : [];
            $result['meta']['fetch_capability'] = $capability;
            $result['meta']['payload_origin'] = 'http_only';

            return $result;
        }

        return $this->renderedBrowser->fetch($fetchConfig);
    }
}
