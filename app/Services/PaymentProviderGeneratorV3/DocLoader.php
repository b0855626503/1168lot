<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGeneratorV3;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class DocLoader
{
    public function load(?string $docUrl = null, ?string $docFile = null, ?string $docText = null): array
    {
        if ($docText !== null && trim($docText) !== '') {
            return [
                'source' => 'text',
                'content' => $docText,
            ];
        }

        if ($docFile !== null && trim($docFile) !== '') {
            $path = base_path($docFile);
            if (!File::exists($path)) {
                $path = storage_path('app/' . ltrim($docFile, '/'));
            }

            if (!File::exists($path)) {
                throw new RuntimeException('Document file not found: ' . $docFile);
            }

            return [
                'source' => 'file:' . $docFile,
                'content' => File::get($path),
            ];
        }

        if ($docUrl !== null && trim($docUrl) !== '') {
            return [
                'source' => 'url:' . $docUrl,
                'content' => $this->fetchUrl($docUrl),
            ];
        }

        return [
            'source' => 'empty',
            'content' => '',
        ];
    }

    private function fetchUrl(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => '1168lot-payment-provider-generator/3.0',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/json,text/plain,*/*',
            ],
        ]);

        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $status >= 400) {
            throw new RuntimeException('Failed to fetch API document: HTTP ' . $status . ' ' . $error);
        }

        return (string) $raw;
    }
}
