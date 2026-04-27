<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGeneratorV3;

final class PaymentProviderPlanner
{
    public function plan(PaymentProviderName $provider, string $reference, array $inspect, array $analysis, array $decisions): array
    {
        $capabilities = (array) $analysis['capabilities'];

        $files = [
            'library' => 'packages/Gametech/Payment/src/Libraries/' . $provider->studly . '.php',
            'controller' => 'packages/Gametech/Payment/src/Http/Controllers/' . $provider->studly . 'Controller.php',
            'config' => 'config/' . $provider->key . '.php',
        ];

        if (!empty($capabilities['withdraw']) || (($decisions['missing_withdraw'] ?? null) === 'stub')) {
            $files['withdraw_job'] = 'packages/Gametech/Auto/src/Jobs/PaymentOut' . $provider->studly . '.php';
        }

        if (!empty($capabilities['balance']) || (($decisions['missing_balance'] ?? null) === 'stub')) {
            $files['balance_job'] = 'packages/Gametech/Auto/src/Jobs/UpdateBalance' . $provider->studly . '.php';
        }

        return [
            'provider' => $provider->key,
            'reference' => $reference,
            'capabilities' => $capabilities,
            'decisions' => $decisions,
            'files_to_create' => array_values($files),
            'files_map' => $files,
            'suggested_manual_patches' => [
                'routes' => [
                    'Add api.' . $provider->key . '.index',
                    'Add api.' . $provider->key . '.deposit',
                    'Add callback routes if provider supports callback or decision uses polling/manual flow',
                ],
                'logging' => [
                    $provider->key . '_api',
                    $provider->key . '_deposit_create',
                    $provider->key . '_deposit_callback',
                    $provider->key . '_withdraw_callback',
                ],
                'env' => [
                    $provider->upperSnake . '_API_URL',
                    $provider->upperSnake . '_API_KEY',
                    $provider->upperSnake . '_SECRET_KEY',
                ],
            ],
            'risks' => [
                'Heuristic doc parser cannot replace final human review for real money flow.',
                'If provider signature differs from generated strategy, library must be adjusted before production.',
                'Webhook idempotency and replay protection must be tested with real provider payload.',
            ],
        ];
    }
}
