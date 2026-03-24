<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoDrawDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Support\DrawStatusFlow;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class LottoDrawController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoDrawDataTable $dataTable, DrawService $drawService)
    {
        $drawService->syncScheduledStatuses();

        $groupOptions = LotteryGroup::query()
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static function (LotteryGroup $group): array {
                return [
                    'value' => (int) $group->id,
                    'text' => (string) $group->name,
                ];
            })
            ->values()
            ->toArray();

        $marketOptions = LotteryMarket::query()
            ->with('group:id,name,sort')
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'group_id', 'name', 'logo', 'icon'])
            ->groupBy(static function (LotteryMarket $market): string {
                return (string) optional($market->group)->name ?: 'ไม่ระบุกลุ่ม';
            })
            ->map(static function ($markets, $groupName): array {
                return [
                    'label' => (string) $groupName,
                    'options' => $markets->map(static function (LotteryMarket $market): array {
                        return [
                            'value' => (int) $market->id,
                            'text' => (string) $market->name,
                            'group_id' => (int) $market->group_id,
                            'logo' => (string) ($market->logo ?: $market->icon ?: ''),
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $latestDrawDate = LottoDraw::query()->max('draw_date');

        return $dataTable->render($this->_config['view'], [
            'groupOptions' => $groupOptions,
            'marketOptions' => $marketOptions,
            'latestDrawDate' => $latestDrawDate ? (string) $latestDrawDate : '',
        ]);
    }

    public function loadData(Request $request, DrawService $drawService): JsonResponse
    {
        $drawService->syncScheduledStatuses();

        $id   = $request->input('id');
        $data = LottoDraw::query()->with('market')->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse([
            'id' => (int) $data->id,
            'market_id' => (int) $data->market_id,
            'market' => [
                'id' => (int) ($data->market->id ?? 0),
                'name' => (string) ($data->market->name ?? '-'),
            ],
            'draw_date' => $data->draw_date ? $data->draw_date->format('Y-m-d') : null,
            'open_at' => $this->formatDateTimeForForm($data->open_at),
            'close_at' => $this->formatDateTimeForForm($data->close_at),
            'result_at' => $this->formatDateTimeForForm($data->result_at),
            'status' => (string) $data->status,
            'result_number' => is_array($data->result_number) ? $data->result_number : [],
        ], 'ดำเนินการเสร็จสิ้น');
    }

    public function loadBlockedNumbers(Request $request): JsonResponse
    {
        $draw = LottoDraw::query()
            ->with('market:id,name')
            ->find((int) $request->input('id'));

        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $rows = LottoNumberBlock::query()
            ->where('draw_id', (int) $draw->id)
            ->orderBy('bet_type')
            ->orderBy('number')
            ->get(['id', 'bet_type', 'number', 'mode', 'blocked_at', 'reason']);

        return $this->sendResponse([
            'draw' => [
                'id' => (int) $draw->id,
                'market_name' => (string) ($draw->market->name ?? '-'),
                'draw_date' => $draw->draw_date ? $draw->draw_date->format('d/m/Y') : '-',
            ],
            'count' => $rows->count(),
            'items' => $rows->map(static function ($row): array {
                return [
                    'id' => (int) $row->id,
                    'bet_type' => (string) $row->bet_type,
                    'bet_type_label' => BetType::label((string) $row->bet_type),
                    'number' => (string) $row->number,
                    'mode' => (string) $row->mode,
                    'blocked_at' => $row->blocked_at ? Carbon::parse((string) $row->blocked_at)->format('d/m/Y H:i:s') : '-',
                    'reason' => (string) ($row->reason ?? ''),
                ];
            })->values()->all(),
        ], 'ดึงรายการเลขอั้นสำเร็จ');
    }

    public function loadTicketsSummary(Request $request): JsonResponse
    {
        $draw = LottoDraw::query()
            ->with('market:id,name')
            ->find((int) $request->input('id'));

        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $rows = LottoTicket::query()
            ->with([
                'member:code,user_name,name',
                'items:id,ticket_id,bet_type,number',
            ])
            ->where('draw_id', (int) $draw->id)
            ->orderByDesc('id')
            ->get(['id', 'member_id', 'total_amount', 'total_bet_amount', 'status', 'created_at']);

        return $this->sendResponse([
            'draw' => [
                'id' => (int) $draw->id,
                'market_name' => (string) ($draw->market->name ?? '-'),
                'draw_date' => $draw->draw_date ? $draw->draw_date->format('d/m/Y') : '-',
            ],
            'count' => $rows->count(),
            'items' => $rows->map(static function ($row): array {
                $betTypes = $row->items
                    ->pluck('bet_type')
                    ->filter()
                    ->unique()
                    ->values()
                    ->map(static fn ($betType) => BetType::label((string) $betType))
                    ->implode(', ');
                $betNumbers = $row->items
                    ->pluck('number')
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    'id' => (int) $row->id,
                    'member_id' => (int) $row->member_id,
                    'member_username' => (string) ($row->member->user_name ?? ''),
                    'member_name' => (string) ($row->member->name ?? ''),
                    'member_display' => (string) ($row->member->user_name ?? $row->member->name ?? ('MEM-' . $row->member_id)),
                    'bet_types' => (string) ($betTypes !== '' ? $betTypes : '-'),
                    'bet_numbers' => (string) ($betNumbers !== '' ? $betNumbers : '-'),
                    'total_amount' => (float) ($row->total_bet_amount ?? $row->total_amount ?? 0),
                    'status' => (string) $row->status,
                    'created_at' => $row->created_at ? $row->created_at->format('d/m/Y H:i:s') : '-',
                ];
            })->values()->all(),
        ], 'ดึงรายการแทงสำเร็จ');
    }

    public function create(Request $request, DrawService $drawService): JsonResponse
    {
        $data = (array) $request->input('data', []);

        $validated = validator($data, [
            'market_id'   => ['required', 'integer', 'exists:lotto_markets,id'],
            'draw_date'   => ['required', 'date_format:Y-m-d'],
            'open_at'     => ['required', 'date_format:Y-m-d H:i'],
            'close_at'    => ['required', 'date_format:Y-m-d H:i', 'after:open_at'],
            'status'      => ['nullable', Rule::in(DrawStatusFlow::allowedStatuses())],
            'result_at'   => ['nullable', 'date_format:Y-m-d H:i'],
        ])->validate();

        try {
            $targetStatus = (string) ($validated['status'] ?? 'draft');

            $draw = $drawService->createDraft([
                'market_id'   => $validated['market_id'],
                'draw_date'   => $validated['draw_date'],
                'open_at'     => $this->normalizeDateTimeInput($validated['open_at']),
                'close_at'    => $this->normalizeDateTimeInput($validated['close_at']),
                'result_at'   => $this->normalizeDateTimeInput($validated['result_at'] ?? null),
                'created_by'  => auth()->id(),
            ]);

            $this->applyStatusTransition($drawService, $draw, 'draft', $targetStatus);

            return $this->sendSuccess('เพิ่มงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->sendError('เพิ่มงวดหวยไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function edit(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = LottoDraw::query()->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request, DrawService $drawService): JsonResponse
    {
        $id   = $request->input('id');
        $data = (array) $request->input('data', []);

        $draw = LottoDraw::query()->find((int) $id);
        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator($data, [
            'market_id'   => ['required', 'integer', 'exists:lotto_markets,id'],
            'draw_date'   => ['required', 'date_format:Y-m-d'],
            'open_at'     => ['required', 'date_format:Y-m-d H:i'],
            'close_at'    => ['required', 'date_format:Y-m-d H:i', 'after:open_at'],
            'status'      => ['nullable', Rule::in(DrawStatusFlow::allowedStatuses())],
            'result_at'   => ['nullable', 'date_format:Y-m-d H:i'],
        ])->validate();

        try {
            $currentStatus = (string) $draw->status;
            $targetStatus = (string) ($validated['status'] ?? $draw->status);

            $draw->update([
                'market_id'   => $validated['market_id'],
                'draw_date'   => $validated['draw_date'],
                'open_at'     => $this->normalizeDateTimeInput($validated['open_at']),
                'close_at'    => $this->normalizeDateTimeInput($validated['close_at']),
                'result_at'   => $this->normalizeDateTimeInput($validated['result_at'] ?? null),
            ]);

            $this->applyStatusTransition($drawService, $draw, $currentStatus, $targetStatus);

            return $this->sendSuccess('อัปเดตงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->sendError('อัปเดตงวดหวยไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function open(Request $request, DrawService $drawService): JsonResponse
    {
        $id = (int) $request->input('id');
        $draw = LottoDraw::query()->find($id);

        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        try {
            $drawService->openDraw($draw);

            return $this->sendSuccess('เปิดรับงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('เปิดรับงวดไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function close(Request $request, DrawService $drawService): JsonResponse
    {
        $id = (int) $request->input('id');
        $draw = LottoDraw::query()->find($id);

        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        try {
            $drawService->closeDraw($draw);

            return $this->sendSuccess('ปิดรับงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('ปิดรับงวดไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function settle(Request $request, SettlementService $settlementService): JsonResponse
    {
        $id = (int) $request->input('id');
        $data = (array) $request->input('data', []);

        $draw = LottoDraw::query()->find($id);

        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ((string) $draw->status !== 'closed') {
            return $this->sendError('ประกาศผลได้เฉพาะงวดที่ปิดรับแล้ว', 422);
        }

        $validated = validator($data, [
            'result_number' => ['required', 'array'],
            'result_number.first_prize' => ['required', 'digits:6'],
            'result_number.last_2_digits' => ['required', 'digits:2'],
            'result_at' => ['nullable', 'date_format:Y-m-d H:i'],
        ])->validate();

        try {
            $summary = $settlementService->settleDraw(
                $draw,
                (array) $validated['result_number'],
                $this->normalizeDateTimeInput($validated['result_at'] ?? null)
            );

            return $this->sendResponse($summary, 'ประกาศผลและประมวลผลโพยสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ประกาศผลไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function generateAuto(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'market_id' => ['nullable', 'integer', 'exists:lotto_markets,id'],
            'dry_run' => ['nullable'],
        ])->validate();

        $params = [
            '--days' => (int) ($validated['days'] ?? 1),
        ];

        if (! empty($validated['date'])) {
            $params['--date'] = (string) $validated['date'];
        }

        if (! empty($validated['market_id'])) {
            $params['--market_id'] = (int) $validated['market_id'];
        }

        if ((bool) ($validated['dry_run'] ?? false)) {
            $params['--dry-run'] = true;
        }

        Artisan::call('lotto:generate-auto-draws', $params);

        $output = trim((string) Artisan::output());
        $decoded = json_decode($output, true);

        return $this->sendResponse([
            'command' => 'lotto:generate-auto-draws',
            'params' => $params,
            'summary' => is_array($decoded) ? $decoded : null,
            'raw_output' => is_array($decoded) ? null : $output,
        ], 'สั่งสร้างงวดอัตโนมัติเรียบร้อยแล้ว');
    }

    private function applyStatusTransition(
        DrawService $drawService,
        LottoDraw $draw,
        string $currentStatus,
        string $targetStatus
    ): void {
        $steps = DrawStatusFlow::transitionSteps($currentStatus, $targetStatus);

        foreach ($steps as $step) {
            if ($step === 'open') {
                $draw = $drawService->openDraw($draw);
            }

            if ($step === 'close') {
                $draw = $drawService->closeDraw($draw);
            }
        }
    }

    private function normalizeDateTimeInput(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $timezone = (string) config('app.timezone', 'Asia/Bangkok');
        $dateTime = Carbon::createFromFormat('Y-m-d H:i', (string) $value, $timezone);

        return $dateTime->format('Y-m-d H:i:s');
    }

    private function formatDateTimeForForm($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->copy()->setTimezone((string) config('app.timezone', 'Asia/Bangkok'))
                ->format('Y-m-d H:i');
        }

        return Carbon::parse((string) $value, (string) config('app.timezone', 'Asia/Bangkok'))
            ->format('Y-m-d H:i');
    }

}
