<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGenerator;

use Illuminate\Support\Facades\File;

final class PaymentProviderValidator
{
    public function validateGenerated(array $manifest): array
    {
        $errors = [];
        $warnings = [];

        $provider = (string) ($manifest['provider'] ?? '');
        try {
            PaymentProviderName::from($provider);
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }

        foreach (($manifest['files']['created'] ?? []) as $file) {
            $path = (string) $file;

            if ($this->isBlockedPath($path)) {
                $errors[] = 'Blocked path: ' . $path;
            }

            if (!$this->isWhitelistedWritePath($path)) {
                $errors[] = 'Path is not whitelisted: ' . $path;
            }

            if (str_ends_with($path, '.php') && File::exists(base_path($path))) {
                $content = File::get(base_path($path));
                if (preg_match('/(api[_-]?key|secret|token)\s*=[\s\'\"][^\'\"]+/i', $content)) {
                    $errors[] = 'Possible hardcoded secret in: ' . $path;
                }
            }
        }

        $requiredStatuses = [
            'pending',
            'processing',
            'pending_review',
            'in_review',
            'success',
            'expired',
            'failed',
            'rejected',
            'refunded',
        ];

        $statusMapping = (array) ($manifest['status_mapping'] ?? []);
        foreach ($requiredStatuses as $status) {
            if (!array_key_exists($status, $statusMapping)) {
                $errors[] = 'Missing status mapping: ' . $status;
            }
        }

        return [
            'passed' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function isBlockedPath(string $path): bool
    {
        foreach ((array) config('payment_provider_generator.blocked_paths', []) as $blocked) {
            if ($path === $blocked || str_starts_with($path, rtrim($blocked, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    private function isWhitelistedWritePath(string $path): bool
    {
        foreach ((array) config('payment_provider_generator.whitelist_write_paths', []) as $allowed) {
            if ($path === $allowed || str_starts_with($path, rtrim($allowed, '/') . '/')) {
                return true;
            }
        }

        return false;
    }
}
