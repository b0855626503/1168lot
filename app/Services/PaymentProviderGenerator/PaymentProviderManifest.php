<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGenerator;

final class PaymentProviderManifest
{
    public static function make(string $provider, string $referenceProvider = 'smkpay'): array
    {
        return [
            'provider' => $provider,
            'reference_provider' => $referenceProvider,
            'created_at' => now()->toIso8601String(),
            'mode' => 'dry_run',
            'files' => [
                'created' => [],
                'modified' => [],
                'suggested_patches' => [],
            ],
            'env_keys' => [],
            'config_keys' => [],
            'routes' => [],
            'webhooks' => [],
            'status_mapping' => config('payment_provider_generator.provider_contract.status_mapping', []),
            'terminal_statuses' => config('payment_provider_generator.provider_contract.terminal_statuses', []),
            'validation' => [
                'passed' => false,
                'errors' => [],
                'warnings' => [],
            ],
        ];
    }
}
