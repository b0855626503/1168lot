<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGeneratorV3;

use Illuminate\Support\Facades\File;

final class PaymentProviderInspector
{
    public function inspect(string $provider): array
    {
        $name = PaymentProviderName::from($provider);

        $paths = config('payment_provider_generator.paths');

        $targets = [
            'library' => $paths['payment_library'] . '/' . $name->studly . '.php',
            'controller' => $paths['payment_controller'] . '/' . $name->studly . 'Controller.php',
            'withdraw_job' => $paths['auto_jobs'] . '/PaymentOut' . $name->studly . '.php',
            'balance_job' => $paths['auto_jobs'] . '/UpdateBalance' . $name->studly . '.php',
        ];

        $files = [];
        foreach ($targets as $key => $relative) {
            $absolute = base_path($relative);
            $files[$key] = [
                'path' => $relative,
                'exists' => File::exists($absolute),
                'sha1' => File::exists($absolute) ? sha1_file($absolute) : null,
                'size' => File::exists($absolute) ? File::size($absolute) : null,
            ];
        }

        return [
            'provider' => $name->key,
            'studly' => $name->studly,
            'files' => $files,
            'flow_summary' => [
                'deposit' => 'controller deposit -> library createPayin -> check_case pending -> callback -> bank_payment',
                'withdraw' => 'job PaymentOut -> library payout -> withdraw callback -> close or rollback',
                'status_terminal_rule' => 'completed/failed/rejected/refunded cannot move backward',
            ],
        ];
    }
}
