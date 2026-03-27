<?php

namespace Gametech\Lotto\Services\AutoResultV2\FetchDrivers;

class ManualInputFetchDriver
{
    /**
     * @param array<string,mixed> $fetchConfig
     * @return array<string,mixed>
     */
    public function fetch(array $fetchConfig): array
    {
        $payload = $fetchConfig['manual_payload'] ?? null;
        if ($payload === null) {
            return [
                'ok' => false,
                'status' => 'FETCH_FAILED',
                'http_status' => null,
                'response_body' => null,
                'response_content_type' => null,
                'duration_ms' => 0,
                'error_message' => 'manual payload missing',
            ];
        }

        return [
            'ok' => true,
            'status' => 'SUCCESS',
            'http_status' => null,
            'response_body' => is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE),
            'response_content_type' => is_string($payload) ? 'text/plain' : 'application/json',
            'duration_ms' => 0,
            'error_message' => null,
        ];
    }
}
