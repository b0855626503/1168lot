<?php

namespace Gametech\LineOA\Events;

use Gametech\LineOA\Models\LineConversation;
use Gametech\LineOA\Models\LineMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
// 👈 ใช้ Now ให้ยิงทันที
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LineOAChatMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conversation_id;

    public array $conversation;

    public array $message;

    public function __construct(LineConversation $conversation, LineMessage $message)
    {
        \Log::channel('line_oa')->info('[LineOA] LineOAChatMessageReceived::__construct', [
            'conversation_id' => $conversation->id,
            'message_id'      => $message->id,
        ]);

        $this->conversation_id = $conversation->id;

        // ให้แน่ใจว่าโหลด relation ครบ (กรณีที่มีที่อื่นเรียก event นี้)
//        $conversation->loadMissing(['contact.member.bank', 'account']);

        // แปลง model เป็น array
        $convArr = $conversation->toArray();

        $convArr['line_account'] = [
            'id'   => $conversation->account?->id,
            'name' => $conversation->account?->name,
        ];

        if (! isset($convArr['last_message'])) {
            $convArr['last_message'] = $conversation->last_message_preview;
        }
        if (! isset($convArr['is_registering'])) {
            $convArr['is_registering'] = $conversation->is_registering;
        }

        if (! isset($convArr['is_pinned'])) {
            $convArr['is_pinned'] = (bool)$conversation->is_pinned;
        }

        // เติมฟิลด์เสริมแบบเดียวกับ API (index/show) เข้าไปใน contact
        if (isset($convArr['contact']) && is_array($convArr['contact'])) {
            $convArr['contact']['member_name']      = $conversation->contact?->member?->name;
            $convArr['contact']['member_bank_name'] = $conversation->contact?->member?->bank?->name_th;
            $convArr['contact']['member_acc_no']    = $conversation->contact?->member?->acc_no;
        }

        $this->conversation = $convArr;
        $this->message      = $message->toArray();
    }


    /**
     * ส่งไป channel ไหน
     * ต้อง “ตรงกับที่ Echo.channel(...) ใช้อยู่”
     */
    public function broadcastOn(): Channel
    {
        // ตรงกับ Echo.channel('{{ config('app.name') }}_events')
        return new Channel(config('app.name').'_events');
    }

    /**
     * ชื่อ event ฝั่ง JS ต้องใช้ listen('ชื่อนี้') หรือ listen('.ชื่อนี้')
     */
    public function broadcastAs(): string
    {
        return 'LineOAChatMessageReceived';
    }
}
