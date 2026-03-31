<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\FetchConfigData;
use Gametech\Lotto\Services\AutoResultV2\Executors\FetchExecutor;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\EmbeddedJsonFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\HtmlHttpFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\JsonHttpFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\ManualInputFetchDriver;
use Gametech\Lotto\Services\AutoResultV2\FetchDrivers\RenderedBrowserFetchDriver;
use PHPUnit\Framework\TestCase;

class FetchExecutorRoutingTest extends TestCase
{
    public function test_rendered_browser_with_http_only_capability_routes_to_http_driver(): void
    {
        $json = new class extends JsonHttpFetchDriver {
            public function fetch(array $fetchConfig): array
            {
                return ['ok' => true, 'status' => 'SUCCESS', 'response_body' => '{"ok":true}', 'response_content_type' => 'application/json', 'duration_ms' => 1];
            }
        };
        $html = new class extends HtmlHttpFetchDriver {
            public function fetch(array $fetchConfig): array
            {
                return ['ok' => true, 'status' => 'SUCCESS', 'response_body' => '<html></html>', 'response_content_type' => 'text/html', 'duration_ms' => 1];
            }
        };
        $rendered = new class extends RenderedBrowserFetchDriver {
            public function fetch(array $fetchConfig): array
            {
                return ['ok' => false, 'status' => 'FETCH_DEFERRED', 'error_code' => 'SHOULD_NOT_BE_CALLED'];
            }
        };

        $executor = new FetchExecutor(
            $json,
            $html,
            $rendered,
            new EmbeddedJsonFetchDriver(),
            new ManualInputFetchDriver()
        );

        $config = FetchConfigData::fromArray([
            'fetch_strategy' => 'RENDERED_BROWSER',
            'endpoint_url' => 'https://example.com',
            'http_method' => 'GET',
            'meta' => [
                'runtime' => [
                    'fetch_capability' => 'http_only',
                    'http_fallback_strategy' => 'JSON_HTTP',
                ],
            ],
        ]);

        $result = $executor->execute($config, ['source_id' => 1, 'draw_id' => 1]);

        $this->assertTrue((bool) $result['ok']);
        $this->assertSame('HTTP_ONLY_ROUTE', $result['selected_driver']);
    }

    public function test_json_http_replaces_expected_draw_date_placeholder_in_endpoint_url(): void
    {
        $json = new class extends JsonHttpFetchDriver {
            public array $lastFetchConfig = [];

            public function fetch(array $fetchConfig): array
            {
                $this->lastFetchConfig = $fetchConfig;

                return ['ok' => true, 'status' => 'SUCCESS', 'response_body' => '[]', 'response_content_type' => 'application/json', 'duration_ms' => 1];
            }
        };

        $executor = new FetchExecutor(
            $json,
            new HtmlHttpFetchDriver(),
            new RenderedBrowserFetchDriver(),
            new EmbeddedJsonFetchDriver(),
            new ManualInputFetchDriver()
        );

        $config = FetchConfigData::fromArray([
            'fetch_strategy' => 'JSON_HTTP',
            'endpoint_url' => 'https://example.com/results/{{expected_draw_date}}/1',
            'http_method' => 'GET',
        ]);

        $executor->execute($config, ['expected_draw_date' => '2026-03-28']);

        $this->assertSame(
            'https://example.com/results/2026-03-28/1',
            (string) ($json->lastFetchConfig['request']['url'] ?? '')
        );
    }

    public function test_json_http_replaces_lookup_date_placeholder_in_query_templates(): void
    {
        $json = new class extends JsonHttpFetchDriver {
            public array $lastFetchConfig = [];

            public function fetch(array $fetchConfig): array
            {
                $this->lastFetchConfig = $fetchConfig;

                return ['ok' => true, 'status' => 'SUCCESS', 'response_body' => '[]', 'response_content_type' => 'application/json', 'duration_ms' => 1];
            }
        };

        $executor = new FetchExecutor(
            $json,
            new HtmlHttpFetchDriver(),
            new RenderedBrowserFetchDriver(),
            new EmbeddedJsonFetchDriver(),
            new ManualInputFetchDriver()
        );

        $config = FetchConfigData::fromArray([
            'fetch_strategy' => 'JSON_HTTP',
            'endpoint_url' => 'https://example.com/results',
            'http_method' => 'GET',
            'query' => [
                'date' => '{{lookup_date}}',
                'static' => 'ok',
            ],
        ]);

        $executor->execute($config, ['expected_draw_date' => '2026-03-30']);

        $this->assertSame('2026-03-30', (string) ($json->lastFetchConfig['request']['query']['date'] ?? ''));
        $this->assertSame('ok', (string) ($json->lastFetchConfig['request']['query']['static'] ?? ''));
    }
}
