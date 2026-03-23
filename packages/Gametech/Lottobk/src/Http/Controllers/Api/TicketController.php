<?php

namespace Gametech\Lotto\Http\Controllers\Api;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoNumberExposure;
use Gametech\Lotto\Models\LottoTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $memberId = $this->resolveMemberId($request);
        if (! $memberId) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $tickets = $this->ticketQuery($memberId)
            ->orderByDesc('id')
            ->limit($this->resolveLimit($request))
            ->get();

        $payload = $tickets->map(fn (LottoTicket $ticket) => $this->mapTicketSummary($ticket))->values();

        return $this->sendResponse($payload, 'ดึงประวัติโพยสำเร็จ');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $memberId = $this->resolveMemberId($request);
        if (! $memberId) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $ticket = $this->ticketQuery($memberId)
            ->find($id);

        if (! $ticket) {
            return $this->sendError('ไม่พบโพยที่ระบุ', 404);
        }

        $payload = $this->mapTicketSummary($ticket);
        $payload['items'] = $ticket->items->map(static function ($item): array {
            return [
                'bet_type' => (string) $item->bet_type,
                'bet_type_label' => BetType::label((string) $item->bet_type),
                'number' => (string) $item->number,
                'amount' => (float) $item->amount,
                'payout_at_time' => (float) $item->payout_at_time,
                'result_status' => $item->result_status,
                'win_amount' => (float) ($item->win_amount ?? 0),
            ];
        })->values();

        return $this->sendResponse($payload, 'ดึงรายละเอียดโพยสำเร็จ');
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $memberId = $this->resolveMemberId($request);
        if (! $memberId) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        try {
            DB::transaction(function () use ($id, $memberId): void {
                $ticket = $this->ticketQuery($memberId)
                    ->with(['draw', 'items'])
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ((string) $ticket->status !== 'active') {
                    throw new \RuntimeException('โพยนี้ไม่สามารถยกเลิกได้');
                }

                if ((string) ($ticket->draw->status ?? '') !== 'open') {
                    throw new \RuntimeException('ยกเลิกได้เฉพาะโพยที่อยู่ในงวดเปิดรับเท่านั้น');
                }

                foreach ($ticket->items as $item) {
                    $exposure = LottoNumberExposure::query()
                        ->where('draw_id', (int) $ticket->draw_id)
                        ->where('bet_type', (string) $item->bet_type)
                        ->where('number', (string) $item->number)
                        ->lockForUpdate()
                        ->first();

                    if (! $exposure) {
                        continue;
                    }

                    $nextAmount = max(0, (float) $exposure->sold_amount - (float) $item->amount);
                    $exposure->update(['sold_amount' => $nextAmount]);
                }

                $ticket->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'refund_amount' => (float) $ticket->total_amount,
                ]);
            });

            return $this->sendSuccess('ยกเลิกโพยสำเร็จ');
        } catch (\RuntimeException $exception) {
            return $this->sendError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            return $this->sendError('ยกเลิกโพยไม่สำเร็จ', 500);
        }
    }

    private function resolveMemberId(Request $request): ?int
    {
        $member = $request->user('customer');

        if (! $member || ! isset($member->code)) {
            return null;
        }

        return (int) $member->code;
    }

    private function resolveLimit(Request $request): int
    {
        return max(1, min((int) $request->input('limit', 20), 100));
    }

    private function ticketQuery(int $memberId)
    {
        return LottoTicket::query()
            ->with(['draw.market', 'items'])
            ->where('member_id', $memberId);
    }

    private function mapTicketSummary(LottoTicket $ticket): array
    {
        return [
            'id' => (int) $ticket->id,
            'draw_id' => (int) $ticket->draw_id,
            'draw_date' => optional($ticket->draw?->draw_date)->toDateString(),
            'market_name' => $ticket->draw?->market?->name,
            'status' => (string) $ticket->status,
            'total_amount' => (float) $ticket->total_amount,
            'created_at' => optional($ticket->created_at)->toDateTimeString(),
        ];
    }
}

