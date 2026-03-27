<?php

namespace Gametech\Lotto\Services\AutoResultV2\FetchDrivers;

class EmbeddedJsonFetchDriver
{
    public function __construct(
        private ?HtmlHttpFetchDriver $htmlHttpFetchDriver = null
    ) {
        $this->htmlHttpFetchDriver = $this->htmlHttpFetchDriver ?: new HtmlHttpFetchDriver();
    }

    /**
     * @param array<string,mixed> $fetchConfig
     * @return array<string,mixed>
     */
    public function fetch(array $fetchConfig): array
    {
        $result = $this->htmlHttpFetchDriver->fetch($fetchConfig);
        if (! (bool) ($result['ok'] ?? false)) {
            return $result;
        }

        $body = (string) ($result['response_body'] ?? '');
        $rule = is_array($fetchConfig['embedded_json'] ?? null) ? $fetchConfig['embedded_json'] : [];
        $pattern = (string) ($rule['regex'] ?? '/\{.*\}/s');

        $matches = [];
        if (@preg_match($pattern, $body, $matches) !== 1) {
            return [
                'ok' => false,
                'status' => 'FETCH_FAILED',
                'http_status' => $result['http_status'] ?? null,
                'response_body' => null,
                'response_content_type' => 'application/json',
                'duration_ms' => (int) ($result['duration_ms'] ?? 0),
                'error_message' => 'embedded json not found',
            ];
        }

        return [
            'ok' => true,
            'status' => 'SUCCESS',
            'http_status' => $result['http_status'] ?? null,
            'response_body' => (string) ($matches[0] ?? ''),
            'response_content_type' => 'application/json',
            'duration_ms' => (int) ($result['duration_ms'] ?? 0),
            'error_message' => null,
        ];
    }
}
