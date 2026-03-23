<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoMarketBetSetting;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LottoBetLimitController extends AppBaseController
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

        $markets = LotteryMarket::query()
            ->whereIn('group_id', $groups->pluck('id')->all())
            ->orderBy('id')
            ->get()
            ->groupBy('group_id');

        $settings = LottoMarketBetSetting::query()
            ->whereIn('market_id', $markets->flatten(1)->pluck('id')->all())
            ->get()
            ->groupBy('market_id');

        $groupTabs = $groups->map(function ($group) use ($markets, $settings, $betTypes): array {
            $groupMarkets = $markets->get($group->id, collect())->map(function ($market) use ($settings, $betTypes): array {
                $settingMap = $settings->get($market->id, collect())->keyBy('bet_type');

                $betSettingRows = collect($betTypes)->mapWithKeys(function (array $type) use ($settingMap): array {
                    $setting = $settingMap->get($type['key']);

                    return [
                        $type['key'] => [
                            'min_bet' => $setting ? (float) $setting->min_bet : null,
                            'max_bet' => $setting ? (float) $setting->max_bet : null,
                            'max_per_number' => $setting ? (float) $setting->max_per_number : null,
                        ],
                    ];
                })->all();

                return [
                    'id' => (int) $market->id,
                    'name' => (string) $market->name,
                    'code' => (string) $market->code,
                    'settings' => $betSettingRows,
                ];
            })->values()->all();

            return [
                'id' => (int) $group->id,
                'name' => (string) $group->name,
                'markets' => $groupMarkets,
            ];
        })->values()->all();

        return view($this->_config['view'], [
            'groupTabs' => $groupTabs,
            'betTypes' => $betTypes,
        ]);
    }

    public function loadMarket(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'market_id' => ['required', 'integer', 'exists:lotto_markets,id'],
        ])->validate();

        $market = LotteryMarket::query()->find((int) $validated['market_id']);
        if (! $market) {
            return $this->sendError('ไม่พบข้อมูลตลาดหวย', 404);
        }

        $settingMap = LottoMarketBetSetting::query()
            ->where('market_id', (int) $market->id)
            ->get()
            ->keyBy('bet_type');

        $payload = [
            'market_id' => (int) $market->id,
            'market_name' => (string) $market->name,
            'bet_settings' => collect(BetType::all())->mapWithKeys(function (string $betType) use ($settingMap): array {
                $setting = $settingMap->get($betType);

                return [
                    $betType => [
                        'min_bet' => $setting ? (float) $setting->min_bet : 0.00,
                        'max_bet' => $setting ? (float) $setting->max_bet : 0.00,
                        'max_per_number' => $setting ? (float) $setting->max_per_number : 0.00,
                    ],
                ];
            })->all(),
        ];

        return $this->sendResponse($payload, 'ดำเนินการเสร็จสิ้น');
    }

    public function updateMarket(Request $request): JsonResponse
    {
        $payload = validator($request->all(), [
            'market_id' => ['required', 'integer', 'exists:lotto_markets,id'],
            'settings' => ['required', 'array'],
        ])->validate();

        $marketId = (int) $payload['market_id'];
        $settings = (array) $payload['settings'];

        $rules = [];
        foreach (BetType::all() as $betType) {
            $rules["settings.{$betType}.min_bet"] = ['required', 'numeric', 'min:0'];
            $rules["settings.{$betType}.max_bet"] = ['required', 'numeric', 'min:0'];
            $rules["settings.{$betType}.max_per_number"] = ['required', 'numeric', 'min:0'];
        }

        validator(['settings' => $settings], $rules)->validate();

        foreach (BetType::all() as $betType) {
            $input = (array) ($settings[$betType] ?? []);
            if ((float) ($input['max_bet'] ?? 0) < (float) ($input['min_bet'] ?? 0)) {
                return $this->sendError('ค่าสูงสุดต้องมากกว่าหรือเท่ากับขั้นต่ำ', 422);
            }
        }

        DB::transaction(function () use ($marketId, $settings): void {
            foreach (BetType::all() as $betType) {
                $input = (array) ($settings[$betType] ?? []);

                $setting = LottoMarketBetSetting::query()->firstOrNew([
                    'market_id' => $marketId,
                    'bet_type' => $betType,
                ]);

                if (! $setting->exists) {
                    $setting->is_enabled = true;
                    $setting->payout = 0;
                    $setting->discount_percent = 0;
                }

                $setting->min_bet = (float) ($input['min_bet'] ?? 0);
                $setting->max_bet = (float) ($input['max_bet'] ?? 0);
                $setting->max_per_number = (float) ($input['max_per_number'] ?? 0);
                $setting->save();
            }
        });

        return $this->sendSuccess('อัปเดตขั้นต่ำ/สูงสุด/สูงสุดต่อเลขเรียบร้อยแล้ว');
    }

    public function copyFromTemplate(Request $request): JsonResponse
    {
        $payload = validator($request->all(), [
            'target_market_ids' => ['required', 'array', 'min:1'],
            'target_market_ids.*' => ['required', 'integer', 'exists:lotto_markets,id', 'distinct'],
            'settings' => ['required', 'array'],
        ], [
            'target_market_ids.required' => 'กรุณาเลือกรายการหวยปลายทางอย่างน้อย 1 รายการ',
            'target_market_ids.min' => 'กรุณาเลือกรายการหวยปลายทางอย่างน้อย 1 รายการ',
        ])->validate();

        $targetMarketIds = collect((array) $payload['target_market_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
        $settings = (array) $payload['settings'];

        if (empty($targetMarketIds)) {
            return $this->sendError('กรุณาเลือกรายการหวยปลายทางอย่างน้อย 1 รายการ', 422);
        }

        $rules = [];
        foreach (BetType::all() as $betType) {
            $rules["settings.{$betType}.min_bet"] = ['required', 'numeric', 'min:0'];
            $rules["settings.{$betType}.max_bet"] = ['required', 'numeric', 'min:0'];
            $rules["settings.{$betType}.max_per_number"] = ['required', 'numeric', 'min:0'];
        }

        validator(['settings' => $settings], $rules)->validate();

        foreach (BetType::all() as $betType) {
            $input = (array) ($settings[$betType] ?? []);
            if ((float) ($input['max_bet'] ?? 0) < (float) ($input['min_bet'] ?? 0)) {
                return $this->sendError('ค่าสูงสุดต้องมากกว่าหรือเท่ากับขั้นต่ำ', 422);
            }
        }

        DB::transaction(function () use ($settings, $targetMarketIds): void {
            foreach ($targetMarketIds as $targetMarketId) {
                foreach (BetType::all() as $betType) {
                    $input = (array) ($settings[$betType] ?? []);

                    $setting = LottoMarketBetSetting::query()->firstOrNew([
                        'market_id' => $targetMarketId,
                        'bet_type' => $betType,
                    ]);

                    if (! $setting->exists) {
                        $setting->is_enabled = true;
                        $setting->payout = 0;
                        $setting->discount_percent = 0;
                    }

                    $setting->min_bet = (float) ($input['min_bet'] ?? 0);
                    $setting->max_bet = (float) ($input['max_bet'] ?? 0);
                    $setting->max_per_number = (float) ($input['max_per_number'] ?? 0);
                    $setting->save();
                }
            }
        });

        return $this->sendSuccess('คัดลอกค่าไปยัง ' . count($targetMarketIds) . ' รายการเรียบร้อยแล้ว');
    }
}
