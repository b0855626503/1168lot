<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGenerator;

final class PaymentProviderPlanner
{
    public function plan(string $newProvider, string $referenceProvider = 'smkpay'): array
    {
        $new = PaymentProviderName::from($newProvider);
        $ref = PaymentProviderName::from($referenceProvider);

        return [
            'new_provider' => $new->key,
            'reference_provider' => $ref->key,
            'files_to_create' => [
                'packages/Gametech/Payment/src/Libraries/' . $new->studly . '.php',
                'packages/Gametech/Payment/src/Http/Controllers/' . $new->studly . 'Controller.php',
                'packages/Gametech/Auto/src/Jobs/PaymentOut' . $new->studly . '.php',
                'packages/Gametech/Auto/src/Jobs/UpdateBalance' . $new->studly . '.php',
                'config/' . $new->key . '.php',
            ],
            'files_to_modify_suggested' => [
                'route file that currently registers smkpay endpoints',
                'config/logging.php if dedicated channels are required',
                'any payment provider registry if exists',
            ],
            'risks' => [
                'API signature may not match smkpay JCS/HMAC pattern',
                'callback payload may use different transaction id fields',
                'withdraw flow may not have same customer/account prepare requirement',
                'route names must not conflict with api.smkpay.*',
            ],
            'implementation_steps' => [
                'Inspect smkpay files',
                'Analyze new API document',
                'Map deposit payload and response',
                'Map withdraw payload and response',
                'Map callback statuses',
                'Generate dry-run files',
                'Validate no hardcoded secrets',
                'Write files only when explicitly requested',
            ],
        ];
    }
}
