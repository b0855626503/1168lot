<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoMarketBetSettingDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoMarketBetSetting;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Support\ToggleFieldGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class LottoMarketBetSettingController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoMarketBetSettingDataTable $dataTable)
    {
        $marketOptions = LotteryMarket::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($market) => ['value' => (int) $market->id, 'text' => $market->name])
            ->values()
            ->toArray();

        $betTypeOptions = collect(BetType::all())
            ->map(fn (string $type) => [
                'value' => $type,
                'text' => $type . ' = ' . BetType::label($type),
            ])
            ->values()
            ->toArray();

        return $dataTable->render($this->_config['view'], [
            'marketOptions' => $marketOptions,
            'betTypeOptions' => $betTypeOptions,
        ]);
    }

    public function loadData(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = LottoMarketBetSetting::query()->with('market')->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request): JsonResponse
    {
        $data = (array) $request->input('data', []);

        $validated = validator($data, [
            'market_id'       => ['required', 'integer', 'exists:lotto_markets,id'],
            'bet_type'        => [
                'required',
                Rule::in(BetType::all()),
                Rule::unique('lotto_market_bet_settings', 'bet_type')
                    ->where(fn ($query) => $query->where('market_id', (int) ($data['market_id'] ?? 0))),
            ],
            'min_bet'         => ['required', 'numeric', 'min:0'],
            'max_bet'         => ['required', 'numeric', 'min:0', 'gte:min_bet'],
            'max_per_number'  => ['required', 'numeric', 'min:0'],
            'is_enabled'      => ['nullable', 'boolean'],
        ], [
            'bet_type.unique' => 'ประเภทเดิมพันนี้มีอยู่แล้วในรายการหวยที่เลือก',
            'bet_type.in' => 'ประเภทเดิมพันไม่ถูกต้อง',
        ])->validate();

        if (array_key_exists('payout', $data) || array_key_exists('discount_percent', $data)) {
            return $this->sendError('DEPRECATED_PAYOUT_OVERRIDE: ห้ามตั้งค่า payout/discount ระดับ market โปรดใช้ Group Package', 422);
        }

        try {
            LottoMarketBetSetting::query()->create([
                'market_id'       => $validated['market_id'],
                'bet_type'        => $validated['bet_type'],
                'payout'          => 0,
                'min_bet'         => $validated['min_bet'],
                'max_bet'         => $validated['max_bet'],
                'max_per_number'  => $validated['max_per_number'],
                'is_enabled'      => $validated['is_enabled'] ?? true,
            ]);

            return $this->sendResponse([], 'เพิ่มค่าตั้งต้นสำเร็จ');
        } catch (\Exception $e) {
            return $this->sendError('เพิ่มค่าตั้งต้นไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function edit(Request $request): JsonResponse
    {
        $id     = (int) $request->input('id');
        $status = $request->input('status');
        $method = (string) $request->input('method', '');

        $data = LottoMarketBetSetting::query()->find($id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ($method && ! is_null($status)) {
            try {
                $resolvedStatus = ToggleFieldGuard::resolveBoolean($status);
                $resolvedMethod = ToggleFieldGuard::resolveField($method, ['is_enabled']);
            } catch (InvalidArgumentException $exception) {
                return $this->sendError($exception->getMessage(), 422);
            }

            $data->update([$resolvedMethod => $resolvedStatus]);

            return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = (array) $request->input('data', []);

        $setting = LottoMarketBetSetting::query()->find((int) $id);
        if (! $setting) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator($data, [
            'market_id'       => ['required', 'integer', 'exists:lotto_markets,id'],
            'bet_type'        => [
                'required',
                Rule::in(BetType::all()),
                Rule::unique('lotto_market_bet_settings', 'bet_type')
                    ->ignore((int) $id)
                    ->where(fn ($query) => $query->where('market_id', (int) ($data['market_id'] ?? 0))),
            ],
            'min_bet'         => ['required', 'numeric', 'min:0'],
            'max_bet'         => ['required', 'numeric', 'min:0', 'gte:min_bet'],
            'max_per_number'  => ['required', 'numeric', 'min:0'],
            'is_enabled'      => ['nullable', 'boolean'],
        ], [
            'bet_type.unique' => 'ประเภทเดิมพันนี้มีอยู่แล้วในรายการหวยที่เลือก',
            'bet_type.in' => 'ประเภทเดิมพันไม่ถูกต้อง',
        ])->validate();

        if (array_key_exists('payout', $data) || array_key_exists('discount_percent', $data)) {
            return $this->sendError('DEPRECATED_PAYOUT_OVERRIDE: ห้ามตั้งค่า payout/discount ระดับ market โปรดใช้ Group Package', 422);
        }

        try {
            $setting->update([
                'market_id'       => $validated['market_id'],
                'bet_type'        => $validated['bet_type'],
                'min_bet'         => $validated['min_bet'],
                'max_bet'         => $validated['max_bet'],
                'max_per_number'  => $validated['max_per_number'],
                'is_enabled'      => $validated['is_enabled'],
            ]);

            return $this->sendResponse([], 'อัปเดตค่าตั้งต้นสำเร็จ');
        } catch (\Exception $e) {
            return $this->sendError('อัปเดตค่าตั้งต้นไม่สำเร็จ: ' . $e->getMessage());
        }
    }
}
