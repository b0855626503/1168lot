<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Validator;

class RealtimeController extends BaseController
{
    public function config(): JsonResponse
    {
        $pusher = (array) config('broadcasting.connections.pusher', []);
        $options = (array) ($pusher['options'] ?? []);

        return $this->sendResponseNew([
            'realtime' => [
                'broadcaster' => 'pusher',
                'key' => (string) ($pusher['key'] ?? ''),
                'ws_host' => (string) ($options['host'] ?? request()->getHost()),
                'ws_port' => (int) ($options['port'] ?? 6001),
                'ws_path' => '',
                'ws_scheme' => (string) ($options['scheme'] ?? 'http'),
                'force_tls' => (bool) ($options['useTLS'] ?? false),
                'shared_member_channel' => (string) config('app.name') . '_members',
                'private_channel_member_template' => (string) config('app.name') . '_members.{member_code}',
                'events' => [
                    'public.activity.updated',
                    'member.activity.updated',
                    'member.balance.updated',
                ],
            ],
        ], 'ดึงข้อมูล realtime config สำเร็จ');
    }

    public function memberContext(Request $request): JsonResponse
    {
        $member = auth()->guard('customer')->user();
        if (! $member || ! isset($member->code)) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        return $this->sendResponseNew([
            'member_code' => (int) $member->code,
            'private_channel' => (string) config('app.name') . '_members.' . (int) $member->code,
        ], 'ดึง realtime member context สำเร็จ');
    }

    public function authenticate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'socket_id' => 'required|string',
            'channel_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('ข้อมูลไม่ครบถ้วน', 422);
        }

        $member = auth()->guard('customer')->user();
        if (! $member || ! isset($member->code)) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $channelName = (string) $request->input('channel_name');
        $expectedPrefix = (string) config('app.name') . '_members.';
        if (str_starts_with($channelName, 'private-')) {
            $channelName = substr($channelName, 8);
        }

        if (str_starts_with($channelName, $expectedPrefix)) {
            $channelMember = (int) substr($channelName, strlen($expectedPrefix));
            if ($channelMember > 0 && $channelMember !== (int) $member->code) {
                return $this->sendError('ไม่มีสิทธิ์เข้าถึง channel นี้', 403);
            }
        }

        $response = Broadcast::auth($request);
        $payload = json_decode((string) $response->getContent(), true);
        if (! is_array($payload)) {
            $payload = [];
        }

        return response()->json($payload, $response->getStatusCode());
    }
}
