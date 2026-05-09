<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoResultCorrectionDataTable;
use Gametech\Lotto\Models\LottoResultCorrection;
use Gametech\Lotto\Models\LottoResultCorrectionItem;
use Gametech\Lotto\Services\ResultCorrectionRetryDebitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LottoResultCorrectionReportController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoResultCorrectionDataTable $dataTable)
    {
        if (! bouncer()->hasPermission('lotto_result_corrections.view')
            && ! bouncer()->hasPermission('lotto_reports.result_corrections')) {
            abort(403);
        }

        return $dataTable->render($this->_config['view']);
    }

    public function loadData(): JsonResponse
    {
        if (! bouncer()->hasPermission('lotto_result_corrections.view')
            && ! bouncer()->hasPermission('lotto_reports.result_corrections')) {
            return $this->sendError('ไม่มีสิทธิ์ดูรายงานแก้ไขผลหวย', 403);
        }

        $rows = LottoResultCorrection::query()
            ->orderByDesc('id')
            ->limit(100)
            ->get([
                'id',
                'draw_id',
                'old_result_number',
                'new_result_number',
                'reason',
                'created_by',
                'affected_ticket_count',
                'total_reversed_amount',
                'total_reverse_failed_amount',
                'total_new_payout_amount',
                'status',
                'created_at',
            ]);

        return $this->sendResponse($rows->toArray(), 'โหลดรายงานแก้ไขผลหวยสำเร็จ');
    }

    public function show(int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('lotto_result_corrections.view_detail')) {
            return $this->sendError('ไม่มีสิทธิ์ดูรายละเอียดรายงานแก้ไขผลหวย', 403);
        }

        $correction = LottoResultCorrection::query()->find($id);
        if (! $correction instanceof LottoResultCorrection) {
            return $this->sendError('ไม่พบข้อมูล correction', 404);
        }

        $rawItems = LottoResultCorrectionItem::query()
            ->where('correction_id', (int) $correction->id)
            ->orderBy('id')
            ->get();

        $ticketMemberMap = DB::table('lotto_tickets')
            ->whereIn('id', $rawItems->pluck('ticket_id')->unique()->values()->all())
            ->pluck('member_id', 'id');

        $normalizedItems = $rawItems->map(function (LottoResultCorrectionItem $item) use ($ticketMemberMap): LottoResultCorrectionItem {
            $ticketMemberId = (int) ($ticketMemberMap[(int) $item->ticket_id] ?? 0);
            if ($ticketMemberId > 0) {
                $item->member_id = $ticketMemberId;
            }

            return $item;
        });

        $memberIds = $normalizedItems->pluck('member_id')->unique()->values()->all();
        $memberSelectColumns = ['code', 'name', 'balance'];
        if (DB::getSchemaBuilder()->hasColumn('members', 'user_name')) {
            $memberSelectColumns[] = 'user_name';
        }
        if (DB::getSchemaBuilder()->hasColumn('members', 'username')) {
            $memberSelectColumns[] = 'username';
        }
        if (DB::getSchemaBuilder()->hasColumn('members', 'tel')) {
            $memberSelectColumns[] = 'tel';
        }
        if (DB::getSchemaBuilder()->hasColumn('members', 'phone')) {
            $memberSelectColumns[] = 'phone';
        }

        $memberRecords = DB::table('members')
            ->whereIn('code', $memberIds)
            ->get($memberSelectColumns);
        $memberRecordsByCode = $memberRecords
            ->filter(static fn ($row): bool => isset($row->code))
            ->keyBy('code');

        $items = $normalizedItems
            ->groupBy('member_id')
            ->map(function ($memberItems, $memberId) use ($memberRecordsByCode): array {
                $ticketCount = $memberItems->pluck('ticket_id')->unique()->count();
                $reverseRequiredAmount = round((float) $memberItems->sum('reverse_required_amount'), 2);
                $reverseDebitedAmount = round((float) $memberItems->sum('reverse_debited_amount'), 2);
                $reverseRemainingAmount = round((float) $memberItems->sum('reverse_remaining_amount'), 2);
                $newCreditAmount = round((float) $memberItems->sum('new_credit_amount'), 2);

                $status = 'completed';
                if ($reverseRemainingAmount > 0) {
                    $status = 'remaining';
                } elseif ($memberItems->contains(static fn (LottoResultCorrectionItem $row): bool => (string) $row->status === 'failed')) {
                    $status = 'failed';
                } elseif ($memberItems->contains(static fn (LottoResultCorrectionItem $row): bool => (string) $row->status === 'credited')) {
                    $status = 'credited';
                }

                $memberRecord = $memberRecordsByCode[(int) $memberId] ?? null;

                return [
                    'id' => (int) $memberId,
                    'member_id' => (int) $memberId,
                    'member_username' => (string) ($memberRecord->user_name
                        ?? $memberRecord->username
                        ?? $memberRecord->tel
                        ?? $memberRecord->phone
                        ?? $memberRecord->name
                        ?? (string) $memberId),
                    'ticket_count' => $ticketCount,
                    'reverse_required_amount' => $reverseRequiredAmount,
                    'reverse_debited_amount' => $reverseDebitedAmount,
                    'reverse_remaining_amount' => $reverseRemainingAmount,
                    'new_credit_amount' => $newCreditAmount,
                    'latest_member_balance' => round((float) ($memberRecord->balance ?? 0), 2),
                    'status' => $status,
                ];
            })
            ->sortByDesc('reverse_remaining_amount')
            ->values()
            ->all();

        $summary = [
            'deducted_count' => collect($items)
                ->filter(static fn (array $item): bool => (float) ($item['reverse_debited_amount'] ?? 0) > 0)
                ->count(),
            'remaining_count' => collect($items)
                ->filter(static fn (array $item): bool => (float) ($item['reverse_remaining_amount'] ?? 0) > 0)
                ->count(),
            'completed_count' => collect($items)
                ->filter(static fn (array $item): bool => (float) ($item['reverse_remaining_amount'] ?? 0) <= 0)
                ->count(),
        ];

        $canRetryDebit = bouncer()->hasPermission('lotto_result_corrections.debit_remaining');

        return $this->sendResponse([
            'id' => (int) $correction->id,
            'draw_id' => (int) $correction->draw_id,
            'status' => (string) $correction->status,
            'summary' => $summary,
            'can_retry_debit' => $canRetryDebit,
            'items' => $items,
        ], 'โหลดรายละเอียด correction สำเร็จ');
    }

    public function retryDebit(int $id, ResultCorrectionRetryDebitService $retryDebitService): JsonResponse
    {
        if (! bouncer()->hasPermission('lotto_result_corrections.debit_remaining')) {
            return $this->sendError('ไม่มีสิทธิ์หักเครดิตเพิ่ม', 403);
        }

        $correction = LottoResultCorrection::query()->find($id);
        if (! $correction instanceof LottoResultCorrection) {
            return $this->sendError('ไม่พบข้อมูล correction', 404);
        }

        try {
            $result = $retryDebitService->retryRemaining(
                (int) $correction->id,
                request('item_id') ? (int) request('item_id') : null,
                auth()->id() ? (int) auth()->id() : null,
                request('member_id') ? (int) request('member_id') : null
            );

            return $this->sendResponse($result, 'หักเครดิตเพิ่มสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('หักเครดิตเพิ่มไม่สำเร็จ: '.$e->getMessage(), 500);
        }
    }
}
