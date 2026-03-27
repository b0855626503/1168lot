<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Models\LottoResultSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ResultFetcher
{
    /**
     * @param array{url:string,method:string,headers:array<string,string>,body:mixed,query:array<string,mixed>} $request
     * @return array{status:string,http_status:int|null,response_body:string|null,duration_ms:int,error_message:string|null}
     */
    public function fetch(LottoResultSource $source, array $request): array
    {
        $start = microtime(true);

        try {
            $response = Http::timeout(max(1, (int) $source->timeout_seconds))
                ->withHeaders($request['headers'] ?? [])
                ->send(
                    strtoupper((string) ($request['method'] ?? 'GET')),
                    (string) ($request['url'] ?? ''),
                    $this->buildHttpOptions($request)
                );

            $duration = (int) round((microtime(true) - $start) * 1000);
            $statusCode = $response->status();

            if ($statusCode < 200 || $statusCode >= 300) {
                return [
                    'status' => 'HTTP_ERROR',
                    'http_status' => $statusCode,
                    'response_body' => $response->body(),
                    'duration_ms' => $duration,
                    'error_message' => 'HTTP status not successful',
                ];
            }

            return [
                'status' => 'SUCCESS',
                'http_status' => $statusCode,
                'response_body' => $response->body(),
                'duration_ms' => $duration,
                'error_message' => null,
            ];
        } catch (ConnectionException $e) {
            return [
                'status' => 'HTTP_ERROR',
                'http_status' => null,
                'response_body' => null,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'error_message' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'HTTP_ERROR',
                'http_status' => null,
                'response_body' => null,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array{body:mixed,method?:string} $request
     * @return array<string,mixed>
     */
    private function buildHttpOptions(array $request): array
    {
        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        if (in_array($method, ['GET', 'HEAD'], true)) {
            return [];
        }

        $body = $request['body'] ?? null;
        if ($body === null) {
            return [];
        }

        if (is_array($body)) {
            if ($body === []) {
                return [];
            }

            return ['json' => $body];
        }

        return ['body' => (string) $body];
    }
}
