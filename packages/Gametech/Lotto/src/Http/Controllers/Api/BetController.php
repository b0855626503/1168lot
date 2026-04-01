<?php

namespace Gametech\Lotto\Http\Controllers\Api;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Exceptions\LottoPackageException;
use Gametech\Lotto\Services\BetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BetController extends AppBaseController
{
    public function store(Request $request, BetService $betService): JsonResponse
    {
        $member = $request->user('customer');
        if (! $member || ! isset($member->code)) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $validated = validator($request->all(), [
            'draw_id' => ['required', 'integer', 'exists:lotto_draws,id'],
            'package_id' => ['required', 'integer', 'exists:lotto_group_packages,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.bet_type' => ['required', 'string'],
            'items.*.number' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:1'],
        ])->validate();

        try {
            $ticket = $betService->placeBet(
                (int) $member->code,
                (int) $validated['draw_id'],
                (int) $validated['package_id'],
                (array) $validated['items']
            );

            return $this->sendResponse([
                'ticket_id' => (int) $ticket->id,
                'total_amount' => (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0),
                'total_bet_amount' => (float) ($ticket->total_bet_amount ?? 0),
                'total_discount_amount' => (float) ($ticket->total_discount_amount ?? 0),
                'total_net_amount' => (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0),
                'total_win_amount' => (float) ($ticket->total_win_amount ?? 0),
                'status' => (string) $ticket->status,
                'item_count' => $ticket->items->count(),
            ], 'แทงหวยสำเร็จ');
        } catch (LottoPackageException $exception) {
            return $this->sendResponseFail([
                'error_code' => $exception->errorCode(),
            ], $exception->getMessage(), $exception->httpStatus());
        } catch (\Throwable $exception) {
            return $this->sendError($exception->getMessage(), 422);
        }
    }
}
