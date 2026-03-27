<?php

namespace Gametech\Lotto\Services\AutoResultV2\Executors;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\FetchConfigData;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\EmbeddedJsonFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\HtmlHttpFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\JsonHttpFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\ManualInputFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\RenderedBrowserFetchDriver;

class FetchExecutor
{
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
    }

    /**
     * @return array<string,mixed>
     */
    public function execute(FetchConfigData $config): array
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
        ];

        return match ($config->strategy()) {
            FetchConfigData::STRATEGY_JSON_HTTP => $this->jsonHttp->fetch($fetchConfig),
            FetchConfigData::STRATEGY_HTML_HTTP => $this->htmlHttp->fetch($fetchConfig),
            FetchConfigData::STRATEGY_RENDERED_BROWSER => $this->renderedBrowser->fetch($fetchConfig),
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
    }
}
