<?php

namespace Gametech\LineOA\Events;

use Gametech\LineOA\Models\LineAccount;
use Gametech\LineOA\Models\LineConversation;
use Gametech\LineOA\Models\LineMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class LineIncomingMessage implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public LineAccount $account;
    public LineConversation $conversation;
    public LineMessage $message;

    public function __construct(LineAccount $account, LineConversation $conversation, LineMessage $message)
    {
        // โหลด relation ให้พร้อมใช้ใน broadcast
        $conversation->loadMissing(['contact', 'account']);

        $this->account      = $account;
        $this->conversation = $conversation;
        $this->message      = $message;
    }

    /**
     * broadcast ไป channel ไหน
     */
    public function broadcastOn(): Channel
    {
        // ถ้าจะแยกตาม OA ก็ทำเป็น new Channel('line-oa.chat.'.$this->account->id);
        return new Channel('line-oa.chat');
    }

    /**
     * ชื่อ event ฝั่ง JS จะใช้ listen('.LineOA.IncomingMessage')
     */
    public function broadcastAs(): string
    {
        return 'LineOA.IncomingMessage';
    }

    /**
     * ข้อมูลที่จะส่งไปให้ JS
     */
    public function broadcastWith(): array
    {
        return [
            'account_id'   => $this->account->id,
            'conversation' => [
                'id'               => $this->conversation->id,
                'line_account_id'  => $this->conversation->line_account_id,
                'line_contact_id'  => $this->conversation->line_contact_id,
                'status'           => $this->conversation->status,
                'last_message'     => $this->conversation->last_message_preview,
                'last_message_at'  => optional($this->conversation->last_message_at)->toDateTimeString(),
                'unread_count'     => $this->conversation->unread_count,
                'unread_count'     => $this->conversation->unread_count,
                'is_pinned'          => (bool) $this->conversation->is_pinned,
                'contact'          => $this->conversation->contact ? [
                    'id'               => $this->conversation->contact->id,
                    'display_name'     => $this->conversation->contact->display_name,
                    'picture_url'      => $this->conversation->contact->picture_url,
                    'member_username'  => $this->conversation->contact->member_username,
                    'member_mobile'    => $this->conversation->contact->member_mobile,
                ] : null,
                'line_account'     => $this->conversation->account ? [
                    'id'   => $this->conversation->account->id,
                    'name' => $this->conversation->account->name,
                ] : null,
            ],
            'message' => [
                'id'                 => $this->message->id,
                'line_conversation_id' => $this->message->line_conversation_id,
                'direction'          => $this->message->direction,
                'source'             => $this->message->source,
                'type'               => $this->message->type,
                'text'               => $this->message->text,
                'sent_at'            => optional($this->message->sent_at)->toDateTimeString(),
                'is_pinned'          => (bool) $this->message->is_pinned,
            ],
        ];
    }
}
