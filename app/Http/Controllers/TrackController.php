<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class TrackController extends Controller
{
    // ไม่ต้อง auth — endpoints สาธารณะ
    public function __construct() {
        $this->middleware('auth')->except(['presence', 'event']);
    }

    /**
     * POST /api/track/presence
     * payload ตัวอย่าง:
     * {"client_id":"<uuid>","mode":"web","display_mode":"browser","sw":false,"stage":"leave","path":"/member","user_id":null,"code":1}
     */
    public function presence(Request $req)
    {
        $data = $req->validate([
            'client_id'    => 'required|string|max:64',
            'mode'         => 'nullable|in:pwa,web',
            'display_mode' => 'nullable|string|max:32',
            'sw'           => 'nullable|boolean',
            'stage'        => 'nullable|in:enter,heartbeat,leave',
            'path'         => 'nullable|string|max:255',
            'user_id'      => 'nullable',
            'code'         => 'nullable',
        ]);

        // map code (เลข) => user_id
        $userId = $data['user_id'] ?? $data['code'] ?? null;
        $userId = is_numeric($userId) ? (int) $userId : null;

        $now = now();

        // upsert ตาม UNIQUE (user_id, client_id)
        DB::table('client_presence')->upsert(
            [[
                'user_id'       => $userId,                    // <- map แล้ว
                'client_id'     => $data['client_id'],
                'mode'          => $data['mode'] ?? 'web',
                'display_mode'  => $data['display_mode'] ?? null,
                'sw'            => (bool) ($data['sw'] ?? false),
                'ua'            => Str::limit($req->userAgent() ?? '', 191, ''),
                'last_path'     => $data['path'] ?? null,
                'first_seen_at' => $now,
                'last_seen_at'  => $now,
            ]],
            // keys (ตามสคีมา): UNIQUE(user_id, client_id)
            ['user_id', 'client_id'],        // :contentReference[oaicite:2]{index=2}
            // update columns
            ['mode','display_mode','sw','ua','last_path','last_seen_at']
        );

        try {
            $name  = $this->stageToName($data['stage'] ?? null, 'presence');
            $props = array_filter([
                'display_mode' => $data['display_mode'] ?? null,
                'sw'           => isset($data['sw']) ? (bool) $data['sw'] : null,
                'path'         => $data['path'] ?? null,
                'user_code'    => $userId,
                'cf_ipcountry' => $req->headers->get('cf-ipcountry'),
                'cf_ray'       => $req->headers->get('cf-ray'),
            ], fn ($v) => !is_null($v));

            DB::table('client_mode_events')->insert([
                // ตารางนี้คาดว่า user_id เป็น numeric → แปลงเฉพาะกรณีเป็นตัวเลข
                'user_id'    => is_numeric($userId) ? (int) $userId : null,
                'mode'       => $data['mode'],
                'name'       => $name,
                'props'      => $props ? json_encode($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'client_id'  => $data['client_id'],
                'ua'         => Str::limit($req->userAgent(), 255, ''),
                'ip'         => Str::limit($req->headers->get('cf-connecting-ip') ?: $req->ip(), 45, ''),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // อย่าให้ presence ล้มเพราะ table/client_mode_events มีปัญหา
        }

        // จบแบบเงียบ ๆ
        return response()->json(['ok' => true], 200);
    }

    /**
     * POST /api/track/event
     * payload ตอนนี้หน้าตาเหมือน presence:
     * {"client_id":"<uuid>","mode":"web","display_mode":"browser","sw":false,"stage":"leave","path":"/member","user_id":null,"code":1}
     * ⇒ เราจะ derive name จาก stage เพื่อผ่าน NOT NULL (name) ใน client_mode_events
     */
    public function event(Request $req)
    {

        $data = $req->validate([
            'name'        => 'required|string|max:64',
            'mode'        => 'required|in:pwa,web',
            'props'       => 'nullable|array',

            'client_id'   => 'nullable|string|max:64',
            'cid'   => 'nullable|string|max:64',
            // optional user fields
            'user_id'     => 'nullable',
            'code'     => 'nullable',
        ]);

        $userId = $data['code'] ?? null;
        $userId = is_numeric($userId) ? (int) $userId : null;

        // สร้าง name จาก stage (ถ้าไม่มีก็ใช้ page_ping)
        $name = match ($data['stage'] ?? null) {
            'enter'     => 'presence_enter',
            'heartbeat' => 'presence_heartbeat',
            'leave'     => 'presence_leave',
            default     => 'page_ping',
        };

        // เก็บ props เป็น JSON ตาม schema client_mode_events.props (JSON) และต้องมี name NOT NULL
        // (ตารางนี้กำหนด name VARCHAR(64) NOT NULL, props เป็น JSON valid) :contentReference[oaicite:3]{index=3}
        $props = [
            'display_mode' => $data['display_mode'] ?? null,
            'sw'           => (bool) ($data['sw'] ?? false),
            'path'         => $data['path'] ?? null,
        ];

        DB::table('client_mode_events')->insert([
            'user_id'    => $userId,
            'mode'       => $data['mode'] ?? 'web',
            'name'       => $name,                                // <- ต้องมีเสมอ
            'props'      => json_encode($props, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'client_id'  => $data['client_id'] ?? ($data['cid'] ?? null),
            'ua'         => Str::limit($req->userAgent() ?? '', 191, ''),
            'ip'         => Str::limit($req->headers->get('cf-connecting-ip') ?: $req->ip(), 45, ''),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true], 200);
    }



    private function stageToName(?string $stage, string $fallback = 'event'): string
    {
        $map = [
            'heartbeat' => 'presence_heartbeat',
            'enter'     => 'presence_enter',
            'leave'     => 'presence_leave',
        ];
        $stage = $stage ? Str::lower($stage) : null;
        return $stage && isset($map[$stage]) ? $map[$stage] : $fallback;
    }


}
