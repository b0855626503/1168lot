<?php

namespace Gametech\Admin\Support;

class SelfUpdateManager
{
    public function getInstalledVersion(): string
    {
        return (string) (config('self-update.version_installed')
            ?: config('app.version')
            ?: 'unknown');
    }

    public function getVersionAvailable(): ?string
    {
        return null;
    }

    public function isNewVersionAvailable(?string $current = null): bool
    {
        return false;
    }

    public function isEnabled(): bool
    {
        return (bool) config('self-update.enabled', false);
    }

    public function getDecommissionedMessage(): string
    {
        return (string) config(
            'self-update.decommissioned_message',
            'ระบบ self-update ถูกถอดออกจากแอปแล้ว ให้ใช้ deployment flow ภายนอกแทน'
        );
    }
}
