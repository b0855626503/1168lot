<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGenerator;

use Illuminate\Support\Facades\File;

final class PaymentProviderInspector
{
    public function inspect(string $provider): array
    {
        $name = PaymentProviderName::from($provider);

        $paths = config('payment_provider_generator.paths');

        $files = [
            'library' => base_path($paths['payment_library'] . '/' . $name->studly . '.php'),
            'controller' => base_path($paths['payment_controller'] . '/' . $name->studly . 'Controller.php'),
            'withdraw_job' => base_path($paths['auto_jobs'] . '/PaymentOut' . $name->studly . '.php'),
            'balance_job' => base_path($paths['auto_jobs'] . '/UpdateBalance' . $name->studly . '.php'),
        ];

        $existing = [];
        foreach ($files as $type => $path) {
            if (File::exists($path)) {
                $existing[$type] = [
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path),
                    'size' => File::size($path),
                    'sha1' => sha1_file($path),
                ];
            }
        }

        return [
            'provider' => $name->key,
            'studly' => $name->studly,
            'files' => $existing,
            'missing' => array_values(array_diff(array_keys($files), array_keys($existing))),
            'reference_summary' => [
                'uses_provider_accounts' => true,
                'deposit_flow' => 'customer/account prepare -> create payin -> check_case pending -> callback -> bank_payments',
                'withdraw_flow' => 'withdraw job -> provider payout -> callback -> close/rollback',
                'status_rule' => 'terminal statuses cannot be changed; expired can revive only to completed',
            ],
        ];
    }
}
