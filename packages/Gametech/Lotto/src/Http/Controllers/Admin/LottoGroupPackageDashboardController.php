<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoGroupPackage;

class LottoGroupPackageDashboardController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index()
    {
        $betTypes = collect(BetType::all())
            ->map(fn (string $type) => [
                'key' => $type,
                'label' => BetType::label($type),
            ])
            ->values()
            ->toArray();

        $groups = LotteryGroup::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $groupIds = $groups->pluck('id')->all();

        $markets = LotteryMarket::query()
            ->whereIn('group_id', $groupIds)
            ->orderBy('id')
            ->get()
            ->groupBy('group_id');

        $packages = LottoGroupPackage::query()
            ->with(['betSettings' => static function ($query) {
                $query->orderBy('bet_type');
            }])
            ->whereIn('group_id', $groupIds)
            ->orderBy('id')
            ->get()
            ->groupBy('group_id');

        $groupTabs = $groups->map(function ($group) use ($markets, $packages, $betTypes): array {
            $groupMarkets = $markets->get($group->id, collect())->map(static function ($market): array {
                return [
                    'id' => (int) $market->id,
                    'name' => (string) $market->name,
                    'code' => (string) $market->code,
                    'logo' => (string) ($market->logo ?? ''),
                    'icon' => (string) ($market->icon ?? ''),
                ];
            })->values()->all();

            $groupPackages = $packages->get($group->id, collect())->map(static function ($package) use ($betTypes): array {
                $settingMap = $package->betSettings->keyBy('bet_type');

                $settings = collect($betTypes)->mapWithKeys(function (array $type) use ($settingMap): array {
                    $setting = $settingMap->get($type['key']);

                    return [
                        $type['key'] => [
                            'payout' => $setting ? (float) $setting->payout : null,
                            'discount_percent' => $setting ? (float) ($setting->discount_percent ?? 0) : null,
                            'is_enabled' => $setting ? (bool) $setting->is_enabled : false,
                        ],
                    ];
                })->all();

                return [
                    'id' => (int) $package->id,
                    'group_id' => (int) $package->group_id,
                    'name' => (string) $package->name,
                    'description' => (string) ($package->description ?? ''),
                    'image' => (string) ($package->image ?? ''),
                    'is_active' => (bool) $package->is_active,
                    'settings' => $settings,
                ];
            })->values()->all();

            return [
                'id' => (int) $group->id,
                'name' => (string) $group->name,
                'markets' => $groupMarkets,
                'packages' => $groupPackages,
            ];
        })->values()->all();

        return view($this->_config['view'], [
            'groupTabs' => $groupTabs,
            'betTypes' => $betTypes,
        ]);
    }
}
