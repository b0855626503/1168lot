<?php

namespace Gametech\Lotto\Services\InternalResultSources;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class HttpResultFetcher
{
    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function get(string $url, array $query = [], int $timeoutSeconds = 10, array $headers = []): array
    {
        $start = microtime(true);

        try {
            $response = Http::timeout(max(1, $timeoutSeconds))
                ->withHeaders($headers)
                ->get($url, $query);

            return [
                'ok' => $response->successful(),
                'http_status' => $response->status(),
                'response_body' => (string) $response->body(),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'error_code' => $response->successful() ? null : ('HTTP_STATUS_' . (string) $response->status()),
                'error_message' => $response->successful() ? null : 'HTTP status not successful',
            ];
        } catch (ConnectionException $exception) {
            return [
                'ok' => false,
                'http_status' => null,
                'response_body' => null,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'error_code' => 'NETWORK_ERROR',
                'error_message' => $exception->getMessage(),
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'http_status' => null,
                'response_body' => null,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'error_code' => 'FETCH_EXCEPTION',
                'error_message' => $exception->getMessage(),
            ];
        }
    }
}

