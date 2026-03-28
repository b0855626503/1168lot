<?php

namespace Gametech\Lotto\Services\AutoResultV2\Browser;

class BrowserRuntimeArtifactService
{
    /**
     * @param array<string,mixed> $context
     * @param array<string,mixed> $result
     * @return array<string,mixed>
     */
    public function persist(array $context, array $result, string $receiptKey): array
    {
        $sourceId = (int) ($context['source_id'] ?? 0);
        $drawId = (int) ($context['draw_id'] ?? 0);
        $datePath = now()->format('Y/m/d');

        $base = rtrim((string) config('lotto_auto_result.browser_runtime.artifacts.base_dir', storage_path('app/lotto/browser-runtime')), '/');
        $dir = $base . '/' . $datePath . '/source_' . $sourceId . '/draw_' . $drawId . '/run_' . $receiptKey;
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $summary = [
            'path' => $dir,
            'files' => [],
        ];

        $maxBytes = max(1024, (int) config('lotto_auto_result.browser_runtime.artifacts.max_bytes_per_run', 5 * 1024 * 1024));
        $written = 0;
        $previewBytes = max(256, (int) config('lotto_auto_result.browser_runtime.artifacts.preview_bytes', 16 * 1024));

        $files = [
            'meta.json' => $this->safeEncode([
                'status' => $result['status'] ?? null,
                'error_code' => $result['error_code'] ?? null,
                'error_message' => $result['error_message'] ?? null,
                'selected_endpoint' => $result['selected_endpoint'] ?? null,
                'meta' => $result['meta'] ?? [],
            ]),
            'network_summary.json' => $this->safeEncode($result['network_summary'] ?? []),
            'capture_0.json' => $this->safeEncode([
                'url' => $result['selected_endpoint'] ?? null,
                'content_type' => $result['response_content_type'] ?? null,
                'preview' => mb_substr((string) ($result['response_body'] ?? ''), 0, $previewBytes),
            ]),
        ];

        foreach ($files as $name => $content) {
            if ($content === '') {
                continue;
            }

            $bytes = strlen($content);
            if (($written + $bytes) > $maxBytes) {
                break;
            }

            $path = $dir . '/' . $name;
            @file_put_contents($path, $content);
            $written += $bytes;
            $summary['files'][] = $name;
        }

        return $summary;
    }

    /**
     * @param array<string,mixed>|array<int,mixed> $payload
     */
    private function safeEncode(array $payload): string
    {
        $json = json_encode($this->redact($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return $json === false ? '' : $json;
    }

    /**
     * @param array<string,mixed>|array<int,mixed> $payload
     * @return array<string,mixed>|array<int,mixed>
     */
    private function redact(array $payload): array
    {
        $sensitiveKeys = ['authorization', 'cookie', 'set-cookie', 'token', 'auth', 'apikey', 'api_key', 'signature', 'sign'];
        $redacted = [];
        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $lower = strtolower($key);
                foreach ($sensitiveKeys as $needle) {
                    if (str_contains($lower, $needle)) {
                        $redacted[$key] = '***REDACTED***';
                        continue 2;
                    }
                }
            }

            if (is_array($value)) {
                $redacted[$key] = $this->redact($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }
}

