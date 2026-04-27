<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGenerator;

use ZipArchive;

final class PaymentProviderPackager
{
    public function package(string $provider, array $files): array
    {
        $name = PaymentProviderName::from($provider);

        $packageDir = storage_path('app/mcp/payment-providers/packages');
        if (!is_dir($packageDir)) {
            mkdir($packageDir, 0755, true);
        }

        $zipPath = $packageDir . '/' . $name->key . '-payment-provider.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $relativePath) {
            $absolutePath = base_path($relativePath);
            if (is_file($absolutePath)) {
                $zip->addFile($absolutePath, $relativePath);
            }
        }

        $zip->close();

        return [
            'provider' => $name->key,
            'zip_path' => $zipPath,
            'files' => $files,
        ];
    }
}
