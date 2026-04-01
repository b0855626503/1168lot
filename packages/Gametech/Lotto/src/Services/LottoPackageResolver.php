<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Exceptions\LottoPackageException;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoGroupPackage;
use Gametech\Lotto\Models\LottoGroupPackageBetSetting;

class LottoPackageResolver
{
    /**
     * @return array{package:LottoGroupPackage,setting:LottoGroupPackageBetSetting}
     */
    public function resolveForBet(LottoDraw $draw, int $packageId, string $betType): array
    {
        $package = LottoGroupPackage::query()
            ->with('betSettings')
            ->find($packageId);

        if (! $package || (int) $package->group_id !== (int) $draw->market?->group_id) {
            throw LottoPackageException::packageNotInGroup();
        }

        if (! (bool) $package->is_active) {
            throw LottoPackageException::packageInactive();
        }

        $setting = $package->betSettings
            ->where('bet_type', $betType)
            ->where('is_enabled', true)
            ->first();

        if (! $setting) {
            throw LottoPackageException::betTypeNotConfigured($betType);
        }

        return [
            'package' => $package,
            'setting' => $setting,
        ];
    }
}

