<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Carbon\CarbonInterface;
use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoDrawDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoResultFetchLog;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Services\AutoResultHardeningService;
use Gametech\Lotto\Services\DrawCancelAllRefundService;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Support\DrawStatusFlow;
use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use Gametech\Lotto\Support\ResultHash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class LottoDrawController extends AppBaseController
{
    protected array $_config;
    private LottoMarketDisplayFormatter $marketDisplayFormatter;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
        $this->marketDisplayFormatter = new LottoMarketDisplayFormatter;
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
            ->get(['id', 'group_id', 'name', 'logo', 'icon', 'result_mode'])
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
                            'result_mode' => (string) ($market->result_mode ?? LotteryMarket::RESULT_MODE_NORMAL),
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

        $id = $request->input('id');
        $data = LottoDraw::query()->with('market')->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $resultNumber = is_array($data->result_number) ? $data->result_number : [];
        $isYeekee = (string) ($data->market->result_mode ?? '') === LotteryMarket::RESULT_MODE_YEEKEE;
        if ($isYeekee && ! $this->canViewSensitiveYeekeeAudit()) {
            $resultNumber = $this->redactYeekeeSensitiveResultNumber($resultNumber);
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
            'result_number' => $resultNumber,
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
                'market_name' => $this->marketDisplayFormatter->formatPlain(
                    (string) ($draw->market->name ?? '-'),
                    (string) ($draw->market->result_mode ?? LotteryMarket::RESULT_MODE_NORMAL),
                    $this->resolveYeekeeRoundNo((int) $draw->id)
                ),
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
                'market_name' => $this->marketDisplayFormatter->formatPlain(
                    (string) ($draw->market->name ?? '-'),
                    (string) ($draw->market->result_mode ?? LotteryMarket::RESULT_MODE_NORMAL),
                    $this->resolveYeekeeRoundNo((int) $draw->id)
                ),
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
                    'member_display' => (string) ($row->member->user_name ?? $row->member->name ?? ('MEM-'.$row->member_id)),
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
            'market_id' => ['required', 'integer', 'exists:lotto_markets,id'],
            'draw_date' => ['required', 'date_format:Y-m-d'],
            'open_at' => ['required', 'date_format:Y-m-d H:i'],
            'close_at' => ['required', 'date_format:Y-m-d H:i'],
            'status' => ['nullable', Rule::in(DrawStatusFlow::allowedStatuses())],
            'result_at' => ['nullable', 'date_format:Y-m-d H:i'],
        ])->validate();

        try {
            $targetStatus = (string) ($validated['status'] ?? 'draft');
            [$normalizedOpenAt, $normalizedCloseAt] = $this->normalizeOpenCloseWindow(
                (string) $validated['open_at'],
                (string) $validated['close_at']
            );
            $normalizedResultAt = $this->normalizeResultAtWithCloseAt(
                $validated['result_at'] ?? null,
                $normalizedCloseAt
            );

            $draw = $drawService->createDraft([
                'market_id' => $validated['market_id'],
                'draw_date' => $validated['draw_date'],
                'open_at' => $normalizedOpenAt,
                'close_at' => $normalizedCloseAt,
                'result_at' => $normalizedResultAt,
                'created_by' => auth()->id(),
            ]);

            $this->applyStatusTransition($drawService, $draw, 'draft', $targetStatus);

            return $this->sendSuccess('เพิ่มงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->sendError('เพิ่มงวดหวยไม่สำเร็จ: '.$e->getMessage());
        }
    }

    public function edit(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $data = LottoDraw::query()->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $data = (array) $request->input('data', []);

        $draw = LottoDraw::query()->find((int) $id);
        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $rules = $this->rulesForUpdateByStatus($draw);
        if ($rules === []) {
            return $this->sendError('งวดที่ประกาศผลแล้วไม่อนุญาตให้แก้ไข', 422);
        }

        try {
            $this->assertNoUnexpectedFields($data, array_keys($rules));
            $validated = validator($data, $rules)->validate();

            $payload = $this->buildUpdatePayloadByStatus($draw, $validated);
            if ($payload === []) {
                throw new InvalidArgumentException('ไม่มีฟิลด์ที่อนุญาตให้แก้ไขสำหรับสถานะปัจจุบัน');
            }

            $draw->update($payload);

            return $this->sendSuccess('อัปเดตงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->sendError('อัปเดตงวดหวยไม่สำเร็จ: '.$e->getMessage());
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
            $drawService->openDraw($draw, $this->manualTransitionContext('manual_open'));

            return $this->sendSuccess('เปิดรับงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('เปิดรับงวดไม่สำเร็จ: '.$e->getMessage());
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
            $drawService->closeDraw($draw, $this->manualTransitionContext('manual_close'));

            return $this->sendSuccess('ปิดรับงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('ปิดรับงวดไม่สำเร็จ: '.$e->getMessage());
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

        try {
            $this->assertNoUnexpectedFields($data, ['result_number']);

            $validated = validator($data, [
                'result_number' => ['required', 'array'],
                'result_number.first_prize' => ['required', 'regex:/^\d{3,6}$/'],
                'result_number.last_2_digits' => ['required', 'digits:2'],
            ])->validate();

            $summary = $settlementService->settleDraw(
                $draw,
                (array) $validated['result_number']
            );

            return $this->sendResponse($summary, 'ประกาศผลและประมวลผลโพยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('ประกาศผลไม่สำเร็จ: '.$e->getMessage());
        }
    }

    public function markNoResult(Request $request, DrawCancelAllRefundService $drawCancelAllRefundService): JsonResponse
    {
        $drawId = (int) $request->input('id');
        $draw = LottoDraw::query()->find($drawId);
        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ((string) $draw->status === 'resulted' && $this->canCancelAllRefundByDraw($draw)) {
            return $this->sendSuccess('งวดนี้ถูกระบุเป็นงดออกผลไว้แล้ว');
        }

        if ((string) $draw->status !== 'closed') {
            return $this->sendError('ระบุงดออกผลได้เฉพาะงวดที่ปิดรับแล้ว', 422);
        }

        try {
            $summary = DB::transaction(function () use ($drawId, $drawCancelAllRefundService): array {
                /** @var LottoDraw $lockedDraw */
                $lockedDraw = LottoDraw::query()->lockForUpdate()->findOrFail($drawId);

                if ((string) $lockedDraw->status === 'resulted' && $this->canCancelAllRefundByDraw($lockedDraw)) {
                    return [
                        'draw_id' => (int) $lockedDraw->id,
                        'status' => (string) $lockedDraw->status,
                        'result_no_result' => true,
                    ];
                }

                if ((string) $lockedDraw->status !== 'closed') {
                    throw new InvalidArgumentException('ระบุงดออกผลได้เฉพาะงวดที่ปิดรับแล้ว');
                }

                $reason = 'งดออกผล';
                $resultNumber = [
                    'no_result' => true,
                    'status' => 'no_result',
                    'label' => $reason,
                    'no_result_reason' => $reason,
                    'manual_marked_no_result' => true,
                ];

                $refundSummary = null;
                if ($this->shouldAutoRefundOnNoResult((int) $lockedDraw->market_id)) {
                    $refundSummary = $drawCancelAllRefundService->cancelAllActiveTickets(
                        lockedDraw: $lockedDraw,
                        reason: $reason,
                        createdByType: 'system',
                        createdById: null,
                        groupCode: 'LOTTO_DRAW_CANCEL_AUTO_'.(int) $lockedDraw->id.'_'.now()->format('YmdHis')
                    );
                    $resultNumber['manual_cancelled_all_tickets'] = true;
                }

                $lockedDraw->forceFill([
                    'status' => 'resulted',
                    'result_at' => now(),
                    'result_number' => $resultNumber,
                    'result_fetch_status' => 'APPLIED',
                    'result_fetch_error' => null,
                    'result_hash' => ResultHash::fromPayload($resultNumber),
                    'result_applied_at' => now(),
                    'result_fetched_at' => now(),
                ])->save();

                return [
                    'draw_id' => (int) $lockedDraw->id,
                    'status' => (string) $lockedDraw->status,
                    'result_no_result' => true,
                    'auto_refund' => $refundSummary,
                ];
            });

            return $this->sendResponse($summary, 'ระบุงวดเป็นงดออกผลสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('ระบุงดออกผลไม่สำเร็จ: '.$e->getMessage(), 500);
        }
    }

    public function cancelAllRefund(Request $request, DrawCancelAllRefundService $drawCancelAllRefundService): JsonResponse
    {
        $canCancelAllRefund = ! function_exists('bouncer')
            || bouncer()->hasPermission('lotto_settings.draws.cancel_all_refund')
            || bouncer()->hasPermission('lotto_settings.draws.settle')
            || bouncer()->hasPermission('lotto_draws.settle');

        if (! $canCancelAllRefund) {
            return $this->sendError('ไม่มีสิทธิ์ยกเลิกโพยทั้งงวดและคืนเงิน', 403);
        }

        $drawId = (int) $request->input('id');
        $draw = LottoDraw::query()->find($drawId);
        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if (! $this->canCancelAllRefundByDraw($draw)) {
            return $this->sendError('ยกเลิกโพยทั้งงวดได้เฉพาะงวดที่ประกาศเป็น งดออกผล เท่านั้น', 422);
        }

        $adminId = auth('admin')->id();
        $groupCode = 'LOTTO_DRAW_CANCEL_'.$drawId.'_'.now()->format('YmdHis');

        try {
            $summary = DB::transaction(function () use ($drawId, $drawCancelAllRefundService, $groupCode, $adminId): array {
                /** @var LottoDraw $lockedDraw */
                $lockedDraw = LottoDraw::query()->lockForUpdate()->findOrFail($drawId);

                if (! $this->canCancelAllRefundByDraw($lockedDraw)) {
                    throw new InvalidArgumentException('สถานะงวดไม่อนุญาตให้ยกเลิกโพยทั้งงวด');
                }

                $reason = 'งดออกผล';
                $refundSummary = $drawCancelAllRefundService->cancelAllActiveTickets(
                    lockedDraw: $lockedDraw,
                    reason: $reason,
                    createdByType: 'admin',
                    createdById: $adminId ? (int) $adminId : null,
                    groupCode: $groupCode
                );

                $resultNumber = [
                    'no_result' => true,
                    'status' => 'no_result',
                    'label' => $reason,
                    'no_result_reason' => $reason,
                    'manual_cancelled_all_tickets' => true,
                ];

                $lockedDraw->forceFill([
                    'status' => 'resulted',
                    'result_at' => now(),
                    'result_number' => $resultNumber,
                    'result_fetch_status' => 'APPLIED',
                    'result_fetch_error' => null,
                    'result_hash' => ResultHash::fromPayload($resultNumber),
                    'result_applied_at' => now(),
                    'result_fetched_at' => now(),
                ])->save();

                return [
                    'draw_id' => (int) $lockedDraw->id,
                    'cancelled_tickets' => (int) ($refundSummary['cancelled_tickets'] ?? 0),
                    'refunded_amount' => (float) ($refundSummary['refunded_amount'] ?? 0.0),
                ];
            });

            return $this->sendResponse($summary, 'ยกเลิกโพยทั้งงวดและคืนเงินสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('ยกเลิกโพยทั้งงวดไม่สำเร็จ: '.$e->getMessage(), 500);
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

    public function autoResultMetrics(Request $request, AutoResultHardeningService $hardeningService): JsonResponse
    {
        $validated = validator($request->all(), [
            'hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ])->validate();

        $hours = (int) ($validated['hours'] ?? (int) config('lotto_auto_result.hardening.metrics.default_window_hours', 24));
        $to = now((string) config('lotto_auto_result.timezone', (string) config('app.timezone', 'Asia/Bangkok')));
        $from = $to->copy()->subHours(max(1, $hours));

        return $this->sendResponse(
            $hardeningService->metrics($from, $to),
            'ดึงสรุป metrics ของระบบ auto-result สำเร็จ'
        );
    }

    public function autoResultTestFetch(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'draw_id' => ['required', 'integer', 'exists:lotto_draws,id'],
            'expected_draw_date' => ['nullable', 'date_format:Y-m-d'],
        ])->validate();

        $runId = sprintf('admin_test_%s_%d', now()->format('YmdHisv'), (int) $validated['draw_id']);
        $params = [
            '--draw-id' => (int) $validated['draw_id'],
            '--limit' => 1,
            '--dry-run' => true,
            '--manual-retry' => true,
            '--run-id' => $runId,
        ];

        if (! empty($validated['expected_draw_date'])) {
            $params['--expected-draw-date'] = (string) $validated['expected_draw_date'];
        }

        $exitCode = Artisan::call('lotto:fetch-auto-results', $params);
        $output = trim((string) Artisan::output());

        if ($exitCode !== 0) {
            return $this->sendError('Dry-run Auto Result ไม่สำเร็จ: '.($output !== '' ? $output : 'command failed'), 500);
        }

        return $this->sendResponse([
            'run_id' => $runId,
            'draw_id' => (int) $validated['draw_id'],
            'mode' => 'dry_run',
            'command' => 'lotto:fetch-auto-results',
            'params' => $params,
            'output' => $output !== '' ? $output : null,
        ], 'ดำเนินการ Dry-run Auto Result เรียบร้อยแล้ว');
    }

    public function autoResultManualRetry(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'draw_id' => ['required', 'integer', 'exists:lotto_draws,id'],
            'expected_draw_date' => ['nullable', 'date_format:Y-m-d'],
        ])->validate();

        $runId = sprintf('admin_retry_%s_%d', now()->format('YmdHisv'), (int) $validated['draw_id']);
        $params = [
            '--draw-id' => (int) $validated['draw_id'],
            '--limit' => 1,
            '--manual-retry' => true,
            '--run-id' => $runId,
        ];

        if (! empty($validated['expected_draw_date'])) {
            $params['--expected-draw-date'] = (string) $validated['expected_draw_date'];
        }

        $exitCode = Artisan::call('lotto:fetch-auto-results', $params);
        $output = trim((string) Artisan::output());

        if ($exitCode !== 0) {
            return $this->sendError('Retry Auto Result ไม่สำเร็จ: '.($output !== '' ? $output : 'command failed'), 500);
        }

        return $this->sendResponse([
            'run_id' => $runId,
            'draw_id' => (int) $validated['draw_id'],
            'mode' => 'manual_retry',
            'command' => 'lotto:fetch-auto-results',
            'params' => $params,
            'output' => $output !== '' ? $output : null,
        ], 'ดำเนินการ Retry Auto Result เรียบร้อยแล้ว');
    }

    public function autoResultLogs(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'draw_id' => ['nullable', 'integer', 'exists:lotto_draws,id'],
            'run_id' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ])->validate();

        $limit = (int) ($validated['limit'] ?? 100);
        $query = LottoResultFetchLog::query()
            ->orderByDesc('id')
            ->limit($limit);

        if (! empty($validated['draw_id'])) {
            $query->where('draw_id', (int) $validated['draw_id']);
        }

        if (! empty($validated['run_id'])) {
            $query->where('run_id', (string) $validated['run_id']);
        }

        $items = $query->get()->map(static function (LottoResultFetchLog $log): array {
            return [
                'id' => (int) $log->id,
                'draw_id' => (int) ($log->draw_id ?? 0),
                'source_id' => $log->source_id !== null ? (int) $log->source_id : null,
                'attempt_no' => (int) ($log->attempt_no ?? 1),
                'status' => (string) $log->status,
                'pipeline_stage' => (string) ($log->pipeline_stage ?? ''),
                'run_id' => (string) ($log->run_id ?? ''),
                'request_url' => (string) ($log->request_url ?? ''),
                'request_meta_json' => $log->request_meta_json,
                'response_http_status' => $log->response_http_status,
                'response_body_preview' => $log->response_body !== null
                    ? mb_substr((string) $log->response_body, 0, 5000)
                    : null,
                'duration_ms' => $log->duration_ms,
                'error_message' => (string) ($log->error_message ?? ''),
                'is_dry_run' => (bool) ($log->is_dry_run ?? false),
                'is_manual_retry' => (bool) ($log->is_manual_retry ?? false),
                'parsed_payload_json' => $log->parsed_payload_json,
                'normalized_result_json' => $log->normalized_result_json,
                'selection_debug_json' => $log->selection_debug_json,
                'trace_json' => $log->trace_json,
                'created_at' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : null,
            ];
        })->values()->all();

        return $this->sendResponse([
            'items' => $items,
            'count' => count($items),
        ], 'ดึง fetch log สำเร็จ');
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
                $draw = $drawService->openDraw($draw, $this->manualTransitionContext('status_transition_open'));
            }

            if ($step === 'close') {
                $draw = $drawService->closeDraw($draw, $this->manualTransitionContext('status_transition_close'));
            }
        }
    }

    private function manualTransitionContext(string $reason): array
    {
        $admin = auth('admin')->user();

        return [
            'source' => DrawService::SOURCE_MANUAL,
            'actor_id' => $admin ? (int) ($admin->id ?? 0) : null,
            'actor_type' => 'admin',
            'reason' => $reason,
        ];
    }

    private function rulesForUpdateByStatus(LottoDraw $draw): array
    {
        $status = (string) $draw->status;

        if ($status === 'resulted') {
            return [];
        }

        if ($status === 'draft') {
            return [
                'market_id' => ['required', 'integer', 'exists:lotto_markets,id'],
                'draw_date' => ['required', 'date_format:Y-m-d'],
                'open_at' => ['required', 'date_format:Y-m-d H:i'],
                'close_at' => ['required', 'date_format:Y-m-d H:i'],
                'result_at' => ['nullable', 'date_format:Y-m-d H:i'],
            ];
        }

        if ($status === 'open') {
            $rules = [
                'draw_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
                'close_at' => ['sometimes', 'required', 'date_format:Y-m-d H:i'],
            ];

            if ($this->drawColumnExists('remark')) {
                $rules['remark'] = ['sometimes', 'nullable', 'string', 'max:1000'];
            }

            if ($this->drawColumnExists('display_name')) {
                $rules['display_name'] = ['sometimes', 'nullable', 'string', 'max:255'];
            }

            return $rules;
        }

        if ($status === 'closed') {
            $rules = [];

            if ($this->drawColumnExists('remark')) {
                $rules['remark'] = ['sometimes', 'nullable', 'string', 'max:1000'];
            }

            if ($this->drawColumnExists('display_name')) {
                $rules['display_name'] = ['sometimes', 'nullable', 'string', 'max:255'];
            }

            return $rules;
        }

        throw new InvalidArgumentException('สถานะงวดไม่ถูกต้อง');
    }

    private function buildUpdatePayloadByStatus(LottoDraw $draw, array $validated): array
    {
        $status = (string) $draw->status;

        if ($status === 'draft') {
            [$normalizedOpenAt, $normalizedCloseAt] = $this->normalizeOpenCloseWindow(
                (string) $validated['open_at'],
                (string) $validated['close_at']
            );
            $normalizedResultAt = $this->normalizeResultAtWithCloseAt(
                $validated['result_at'] ?? null,
                $normalizedCloseAt
            );

            return [
                'market_id' => (int) $validated['market_id'],
                'draw_date' => (string) $validated['draw_date'],
                'open_at' => $normalizedOpenAt,
                'close_at' => $normalizedCloseAt,
                'result_at' => $normalizedResultAt,
            ];
        }

        if ($status === 'open') {
            $payload = [];
            if (array_key_exists('draw_date', $validated)) {
                $payload['draw_date'] = (string) $validated['draw_date'];
            }

            if (array_key_exists('close_at', $validated)) {
                $payload['close_at'] = $this->normalizeCloseAtWithExistingOpenAt(
                    (string) $validated['close_at'],
                    $draw->open_at
                );
            }

            if ($this->drawColumnExists('remark') && array_key_exists('remark', $validated)) {
                $payload['remark'] = $validated['remark'];
            }

            if ($this->drawColumnExists('display_name') && array_key_exists('display_name', $validated)) {
                $payload['display_name'] = $validated['display_name'];
            }

            return $payload;
        }

        if ($status === 'closed') {
            $payload = [];

            if ($this->drawColumnExists('remark') && array_key_exists('remark', $validated)) {
                $payload['remark'] = $validated['remark'];
            }

            if ($this->drawColumnExists('display_name') && array_key_exists('display_name', $validated)) {
                $payload['display_name'] = $validated['display_name'];
            }

            return $payload;
        }

        return [];
    }

    private function drawColumnExists(string $column): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $allowedKeys
     */
    private function assertNoUnexpectedFields(array $payload, array $allowedKeys): void
    {
        $unexpected = array_diff(array_keys($payload), $allowedKeys);
        if ($unexpected !== []) {
            throw new InvalidArgumentException(
                'พบฟิลด์ที่ไม่อนุญาตให้แก้ไข: '.implode(', ', $unexpected)
            );
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

    /**
     * รองรับเคสข้ามวัน: ถ้า close_at น้อยกว่า open_at
     * จะตีความ close_at เป็นวันถัดไปจนกว่าจะมากกว่า open_at
     *
     * @return array{0:string,1:string}
     */
    private function normalizeOpenCloseWindow(string $openAtInput, string $closeAtInput): array
    {
        $timezone = (string) config('app.timezone', 'Asia/Bangkok');
        $openAt = Carbon::createFromFormat('Y-m-d H:i', $openAtInput, $timezone);
        $closeAt = Carbon::createFromFormat('Y-m-d H:i', $closeAtInput, $timezone);

        if ($closeAt->equalTo($openAt)) {
            throw new InvalidArgumentException('เวลาเปิดรับและเวลาปิดรับต้องไม่เท่ากัน');
        }

        while ($closeAt->lt($openAt)) {
            $closeAt = $closeAt->copy()->addDay();
        }

        return [
            $openAt->format('Y-m-d H:i:s'),
            $closeAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param  CarbonInterface|string|null  $openAt
     */
    private function normalizeCloseAtWithExistingOpenAt(string $closeAtInput, $openAt): string
    {
        $normalizedCloseAt = $this->normalizeDateTimeInput($closeAtInput);
        if ($normalizedCloseAt === null || ! $openAt) {
            return (string) $normalizedCloseAt;
        }

        $timezone = (string) config('app.timezone', 'Asia/Bangkok');
        $openAtCarbon = $openAt instanceof CarbonInterface
            ? $openAt->copy()->setTimezone($timezone)
            : Carbon::parse((string) $openAt, $timezone);
        $closeAtCarbon = Carbon::parse($normalizedCloseAt, $timezone);

        if ($closeAtCarbon->equalTo($openAtCarbon)) {
            throw new InvalidArgumentException('เวลาเปิดรับและเวลาปิดรับต้องไม่เท่ากัน');
        }

        while ($closeAtCarbon->lt($openAtCarbon)) {
            $closeAtCarbon = $closeAtCarbon->copy()->addDay();
        }

        return $closeAtCarbon->format('Y-m-d H:i:s');
    }

    private function normalizeResultAtWithCloseAt(?string $resultAtInput, ?string $closeAt): ?string
    {
        $normalizedResultAt = $this->normalizeDateTimeInput($resultAtInput);
        if ($normalizedResultAt === null || empty($closeAt)) {
            return $normalizedResultAt;
        }

        $timezone = (string) config('app.timezone', 'Asia/Bangkok');
        $closeAtCarbon = Carbon::parse($closeAt, $timezone);
        $resultAtCarbon = Carbon::parse($normalizedResultAt, $timezone);

        while ($resultAtCarbon->lt($closeAtCarbon)) {
            $resultAtCarbon = $resultAtCarbon->copy()->addDay();
        }

        return $resultAtCarbon->format('Y-m-d H:i:s');
    }

    private function canCancelAllRefundByDraw(LottoDraw $draw): bool
    {
        $status = (string) $draw->status;
        if ($status !== 'resulted') {
            return false;
        }

        $resultNumber = is_array($draw->result_number) ? $draw->result_number : [];

        $isNoResult = (bool) ($resultNumber['no_result'] ?? false)
            || (string) ($resultNumber['status'] ?? '') === 'no_result';

        if (! $isNoResult) {
            return false;
        }

        return ! (bool) ($resultNumber['manual_cancelled_all_tickets'] ?? false);
    }

    private function shouldAutoRefundOnNoResult(int $marketId): bool
    {
        $market = LotteryMarket::query()
            ->select(['id', 'auto_refund_on_no_result'])
            ->find($marketId);

        if (! $market instanceof LotteryMarket) {
            return false;
        }

        return (bool) ($market->auto_refund_on_no_result ?? false);
    }

    private function resolveYeekeeRoundNo(int $drawId): ?int
    {
        if (! Schema::hasTable('yeekee_rounds')) {
            return null;
        }

        $roundNo = YeekeeRound::query()
            ->where('lotto_draw_id', $drawId)
            ->orderByDesc('id')
            ->value('round_no');

        return $roundNo !== null ? (int) $roundNo : null;
    }

    private function canViewSensitiveYeekeeAudit(): bool
    {
        $checker = null;
        if (app()->bound('bouncer')) {
            $checker = app('bouncer');
        } elseif (function_exists('bouncer')) {
            $checker = bouncer();
        }

        if (! $checker || ! method_exists($checker, 'hasPermission')) {
            return false;
        }

        $permissions = [
            'lotto.yeekee.audit.view_sensitive',
        ];

        foreach ($permissions as $permission) {
            if ($checker->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string,mixed>  $resultNumber
     * @return array<string,mixed>
     */
    private function redactYeekeeSensitiveResultNumber(array $resultNumber): array
    {
        $safeKeys = [
            'top_3',
            'top_2',
            'bottom_2',
            'last_2_digits',
            'formula_label',
            'no_result',
            'status',
            'label',
            'no_result_reason',
            'manual_marked_no_result',
            'manual_cancelled_all_tickets',
        ];

        $safe = [];
        foreach ($safeKeys as $key) {
            if (array_key_exists($key, $resultNumber)) {
                $safe[$key] = $resultNumber[$key];
            }
        }

        $safe['_sensitive_redacted'] = true;

        return $safe;
    }
}
