<?php

namespace Gametech\Wallet\Http\Controllers;

use App\Services\Online\MemberOnlineService;
use Illuminate\Http\JsonResponse;

class OnlineHeartbeatController extends Controller
{
    public function __invoke(MemberOnlineService $memberOnlineService): JsonResponse
    {
        $member = auth()->guard('customer')->user();

        if (!$member || !isset($member->code)) {
            return response()->json(['ok' => false], 401);
        }

        $memberOnlineService->touch((int) $member->code);

        return response()->json(['ok' => true]);
    }
}
