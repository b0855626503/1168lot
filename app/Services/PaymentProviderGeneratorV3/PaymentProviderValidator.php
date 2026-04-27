<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGeneratorV3;

final class PaymentProviderValidator
{
    public function validate(array $manifest): array
    {
        $errors = [];
        $warnings = [];

        $provider = (string) ($manifest['provider'] ?? '');
        try {
            PaymentProviderName::from($provider);
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }

        $auth = (array) ($manifest['auth'] ?? []);
        if (!empty($auth['unknown'])) {
            $errors[] = 'Auth/signature strategy is unknown. Do not write production provider until confirmed.';
        }

        foreach ((array) data_get($manifest, 'files.created', []) as $path) {
            if ($this->isBlocked($path)) {
                $errors[] = 'Blocked path: ' . $path;
            }

            if (!$this->isWhitelisted($path)) {
                $errors[] = 'Not whitelisted path: ' . $path;
            }
        }

        $capabilities = (array) ($manifest['capabilities'] ?? []);
        $decisions = (array) ($manifest['decisions'] ?? []);

        if (empty($capabilities['withdraw']) && !isset($decisions['missing_withdraw'])) {
            $errors[] = 'Withdraw missing but no user decision recorded.';
        }

        if (empty($capabilities['callback']) && !isset($decisions['missing_callback'])) {
            $errors[] = 'Callback missing but no user decision recorded.';
        }

        if (empty($capabilities['balance']) && !isset($decisions['missing_balance'])) {
            $warnings[] = 'Balance API missing; balance sync may be stubbed.';
        }

        return [
            'passed' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function isBlocked(string $path): bool
    {
        foreach ((array) config('payment_provider_generator.blocked_paths', []) as $blocked) {
            if ($path === $blocked || str_starts_with($path, rtrim($blocked, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    private function isWhitelisted(string $path): bool
    {
        foreach ((array) config('payment_provider_generator.whitelist_write_paths', []) as $allowed) {
            if ($path === $allowed || str_starts_with($path, rtrim($allowed, '/') . '/')) {
                return true;
            }
        }

        return false;
    }
}
