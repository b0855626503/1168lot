<?php

namespace Gametech\Lotto\Services\InternalResultSources;

use Symfony\Component\Process\Process;

class ExphuayPythonWorkerClient
{
    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $headers
     * @return array<string,mixed>
     */
    public function fetch(string $url, array $query, array $headers, int $timeoutSeconds): array
    {
        $start = microtime(true);

        $pythonBinary = (string) config('lotto_auto_result.internal_result_sources.exphuay.python_worker_binary', 'python3');
        $workerScript = (string) config(
            'lotto_auto_result.internal_result_sources.exphuay.python_worker_script',
            base_path('scripts/lotto/exphuay_curl_cffi_worker.py')
        );
        if (! is_file($workerScript)) {
            return $this->errorResult('PYTHON_WORKER_UNAVAILABLE', 'python worker script not found', $start);
        }

        $payload = json_encode([
            'url' => $url,
            'method' => 'GET',
            'query' => $query,
            'headers' => $headers,
            'timeout_seconds' => max(1, $timeoutSeconds),
            'impersonate' => (string) config('lotto_auto_result.internal_result_sources.exphuay.python_worker_impersonate', 'chrome124'),
            'warmup' => [
                'enabled' => (bool) config('lotto_auto_result.internal_result_sources.exphuay.python_worker_warmup_enabled', true),
                'url' => (string) config('lotto_auto_result.internal_result_sources.exphuay.python_worker_warmup_url', 'https://exphuay.com/'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            return $this->errorResult('PYTHON_WORKER_IO_ERROR', 'cannot encode python worker payload', $start);
        }

        try {
            $process = new Process([$pythonBinary, $workerScript], base_path(), null, $payload, max(1, $timeoutSeconds + 5));
            $process->run();

            $stderr = trim((string) $process->getErrorOutput());
            $stdout = trim((string) $process->getOutput());

            if (! $process->isSuccessful()) {
                return $this->errorResult(
                    $process->getExitCode() === 127 ? 'PYTHON_WORKER_UNAVAILABLE' : 'PYTHON_WORKER_FAILED',
                    $stderr !== '' ? mb_substr($stderr, 0, 2000) : 'python worker failed',
                    $start
                );
            }

            $decoded = json_decode($stdout, true);
            if (! is_array($decoded)) {
                return $this->errorResult('PYTHON_WORKER_IO_ERROR', 'invalid python worker output', $start);
            }

            return [
                'ok' => (bool) ($decoded['ok'] ?? false),
                'http_status' => isset($decoded['http_status']) ? (int) $decoded['http_status'] : null,
                'response_body' => isset($decoded['response_body']) ? (string) $decoded['response_body'] : null,
                'duration_ms' => (int) ($decoded['duration_ms'] ?? round((microtime(true) - $start) * 1000)),
                'error_code' => $decoded['error_code'] ?? null,
                'error_message' => isset($decoded['error_message']) ? (string) $decoded['error_message'] : null,
                'response_content_type' => isset($decoded['response_content_type']) ? (string) $decoded['response_content_type'] : null,
            ];
        } catch (\Throwable $e) {
            $message = strtolower($e->getMessage());

            return $this->errorResult(
                str_contains($message, 'timed out') ? 'PYTHON_WORKER_TIMEOUT' : 'PYTHON_WORKER_IO_ERROR',
                $e->getMessage(),
                $start
            );
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function errorResult(string $errorCode, string $errorMessage, float $start): array
    {
        return [
            'ok' => false,
            'http_status' => null,
            'response_body' => null,
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'response_content_type' => null,
        ];
    }
}
