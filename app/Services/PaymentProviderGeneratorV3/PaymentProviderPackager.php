<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGeneratorV3;

use ZipArchive;

final class PaymentProviderPackager
{
    public function package(PaymentProviderName $provider, array $manifest): array
    {
        $baseDir = storage_path('app/mcp/payment-providers/' . $provider->key);
        $zipPath = $baseDir . '/' . $provider->key . '-payment-provider.zip';

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (['analysis.json', 'plan.json', 'manifest.json', 'validation.json'] as $file) {
            $path = $baseDir . '/' . $file;
            if (is_file($path)) {
                $zip->addFile($path, $file);
            }
        }

        $dryRunDir = $baseDir . '/dry-run';
        if (is_dir($dryRunDir)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dryRunDir)) as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), 'dry-run/' . $file->getFilename());
                }
            }
        }

        foreach ((array) data_get($manifest, 'files.created', []) as $relative) {
            $absolute = base_path($relative);
            if (is_file($absolute)) {
                $zip->addFile($absolute, $relative);
            }
        }

        $zip->close();

        return [
            'provider' => $provider->key,
            'zip_path' => $zipPath,
        ];
    }
}
