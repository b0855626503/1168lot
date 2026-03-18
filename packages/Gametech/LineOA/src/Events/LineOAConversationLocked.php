<?php

namespace Gametech\LineOA\Events;

use Gametech\LineOA\Models\LineConversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LineOAConversationLocked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conversation_id;

    public array $conversation;

    public function __construct(LineConversation $conversation)
    {
        $conversation->loadMissing(['contact.member', 'account']);

        \Log::channel('line_oa')->info('[LineOA] LineOAConversationLocked::__construct', [
            'conversation_id'        => $conversation->id,
            'status'                 => $conversation->status,
            'locked_by_employee_id'  => $conversation->locked_by_employee_id,
            'locked_by_employee_name'=> $conversation->locked_by_employee_name,
        ]);

        $this->conversation_id = (int) $conversation->id;

        $this->conversation = [
            'id'               => $conversation->id,
            'status'           => $conversation->status,
            'last_message'     => $conversation->last_message_preview,
            'last_message_at'  => optional($conversation->last_message_at)->toIso8601String(),
            'unread_count'     => $conversation->unread_count,
            'is_registering' => $conversation->is_registering,

            'assigned_employee_id'   => $conversation->assigned_employee_id,
            'assigned_employee_name' => $conversation->assigned_employee_name,
            'assigned_at'            => optional($conversation->assigned_at)->toIso8601String(),

            'locked_by_employee_id'   => $conversation->locked_by_employee_id,
            'locked_by_employee_name' => $conversation->locked_by_employee_name,
            'locked_at'               => optional($conversation->locked_at)->toIso8601String(),

            'closed_by_employee_id'   => $conversation->closed_by_employee_id,
            'closed_by_employee_name' => $conversation->closed_by_employee_name,
            'closed_at'               => optional($conversation->closed_at)->toIso8601String(),

            'is_pinned'               => (bool)$conversation->is_pinned,

            'line_account' => [
                'id'   => $conversation->account?->id,
                'name' => $conversation->account?->name,
            ],

            'contact' => [
                'id'               => $conversation->contact?->id,
                'display_name'     => $conversation->contact?->display_name,
                'line_user_id'     => $conversation->contact?->line_user_id,
                'member_id'        => $conversation->contact?->member_id,
                'member_username'  => $conversation->contact?->member_username,
                'member_mobile'    => $conversation->contact?->member_mobile,
                'picture_url'      => $conversation->contact?->picture_url,
                'blocked_at'       => optional($conversation->contact?->blocked_at)->toDateTimeString(),

                'member_name'      => $conversation->contact?->member?->name,
                'member_bank_name' => $conversation->contact?->member?->bank?->name_th,
                'member_acc_no'    => $conversation->contact?->member?->acc_no,
            ],
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel(config('app.name') . '_events');
    }

    public function broadcastAs(): string
    {
        return 'LineOAConversationLocked';
    }
}
