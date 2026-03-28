<?php

namespace Gametech\Lotto\Services\AutoResultV2\FetchDrivers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class JsonHttpFetchDriver
{
    /**
     * @param array<string,mixed> $fetchConfig
     * @return array<string,mixed>
     */
    public function fetch(array $fetchConfig): array
    {
        $request = is_array($fetchConfig['request'] ?? null) ? $fetchConfig['request'] : [];
        $url = (string) ($request['url'] ?? '');
        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        $headers = is_array($request['headers'] ?? null) ? $request['headers'] : [];
        $query = is_array($request['query'] ?? null) ? $request['query'] : [];
        $body = $request['body'] ?? null;
        $timeout = max(1, (int) ($fetchConfig['timeout_seconds'] ?? 10));

        $start = microtime(true);
        try {
            $response = Http::timeout($timeout)
                ->withHeaders($headers)
                ->send($method, $url, $this->buildOptions($method, $query, $body));

            return [
                'ok' => $response->successful(),
                'status' => $response->successful() ? 'SUCCESS' : 'FETCH_FAILED',
                'http_status' => $response->status(),
                'response_body' => (string) $response->body(),
                'response_content_type' => (string) $response->header('Content-Type', 'application/json'),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'error_message' => $response->successful() ? null : 'HTTP status not successful',
                'error_code' => $response->successful() ? null : ('HTTP_STATUS_' . (string) $response->status()),
            ];
        } catch (ConnectionException $e) {
            return [
                'ok' => false,
                'status' => 'FETCH_FAILED',
                'http_status' => null,
                'response_body' => null,
                'response_content_type' => null,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'error_message' => $e->getMessage(),
                'error_code' => 'NETWORK_ERROR',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 'FETCH_FAILED',
                'http_status' => null,
                'response_body' => null,
                'response_content_type' => null,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'error_message' => $e->getMessage(),
                'error_code' => 'FETCH_EXCEPTION',
            ];
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function buildOptions(string $method, array $query, mixed $body): array
    {
        $options = [];
        if ($query !== []) {
            $options['query'] = $query;
        }

        if (in_array($method, ['GET', 'HEAD'], true)) {
            return $options;
        }

        if (is_array($body) && $body !== []) {
            $options['json'] = $body;
        } elseif ($body !== null && $body !== '') {
            $options['body'] = (string) $body;
        }

        return $options;
    }
}
