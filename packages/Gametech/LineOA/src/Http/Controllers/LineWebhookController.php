<?php

namespace Gametech\LineOA\Http\Controllers;

use Gametech\LineOA\Models\LineAccount;
use Gametech\LineOA\Models\LineWebhookLog;
use Gametech\LineOA\Services\LineWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LineWebhookController extends Controller
{
    /**
     * Handle incoming webhook from LINE.
     *
     * URL example:
     *   https://api.xxx.com/api/line-oa/webhook/{token}
     *
     * @param  string  $token  webhook_token ที่ map กับ line_accounts.webhook_token
     */
    public function handle(Request $request, string $token, LineWebhookService $service): JsonResponse
    {
        // 1) หา OA จาก webhook_token
        /** @var LineAccount|null $account */
        $account = LineAccount::query()
            ->where('webhook_token', $token)
            ->where('status', 'active')
            ->first();

        // 2) เตรียม raw data สำหรับ log
        $rawBody = $request->getContent();
        $json = json_decode($rawBody, true) ?: [];
        $headers = $request->headers->all();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $requestId = (string) Str::uuid();

        // 3) เดา event_type / event_id จาก event แรก (ถ้ามี)
        $firstEvent = $json['events'][0] ?? null;
        $eventType = $firstEvent['type'] ?? null;

        // LINE v2 ใช้ field ต่างกันบ้าง ลองดึงจาก message.id หรือ webhookEventId
        $eventId = $firstEvent['message']['id']
            ?? ($firstEvent['webhookEventId'] ?? null);

        // 4) สร้าง log record ก่อนประมวลผล
        /** @var LineWebhookLog $log */
        $log = LineWebhookLog::create([
            'line_account_id' => $account?->id,
            'event_type' => $eventType,
            'event_id' => $eventId,
            'request_id' => $requestId,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'headers' => $headers,
            'body' => $rawBody,
            'http_status' => null,
            'is_processed' => false,
            'processed_at' => null,
            'error_message' => null,
        ]);

        // 5) ถ้าไม่เจอ account ให้ตอบ 404 (LINE verify จะขึ้น error → ช่วยให้รู้ว่า token ผิด)
        if (! $account) {
            $message = 'LINE Account not found for given webhook token.';

            $log->update([
                'http_status' => 404,
                'is_processed' => false,
                'error_message' => $message,
            ]);

            Log::channel('line_oa')->warning('[LineWebhook] invalid webhook token', [
                'token' => $token,
                'request_id' => $requestId,
            ]);

            return response()->json([
                'message' => $message,
            ], 404);
        }

        // 6) ประมวลผล payload จริง ๆ
        try {
            $service->handle($account, $json, $log);

            $log->update([
                'http_status' => 200,
                'is_processed' => true,
                'processed_at' => now(),
            ]);

            // LINE ต้องการ 200 OK ถ้ารับ payload ได้
            return response()->json([
                'message' => 'OK',
            ], 200);
        } catch (\Throwable $e) {
            // กันไม่ให้ error ภายในทำให้ LINE ยิงซ้ำซ้อนเกินไป
            $log->update([
                'http_status' => 500,
                'is_processed' => false,
                'error_message' => $e->getMessage(),
            ]);

            Log::channel('line_oa')->error('[LineWebhook] error while processing payload', [
                'account_id' => $account->id,
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            // ส่วนใหญ่ production จะตอบ 200 กลับ LINE เพื่อป้องกัน retry loop
            return response()->json([
                'message' => 'ERROR',
            ], 200);
        }
    }
}
