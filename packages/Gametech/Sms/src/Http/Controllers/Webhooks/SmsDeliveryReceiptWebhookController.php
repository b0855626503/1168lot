<?php

namespace Gametech\Sms\Http\Controllers\Webhooks;

use Gametech\Sms\Services\DeliveryReceiptService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class SmsDeliveryReceiptWebhookController extends Controller
{
    /**
     * DLR endpoint (provider agnostic)
     * URL: /api/sms/webhook/{provider}/dlr
     */
    public function dlr(string $provider, Request $request, DeliveryReceiptService $service)
    {
        // Vonage (SMS API) มักเป็น GET query string, บางเจ้าจะเป็น POST form/json
        $payload = $request->all();
        Log::channel('sms')->warning('payload', [
            'payload' => $payload
        ]);
        // กัน provider แปลก ๆ เข้ามามั่ว (แต่ยังตอบ 200 กัน retry รัว)
        $provider = strtolower(trim($provider));

        if (! config("sms.providers.$provider")) {
            Log::channel('sms')->warning('[SmsWebhook][DLR] unknown provider', [
                'provider' => $provider,
                'url'      => $request->fullUrl(),
            ]);

            return response()->json(['ok' => true], 200);
        }

        try {
            $result = $service->handleProviderDlr($provider, $payload);

            // ตอบ 200 เสมอ เพื่อหยุด retry (คุณจะ debug จาก log + db)
            return response()->json([
                'ok'     => true,
                'result' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::channel('sms')->error('[SmsWebhook][DLR] unhandled exception', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            // ยังตอบ 200 เพื่อไม่ให้ provider ยิงซ้ำรัวจนระบบคุณร่วง
            return response()->json(['ok' => true], 200);
        }
    }

    // public function inbound(string $provider, Request $request, InboundService $service) { ... }
}
