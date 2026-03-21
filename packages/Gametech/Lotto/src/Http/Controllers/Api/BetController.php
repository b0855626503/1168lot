<?php

namespace Gametech\Lotto\Http\Controllers\Api;

use Gametech\Admin\Http\Controllers\AppBaseController;
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.bet_type' => ['required', 'string'],
            'items.*.number' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:1'],
        ])->validate();

        try {
            $ticket = $betService->placeBet(
                (int) $member->code,
                (int) $validated['draw_id'],
                (array) $validated['items']
            );

            return $this->sendResponse([
                'ticket_id' => (int) $ticket->id,
                'total_amount' => (float) $ticket->total_amount,
                'status' => (string) $ticket->status,
                'item_count' => $ticket->items->count(),
            ], 'แทงหวยสำเร็จ');
        } catch (\Throwable $exception) {
            return $this->sendError($exception->getMessage(), 422);
        }
    }
}

