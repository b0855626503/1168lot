<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LotteryMarketDataTable;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\YeekeeMarketSetting;
use Gametech\Lotto\Support\ToggleFieldGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LotteryMarketController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LotteryMarketDataTable $dataTable)
    {
        $groups = LotteryGroup::query()->orderBy('sort')->get();

        return $dataTable->render($this->_config['view'], compact('groups'));
    }

    public function loadData(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $data = LotteryMarket::query()->with('yeekeeSetting')->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $payload = $data->toArray();
        $payload['yeekee_settings'] = [
            'round_duration_minutes' => (int) ($data->yeekeeSetting->round_config['round_duration_minutes'] ?? 15),
            'shoot_window_after_bet_close_seconds' => (int) ($data->yeekeeSetting->round_config['shoot_window_after_bet_close_seconds'] ?? 60),
            'settlement_delay_after_shoot_close_seconds' => (int) ($data->yeekeeSetting->round_config['settlement_delay_after_shoot_close_seconds'] ?? 60),
            'expected_payout_sla_minutes' => (int) ($data->yeekeeSetting->round_config['expected_payout_sla_minutes'] ?? 5),
            'formula_preset' => (string) ($data->yeekeeSetting->formula_config['default_preset'] ?? 'SHOOTS_SUM_MINUS_POSITION'),
            'subtract_position' => (int) ($data->yeekeeSetting->formula_config['subtract_position'] ?? 16),
            'reward_enabled' => (bool) ($data->yeekeeSetting->reward_enabled ?? false),
            'min_bet_amount' => (float) ($data->yeekeeSetting->reward_config['min_bet_amount'] ?? 0),
            'refund_if_bet_entries_below_min' => (bool) ($data->yeekeeSetting->refund_if_bet_entries_below_min ?? false),
            'min_bet_entries_required' => (int) ($data->yeekeeSetting->refund_config['min_bet_entries_required'] ?? 0),
            'refund_count_mode' => (string) ($data->yeekeeSetting->refund_config['count_mode'] ?? 'count_bet_entries'),
            'refund_action' => (string) ($data->yeekeeSetting->refund_config['action'] ?? 'VOID_AND_REFUND'),
        ];

        return $this->sendResponse($payload, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request): JsonResponse
    {
        $data = (array) $request->input('data', []);

        $validated = validator($data, [
            'group_id' => ['required', 'integer', 'exists:lotto_groups,id'],
            'name' => ['required', 'string', 'max:191'],
            'name_en' => ['nullable', 'string', 'max:191'],
            'name_kh' => ['nullable', 'string', 'max:191'],
            'name_laos' => ['nullable', 'string', 'max:191'],
            'logo' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_markets,code'],
            'result_mode' => ['nullable', 'string', 'in:'.implode(',', LotteryMarket::resultModes())],
            'draw_mode' => ['nullable', 'string', 'in:'.implode(',', LotteryMarket::drawModes())],
            'draw_schedule_type' => ['nullable', 'string', 'in:'.implode(',', LotteryMarket::drawScheduleTypes())],
            'draw_days' => ['nullable', 'array'],
            'draw_dates' => ['nullable', 'array'],
            'auto_open_time' => ['nullable', 'date_format:H:i'],
            'auto_close_time' => ['nullable', 'date_format:H:i'],
            'auto_result_time' => ['nullable', 'date_format:H:i'],
            'result_url' => ['nullable', 'string', 'max:255'],
            'auto_settle_on_result' => ['nullable'],
            'auto_refund_on_no_result' => ['nullable'],
            'notify_result_telegram' => ['nullable'],
            'is_enabled' => ['nullable'],
            'yeekee_settings' => ['nullable', 'array'],
        ])->validate();

        $schedulePayload = $this->resolveSchedulePayloadForSave($validated);
        $this->validateAutoDrawConfiguration($validated, $schedulePayload);

        $request->validate([
            'logo_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
            'icon_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
        ]);

        $payload = [
            'group_id' => (int) $validated['group_id'],
            'name' => $validated['name'],
            'name_en' => $this->nullableString($validated['name_en'] ?? null),
            'name_kh' => $this->nullableString($validated['name_kh'] ?? null),
            'name_laos' => $this->nullableString($validated['name_laos'] ?? null),
            'logo' => $this->nullableString($validated['logo'] ?? null),
            'icon' => $this->nullableString($validated['icon'] ?? null),
            'code' => strtolower($validated['code']),
            'result_mode' => $this->resolveResultMode($validated['result_mode'] ?? null),
            'draw_mode' => $schedulePayload['legacy_draw_mode'],
            'draw_schedule_type' => $schedulePayload['draw_schedule_type'],
            'draw_days' => $schedulePayload['draw_days'],
            'draw_dates' => $schedulePayload['draw_dates'],
            'auto_open_time' => $this->normalizeTime($validated['auto_open_time'] ?? null),
            'auto_close_time' => $this->normalizeTime($validated['auto_close_time'] ?? null),
            'auto_result_time' => $this->normalizeTime($validated['auto_result_time'] ?? null),
            'result_url' => $this->nullableString($validated['result_url'] ?? null),
            'auto_settle_on_result' => (bool) ($validated['auto_settle_on_result'] ?? true),
            'auto_refund_on_no_result' => (bool) ($validated['auto_refund_on_no_result'] ?? false),
            'notify_result_telegram' => (bool) ($validated['notify_result_telegram'] ?? true),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'affect_existing_members' => false,
        ];

        $payload = $this->attachUploadedMedia($request, $payload);

        $market = LotteryMarket::query()->create($payload);
        $this->upsertYeekeeSettingForMarket($market, (array) ($validated['yeekee_settings'] ?? []));

        return $this->sendSuccess('สร้างรายการหวยเรียบร้อยแล้ว');
    }

    public function edit(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');

        try {
            $status = ToggleFieldGuard::resolveBoolean($request->input('status'));
            $allowedFields = ['is_enabled', 'auto_settle_on_result', 'auto_refund_on_no_result'];
            $method = ToggleFieldGuard::resolveField((string) $request->input('method'), $allowedFields);
        } catch (InvalidArgumentException $exception) {
            return $this->sendError($exception->getMessage(), 422);
        }

        $market = LotteryMarket::query()->find($id);

        if (! $market) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $market->update([$method => $status]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $data = (array) $request->input('data', []);

        $market = LotteryMarket::query()->find($id);

        if (! $market) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator($data, [
            'group_id' => ['required', 'integer', 'exists:lotto_groups,id'],
            'name' => ['required', 'string', 'max:191'],
            'name_en' => ['nullable', 'string', 'max:191'],
            'name_kh' => ['nullable', 'string', 'max:191'],
            'name_laos' => ['nullable', 'string', 'max:191'],
            'logo' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:lotto_markets,code,'.$market->id],
            'result_mode' => ['nullable', 'string', 'in:'.implode(',', LotteryMarket::resultModes())],
            'draw_mode' => ['nullable', 'string', 'in:'.implode(',', LotteryMarket::drawModes())],
            'draw_schedule_type' => ['nullable', 'string', 'in:'.implode(',', LotteryMarket::drawScheduleTypes())],
            'draw_days' => ['nullable', 'array'],
            'draw_dates' => ['nullable', 'array'],
            'auto_open_time' => ['nullable', 'date_format:H:i'],
            'auto_close_time' => ['nullable', 'date_format:H:i'],
            'auto_result_time' => ['nullable', 'date_format:H:i'],
            'result_url' => ['nullable', 'string', 'max:255'],
            'auto_settle_on_result' => ['nullable'],
            'auto_refund_on_no_result' => ['nullable'],
            'notify_result_telegram' => ['nullable'],
            'is_enabled' => ['nullable'],
            'yeekee_settings' => ['nullable', 'array'],
        ])->validate();

        $schedulePayload = $this->resolveSchedulePayloadForSave($validated);
        $this->validateAutoDrawConfiguration($validated, $schedulePayload);

        $request->validate([
            'logo_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
            'icon_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
        ]);

        $nextResultMode = $this->resolveResultMode($validated['result_mode'] ?? null);
        $this->ensureMarketTypeCanBeChanged($market, $nextResultMode);

        $payload = [
            'group_id' => (int) $validated['group_id'],
            'name' => $validated['name'],
            'name_en' => $this->nullableString($validated['name_en'] ?? null),
            'name_kh' => $this->nullableString($validated['name_kh'] ?? null),
            'name_laos' => $this->nullableString($validated['name_laos'] ?? null),
            'logo' => $this->nullableString($validated['logo'] ?? null),
            'icon' => $this->nullableString($validated['icon'] ?? null),
            'code' => strtolower($validated['code']),
            'result_mode' => $nextResultMode,
            'draw_mode' => $schedulePayload['legacy_draw_mode'],
            'draw_schedule_type' => $schedulePayload['draw_schedule_type'],
            'draw_days' => $schedulePayload['draw_days'],
            'draw_dates' => $schedulePayload['draw_dates'],
            'auto_open_time' => $this->normalizeTime($validated['auto_open_time'] ?? null),
            'auto_close_time' => $this->normalizeTime($validated['auto_close_time'] ?? null),
            'auto_result_time' => $this->normalizeTime($validated['auto_result_time'] ?? null),
            'result_url' => $this->nullableString($validated['result_url'] ?? null),
            'auto_settle_on_result' => (bool) ($validated['auto_settle_on_result'] ?? true),
            'auto_refund_on_no_result' => (bool) ($validated['auto_refund_on_no_result'] ?? false),
            'notify_result_telegram' => (bool) ($validated['notify_result_telegram'] ?? true),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
        ];

        $payload = $this->attachUploadedMedia($request, $payload, [
            'logo' => (string) ($market->logo ?? ''),
            'icon' => (string) ($market->icon ?? ''),
        ]);

        $market->update($payload);
        $this->upsertYeekeeSettingForMarket($market->fresh(), (array) ($validated['yeekee_settings'] ?? []));

        return $this->sendSuccess('อัปเดตรายการหวยเรียบร้อยแล้ว');
    }

    private function attachUploadedMedia(Request $request, array $payload, array $oldMedia = []): array
    {
        $map = [
            'logo_file' => 'logo',
            'icon_file' => 'icon',
        ];

        foreach ($map as $fileField => $column) {
            if (! $request->hasFile($fileField)) {
                continue;
            }

            $file = $request->file($fileField);
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('lotto/media', 'public');
            $payload[$column] = '/storage/'.$path;

            $this->deleteOldPublicFile($oldMedia[$column] ?? null);
        }

        return $payload;
    }

    private function deleteOldPublicFile(?string $oldPath): void
    {
        if (! $oldPath || ! str_starts_with($oldPath, '/storage/')) {
            return;
        }

        try {
            $relative = substr($oldPath, strlen('/storage/'));
            if ($relative !== '') {
                Storage::disk('public')->delete($relative);
            }
        } catch (\Throwable $exception) {
            // ignore file deletion failure to keep update flow stable
        }
    }

    private function nullableString($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeTime($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed.':00';
    }

    private function resolveResultMode($value): string
    {
        $mode = is_string($value) ? trim(strtolower($value)) : '';

        if ($mode === LotteryMarket::RESULT_MODE_YEEKEE) {
            return LotteryMarket::RESULT_MODE_YEEKEE;
        }

        return LotteryMarket::RESULT_MODE_NORMAL;
    }

    private function ensureMarketTypeCanBeChanged(LotteryMarket $market, string $nextResultMode): void
    {
        $currentResultMode = $this->resolveResultMode($market->result_mode ?? null);
        if ($currentResultMode === $nextResultMode) {
            return;
        }

        $hasDrawOrTicket = $market->draws()
            ->leftJoin('lotto_tickets', 'lotto_tickets.draw_id', '=', 'lotto_draws.id')
            ->where('lotto_draws.market_id', (int) $market->id)
            ->selectRaw('COUNT(*) as aggregate')
            ->value('aggregate');

        if ((int) $hasDrawOrTicket > 0) {
            throw ValidationException::withMessages([
                'result_mode' => 'ไม่สามารถเปลี่ยนประเภทตลาดได้หลังมีงวดหรือโพยแล้ว',
            ]);
        }
    }

    private function upsertYeekeeSettingForMarket(LotteryMarket $market, array $settings = []): void
    {
        $mode = $this->resolveResultMode($market->result_mode ?? null);
        if ($mode !== LotteryMarket::RESULT_MODE_YEEKEE) {
            return;
        }

        $payload = $this->normalizeYeekeeSettingPayload($settings);

        YeekeeMarketSetting::query()->updateOrCreate(
            ['market_id' => (int) $market->id],
            $payload
        );
    }

    private function normalizeYeekeeSettingPayload(array $settings): array
    {
        $roundDurationMinutes = (int) ($settings['round_duration_minutes'] ?? 15);
        $shootWindowAfterBetCloseSeconds = (int) ($settings['shoot_window_after_bet_close_seconds'] ?? 60);
        $settlementDelayAfterShootCloseSeconds = (int) ($settings['settlement_delay_after_shoot_close_seconds'] ?? 60);
        $expectedPayoutSlaMinutes = (int) ($settings['expected_payout_sla_minutes'] ?? 5);
        $formulaPreset = strtoupper(trim((string) ($settings['formula_preset'] ?? 'SHOOTS_SUM_MINUS_POSITION')));
        $subtractPosition = (int) ($settings['subtract_position'] ?? 16);
        $rewardEnabled = (bool) ($settings['reward_enabled'] ?? false);
        $rewardPositions = is_array($settings['reward_positions'] ?? null) ? $settings['reward_positions'] : [];
        $minBetAmount = (float) ($settings['min_bet_amount'] ?? 0);
        $refundEnabled = (bool) ($settings['refund_if_bet_entries_below_min'] ?? false);
        $minBetEntries = (int) ($settings['min_bet_entries_required'] ?? 0);
        $refundCountMode = (string) ($settings['refund_count_mode'] ?? 'count_bet_entries');
        $refundAction = (string) ($settings['refund_action'] ?? 'VOID_AND_REFUND');

        if ($roundDurationMinutes < 1 || $roundDurationMinutes > 60) {
            throw ValidationException::withMessages(['yeekee_settings.round_duration_minutes' => 'ระยะเวลาต่อรอบต้องอยู่ระหว่าง 1-60 นาที']);
        }

        if ($shootWindowAfterBetCloseSeconds < 0 || $shootWindowAfterBetCloseSeconds > 3600) {
            throw ValidationException::withMessages(['yeekee_settings.shoot_window_after_bet_close_seconds' => 'ระยะเวลายิงเลขหลังปิดรับแทงต้องอยู่ระหว่าง 0-3600 วินาที']);
        }

        if ($settlementDelayAfterShootCloseSeconds < 0 || $settlementDelayAfterShootCloseSeconds > 3600) {
            throw ValidationException::withMessages(['yeekee_settings.settlement_delay_after_shoot_close_seconds' => 'เวลารอก่อนคำนวณผลต้องอยู่ระหว่าง 0-3600 วินาที']);
        }

        if ($expectedPayoutSlaMinutes < 1 || $expectedPayoutSlaMinutes > 60) {
            throw ValidationException::withMessages(['yeekee_settings.expected_payout_sla_minutes' => 'ระยะเวลาคาดหวังในการจ่ายรางวัลต้องอยู่ระหว่าง 1-60 นาที']);
        }

        if (! in_array($formulaPreset, ['SHOOTS_SUM_MINUS_POSITION', 'PRECOMMITTED_BASE64_MD5'], true)) {
            throw ValidationException::withMessages(['yeekee_settings.formula_preset' => 'สูตรคำนวณผลไม่ถูกต้อง']);
        }

        if ($subtractPosition <= 0) {
            throw ValidationException::withMessages(['yeekee_settings.subtract_position' => 'ลำดับเลขยิงที่ใช้ลบต้องมากกว่า 0']);
        }

        if ($minBetAmount < 0) {
            throw ValidationException::withMessages(['yeekee_settings.min_bet_amount' => 'ยอดเดิมพันขั้นต่ำต้องไม่ติดลบ']);
        }

        if ($minBetEntries < 0) {
            throw ValidationException::withMessages(['yeekee_settings.min_bet_entries_required' => 'จำนวนรายการแทงขั้นต่ำต้องไม่ติดลบ']);
        }

        if (! in_array($refundCountMode, ['count_bet_entries', 'count_unique_members'], true)) {
            throw ValidationException::withMessages(['yeekee_settings.refund_count_mode' => 'รูปแบบการนับรายการแทงไม่ถูกต้อง']);
        }

        if ($refundAction !== 'VOID_AND_REFUND') {
            throw ValidationException::withMessages(['yeekee_settings.refund_action' => 'การดำเนินการเมื่อไม่ผ่านเงื่อนไขต้องเป็น งดออกผลและคืนโพย เท่านั้น']);
        }

        $normalizedRewardPositions = collect($rewardPositions)->map(static function ($item): ?array {
            if (! is_array($item)) {
                return null;
            }

            $position = (int) ($item['position'] ?? 0);
            $creditAmount = (float) ($item['credit_amount'] ?? 0);
            if ($position <= 0 || $creditAmount <= 0) {
                return null;
            }

            return [
                'position' => $position,
                'credit_amount' => $creditAmount,
            ];
        })->filter()->values()->all();

        return [
            'round_config' => [
                'round_duration_minutes' => $roundDurationMinutes,
                'shoot_window_after_bet_close_seconds' => $shootWindowAfterBetCloseSeconds,
                'settlement_delay_after_shoot_close_seconds' => $settlementDelayAfterShootCloseSeconds,
                'expected_payout_sla_minutes' => $expectedPayoutSlaMinutes,
            ],
            'formula_config' => [
                'default_preset' => $formulaPreset,
                'subtract_position' => $subtractPosition,
            ],
            'reward_config' => [
                'reward_enabled' => $rewardEnabled,
                'reward_positions' => $normalizedRewardPositions,
                'min_bet_amount' => $minBetAmount,
            ],
            'refund_config' => [
                'refund_if_bet_entries_below_min' => $refundEnabled,
                'min_bet_entries_required' => $minBetEntries,
                'count_mode' => $refundCountMode,
                'action' => $refundAction,
            ],
            'ui_config' => null,
            'reward_enabled' => $rewardEnabled,
            'refund_if_bet_entries_below_min' => $refundEnabled,
        ];
    }

    /**
     * @param  array{draw_schedule_type:string,draw_days:array<int,int>,draw_dates:array<int,int>}  $schedulePayload
     */
    private function validateAutoDrawConfiguration(array $validated, array $schedulePayload): void
    {
        $scheduleType = (string) $schedulePayload['draw_schedule_type'];
        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_MANUAL) {
            return;
        }

        if (empty($validated['auto_close_time'])) {
            throw new InvalidArgumentException('กรุณาระบุเวลาปิดรับอัตโนมัติสำหรับโหมดงวดอัตโนมัติ');
        }

        if (! empty($validated['auto_open_time']) && ! empty($validated['auto_close_time'])) {
            if ((string) $validated['auto_open_time'] === (string) $validated['auto_close_time']) {
                throw new InvalidArgumentException('เวลาเปิดรับและเวลาปิดรับต้องไม่เท่ากัน');
            }
        }

        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY && count($schedulePayload['draw_days']) === 0) {
            throw new InvalidArgumentException('กรุณาเลือกวันออกงวดอย่างน้อย 1 วัน สำหรับโหมดรายสัปดาห์');
        }

        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_MONTHLY && count($schedulePayload['draw_dates']) === 0) {
            throw new InvalidArgumentException('กรุณาเลือกวันที่ออกงวดอย่างน้อย 1 วัน สำหรับโหมดรายเดือน');
        }
    }

    /**
     * @return array{draw_schedule_type:string,draw_days:array<int,int>,draw_dates:array<int,int>,legacy_draw_mode:string}
     */
    private function resolveSchedulePayloadForSave(array $validated): array
    {
        $scheduleType = $this->nullableString($validated['draw_schedule_type'] ?? null);
        $drawDays = $this->normalizeIntegerArray($validated['draw_days'] ?? null, 1, 7);
        $drawDates = $this->normalizeIntegerArray($validated['draw_dates'] ?? null, 1, 31);

        if ($scheduleType === null) {
            $legacyDrawMode = (string) ($validated['draw_mode'] ?? LotteryMarket::DRAW_MODE_MANUAL);
            if ($legacyDrawMode === LotteryMarket::DRAW_MODE_DAILY) {
                $scheduleType = LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY;
                $drawDays = [1, 2, 3, 4, 5, 6, 7];
                $drawDates = [];
            } elseif ($legacyDrawMode === LotteryMarket::DRAW_MODE_WEEKDAYS) {
                $scheduleType = LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY;
                $drawDays = [1, 2, 3, 4, 5];
                $drawDates = [];
            } elseif ($legacyDrawMode === LotteryMarket::DRAW_MODE_WED_SAT_SUN) {
                $scheduleType = LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY;
                $drawDays = [3, 6, 7];
                $drawDates = [];
            } else {
                $scheduleType = LotteryMarket::DRAW_SCHEDULE_TYPE_MANUAL;
                $drawDays = [];
                $drawDates = [];
            }
        }

        if ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_MANUAL) {
            $drawDays = [];
            $drawDates = [];
        } elseif ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY) {
            $drawDates = [];
        } elseif ($scheduleType === LotteryMarket::DRAW_SCHEDULE_TYPE_MONTHLY) {
            $drawDays = [];
        }

        return [
            'draw_schedule_type' => $scheduleType,
            'draw_days' => $drawDays,
            'draw_dates' => $drawDates,
            'legacy_draw_mode' => $this->mapScheduleToLegacyDrawMode($scheduleType, $drawDays),
        ];
    }

    /**
     * @return array<int,int>
     */
    private function normalizeIntegerArray($value, int $min, int $max): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) === '') {
                continue;
            }

            $number = (int) $item;
            if ($number < $min || $number > $max) {
                continue;
            }

            $normalized[$number] = $number;
        }

        $result = array_values($normalized);
        sort($result);

        return $result;
    }

    /**
     * @param  array<int,int>  $drawDays
     */
    private function mapScheduleToLegacyDrawMode(string $scheduleType, array $drawDays): string
    {
        if ($scheduleType !== LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY) {
            return LotteryMarket::DRAW_MODE_MANUAL;
        }

        if ($drawDays === [1, 2, 3, 4, 5, 6, 7]) {
            return LotteryMarket::DRAW_MODE_DAILY;
        }

        if ($drawDays === [1, 2, 3, 4, 5]) {
            return LotteryMarket::DRAW_MODE_WEEKDAYS;
        }

        if ($drawDays === [3, 6, 7]) {
            return LotteryMarket::DRAW_MODE_WED_SAT_SUN;
        }

        return LotteryMarket::DRAW_MODE_MANUAL;
    }
}
