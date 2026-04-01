<?php

namespace Gametech\Lotto\Services;

use Illuminate\Support\Facades\Cache;

class LottoPackageSelectionService
{
    private const TTL_SECONDS = 43200;

    public function select(int $memberId, int $groupId, int $packageId): void
    {
        Cache::put($this->cacheKey($memberId, $groupId), $packageId, self::TTL_SECONDS);
    }

    public function getSelectedPackageId(int $memberId, int $groupId): ?int
    {
        $value = Cache::get($this->cacheKey($memberId, $groupId));
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }

    private function cacheKey(int $memberId, int $groupId): string
    {
        return sprintf('lotto:selected-package:%d:%d', $memberId, $groupId);
    }
}

