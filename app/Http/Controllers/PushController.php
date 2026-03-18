<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;


class PushController extends Controller
{
    public function __construct()
    {
        // บังคับให้เป็นลูกค้าที่ล็อกอินด้วย guard(customer) เท่านั้น
        $this->middleware('auth:customer')->only(['subscribe', 'unsubscribe']);
    }

    /** สมัคร (บันทึก/อัปเดต) Push Subscription ให้ customer ปัจจุบัน */
    public function subscribe(Request $req)
    {
        $data = $req->all();
        $endpoint = $data['endpoint'] ?? null;
        $keys = $data['keys'] ?? [];

        if (!$endpoint || !isset($keys['p256dh'], $keys['auth'])) {
            return response()->json(['ok' => false, 'msg' => 'invalid subscription'], 422);
        }

        $customer = $req->user('customer'); // <-- ใช้ guard customer
        $userId = $customer->code;

        // อนุญาตให้ endpoint เดิม “ย้ายเจ้าของ” ได้กรณีสลับบัญชีในเครื่องเดียวกัน
        PushSubscription::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'user_id' => $userId,
                'p256dh' => $keys['p256dh'],
                'auth' => $keys['auth'],
            ]
        );

        return ['ok' => true];
    }

    /** ยกเลิก subscription ของ customer ปัจจุบัน (กันลบของคนอื่น) */
    public function unsubscribe(Request $req)
    {
        $endpoint = $req->input('endpoint');
        if ($endpoint) {
            PushSubscription::where('endpoint', $endpoint)
                ->where('user_id', $req->user('customer')->code) // <-- bound กับ customer ปัจจุบัน
                ->delete();
        }
        return ['ok' => true];
    }

    /** ส่งทดสอบถึง subscriptions ของ customer ปัจจุบัน */

    public function test(Request $req)
    {
        // ===== 1) รับพารามิเตอร์แบบยืดหยุ่น =====
        $data = $req->validate([
            // เลือก target ได้ 3 ทาง: endpoint เดียว / user_id เดียว / broadcast ทั้งหมด
            'endpoint' => 'nullable|string',
            'user_id' => 'nullable|string',   // NOTE: ต้องใส่ค่าให้ตรงกับคอลัมน์ user_id ในตาราง (จะเป็น code หรือ id ก็ได้)
            'broadcast' => 'nullable|boolean',  // =1 เพื่อยิงทุกคน (ควรใช้เฉพาะแอดมิน)
            'limit' => 'nullable|integer|min:1|max:5000', // กันยิงทีละเยอะเกิน
            'dry_run' => 'nullable|boolean',  // =1 แค่ลองเลือกกลุ่มเป้าหมาย แต่ไม่ส่งจริง

            // payload
            'title' => 'sometimes|string|max:120',
            'body' => 'sometimes|string|max:500',
            'url' => 'sometimes|string|max:512',
            'icon' => 'sometimes|string|max:512',
            'badge' => 'sometimes|string|max:512',
        ]);

        // ค่าดีฟอลต์ payload
        $payload = [
            'title' => $req->input('title', 'ทดสอบแจ้งเตือน'),
            'body' => $req->input('body', 'ฮัลโหลจากเซิร์ฟเวอร์ 👋'),
            'url' => $req->input('url', '/'),
            'icon' => $req->input('icon', '/icons/icon-192.png'),
            'badge' => $req->input('badge', '/icons/badge-72.png'),
        ];

        // ===== 2) เลือกกลุ่มเป้าหมาย =====
        $query = PushSubscription::query();
        $targetBy = null;

        if (!empty($data['endpoint'])) {
            // ยิง endpoint เดียว
            $query->where('endpoint', $data['endpoint']);
            $targetBy = 'endpoint';
        } elseif (!empty($data['user_id'])) {
            // ยิงตาม user_id ที่ส่งมา (ให้ตรงกับที่คุณเก็บในตาราง push_subscriptions.user_id
            // ถ้าคุณเก็บ customer->code ก็ส่ง code; ถ้าเก็บ numeric id ก็ส่ง id)
            $query->where('user_id', $data['user_id']);
            $targetBy = 'user_id';
        } elseif (!empty($data['broadcast'])) {
            // ยิงทุกคน (ระวัง!)
            $targetBy = 'broadcast';
        } else {
            // ดีฟอลต์: ถ้าล็อกอินอยู่ ให้ยิงเฉพาะลูกค้าคนที่ล็อกอิน; ถ้าไม่ล็อกอิน → บังคับให้ระบุอย่างใดอย่างหนึ่ง
            $authCustomer = $req->user('customer');
            if ($authCustomer) {
                // NOTE: ถ้าคุณบันทึก user_id เป็น "code" ให้ใช้ $authCustomer->code
                // ถ้าบันทึกเป็น id ให้สลับมาใช้ $authCustomer->id
                $query->where('user_id', $authCustomer->code);
                $targetBy = 'auth_customer';
            } else {
                return response()->json([
                    'ok' => false,
                    'msg' => 'กรุณาระบุ endpoint หรือ user_id หรือส่ง broadcast=1 (หรือทำการล็อกอิน customer ก่อน)',
                ], 422);
            }
        }

        // ป้องกันยิงครั้งละมากไป โดยเฉพาะ broadcast
        $limit = (int)($data['limit'] ?? 1000);
        $subs = $query->limit($limit)->get();

        if ($subs->isEmpty()) {
            return response()->json(['ok' => false, 'msg' => 'no subscriptions'], 404);
        }

        // dry run: แค่รายงานจำนวน/ตัวอย่าง ไม่ส่งจริง
        if (!empty($data['dry_run'])) {
            return response()->json([
                'ok' => true,
                'dry_run' => true,
                'target' => $targetBy,
                'count' => $subs->count(),
                'sample' => $subs->take(3)->map(fn($s) => [
                    'endpoint' => $s->endpoint,
                    'user_id' => $s->user_id,
                ])->values(),
                'payload' => $payload,
            ]);
        }

        // ===== 3) ตั้งค่า VAPID & ส่งจริง =====
        $auth = [
            'VAPID' => [
                'subject' => config('app.url') ?: env('VAPID_SUBJECT', 'mailto:admin@example.com'),
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ];

        $webPush = new WebPush($auth);
        $webPush->setAutomaticPadding(0);

        // ตัวเลือกส่ง (optional)
        $options = [
            'TTL' => 60,        // อยู่ในคิว push service กี่วินาที
            'urgency' => 'normal',  // 'very-low'|'low'|'normal'|'high'
        ];

        foreach ($subs as $s) {
            $sub = Subscription::create([
                'endpoint' => $s->endpoint,
                'keys' => ['p256dh' => $s->p256dh, 'auth' => $s->auth],
            ]);
            $webPush->queueNotification($sub, json_encode($payload), $options);
        }

        $ok = 0;
        $fail = 0;
        $pruned = 0;
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $ok++;
            } else {
                $fail++;
                $status = $report->getResponse()?->getStatusCode();
                // ลบ subscription ตายทิ้ง
                if (in_array($status, [404, 410])) {
                    $endpoint = method_exists($report, 'getEndpoint')
                        ? $report->getEndpoint()
                        : ($report->getRequest()?->getUri() ?? null);
                    if ($endpoint) {
                        $pruned += PushSubscription::where('endpoint', (string)$endpoint)->delete();
                    }
                }
            }
        }

        return [
            'ok' => true,
            'target' => $targetBy,
            'count' => $subs->count(),
            'sent' => $ok,
            'failed' => $fail,
            'pruned' => $pruned,
            'payload' => $payload,
        ];
    }


}
