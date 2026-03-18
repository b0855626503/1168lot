<?php

namespace App\Services\Dashboard;

class DashboardWebCodeResolver
{
    public function resolve(?string $preferred = null): string
    {
        if ($this->hasValue($preferred)) {
            return $this->normalize((string) $preferred);
        }

        $appName = config('app.name');
        if ($this->hasValue($appName)) {
            return $this->normalize((string) $appName);
        }

        try {
            $config = core()->getConfigData();

            foreach (['dashboard_web_code', 'web_code', 'webcode'] as $field) {
                if ($this->hasValue($config?->{$field} ?? null)) {
                    return $this->normalize((string) $config->{$field});
                }
            }
        } catch (\Throwable $e) {
            // Fallback to app config when runtime config table is unavailable.
        }

        return 'default';
    }

    public function fromDeposit($deposit): string
    {
        return $this->resolve();
    }

    private function hasValue($value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    private function normalize(string $value): string
    {
        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : 'default';
    }
}
