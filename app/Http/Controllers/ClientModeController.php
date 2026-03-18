<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientModeEvent;

class ClientModeController extends Controller
{
    public function store(Request $request)
    {
        // อนุญาตทั้ง guest และ user ที่ล็อกอิน
        $sessionId = $request->session()->getId();

        $data = $request->validate([
            'mode'          => 'required|string|in:browser,pwa',
            'display_mode'  => 'nullable|string|max:50',
            'reason'        => 'nullable|string|max:50',
            'url'           => 'nullable|string|max:2048',
            'ua'            => 'nullable|string|max:1024',
            'pwa_installed_hint' => 'nullable|boolean',
        ]);

        $event = ClientModeEvent::create([
            'user_id'  => optional($request->user())->id,
            'session_id' => $sessionId,
            'mode'       => $data['mode'],
            'display_mode' => $data['display_mode'] ?? null,
            'reason'     => $data['reason'] ?? null,
            'url'        => $data['url'] ?? null,
            'ua'         => $data['ua'] ?? null,
            'pwa_installed_hint' => isset($data['pwa_installed_hint']) ? (bool)$data['pwa_installed_hint'] : null,
        ]);

        // อยากทำสรุป “สถานะล่าสุด” ระดับผู้ใช้/เซสชัน ก็อัปเดตเพิ่มได้ที่นี่

        return response()->json(['ok' => true, 'id' => $event->id]);
    }
}
