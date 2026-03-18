<?php

namespace Gametech\Sms\Http\Controllers;


use Gametech\Sms\Services\DeliveryReceiptService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class VonageDeliveryReceiptController extends Controller
{
    public function handle(Request $request, DeliveryReceiptService $service)
    {
        // Vonage DLR example payload fields: msisdn,to,network-code,messageId,price,status,scts,err-code,api-key,message-timestamp :contentReference[oaicite:9]{index=9}
        $payload = $request->all();

        // บางครั้ง field เป็น JSON body; บางครั้งเป็น form/query
        $messageId = (string) Arr::get($payload, 'messageId', Arr::get($payload, 'message-id'));
        $status    = (string) Arr::get($payload, 'status');
        $errCode   = (string) Arr::get($payload, 'err-code', Arr::get($payload, 'err_code'));

        if ($messageId === '' || $status === '') {
            // ต้องตอบ 2xx เพื่อไม่ให้ Vonage retry รัว ๆ แต่เราจะ log ไว้
            Log::channel('sms')->warning('[VonageDLR] missing required fields', [
                'payload' => $payload,
            ]);

            return response()->json(['ok' => true], 200);
        }

        $result = $service->handleVonageDlr($payload);

        // Always 200/204 to stop retries (unless you explicitly want retries)
        return response()->json([
            'ok' => true,
            'result' => $result,
        ], 200);
    }
}
