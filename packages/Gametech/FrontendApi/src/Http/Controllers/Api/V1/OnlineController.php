<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use App\Services\Online\MemberOnlineService;
use Illuminate\Http\JsonResponse;

class OnlineController extends BaseController
{
    public function heartbeat(MemberOnlineService $memberOnlineService): JsonResponse
    {
        $member = auth()->guard('customer')->user();
        if (! $member || ! isset($member->code)) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $memberOnlineService->touch((int) $member->code);

        return $this->sendResponseNew([
            'heartbeat' => 'ok',
            'online' => $memberOnlineService->countActive(),
        ], 'อัปเดตสถานะออนไลน์สำเร็จ');
    }

    public function count(MemberOnlineService $memberOnlineService): JsonResponse
    {
        return $this->sendResponseNew([
            'online' => $memberOnlineService->countActive(),
        ], 'ดึงจำนวนสมาชิกออนไลน์สำเร็จ');
    }
}
