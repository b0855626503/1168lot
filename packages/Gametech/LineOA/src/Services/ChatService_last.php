<?php

namespace Gametech\LineOA\Services;

use Gametech\LineOA\Events\LineIncomingMessage;
use Gametech\LineOA\Events\LineOAChatMessageReceived;
use Gametech\LineOA\Models\LineAccount;
use Gametech\LineOA\Models\LineContact;
use Gametech\LineOA\Models\LineConversation;
use Gametech\LineOA\Models\LineMessage;
use Gametech\LineOA\Models\LineWebhookLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatService_last
{
    /**
     * LINE Messaging client สำหรับยิง/ดึงข้อมูลจาก LINE
     */
    protected LineMessagingClient $messagingClient;

    public function __construct(LineMessagingClient $messagingClient)
    {
        $this->messagingClient = $messagingClient;
    }

    /**
     * เวอร์ชันเก่า (เก็บไว้เผื่ออ้างอิง) - อย่าใช้
     */
    public function handleIncomingMessage_(LineAccount $account, array $event, ?LineWebhookLog $log = null): LineMessage
    {
        $userId      = Arr::get($event, 'source.userId');       // line userId
        $messageId   = Arr::get($event, 'message.id');
        $messageType = Arr::get($event, 'message.type');
        $text        = Arr::get($event, 'message.text');
        $sentAt      = Arr::get($event, 'timestamp');

        $sentAtCarbon = \Carbon\Carbon::createFromTimestampMs($sentAt);

        $message = DB::transaction(function () use (
            $account,
            $userId,
            $messageId,
            $messageType,
            $text,
            $sentAtCarbon,
            $event,
            $log
        ) {
            // 1) หา/สร้าง contact
            $contact = $this->getOrCreateContact($account, $userId);

            // 2) หา/สร้าง conversation
            $conversation = $this->getOrCreateConversation($account, $contact);

            // 3) สร้าง message (inbound)
            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id'      => $account->id,
                'line_contact_id'      => $contact->id,
                'direction'            => 'inbound',
                'source'               => 'user',
                'type'                 => $messageType ?? 'text',
                'line_message_id'      => $messageId,
                'text'                 => $messageType === 'text' ? $text : null,
                'payload'              => $event,
                'meta'                 => null,
                'sender_employee_id'   => null,
                'sender_bot_key'       => null,
                'sent_at'              => $sentAtCarbon,
            ]);

            // 4) อัปเดต conversation summary
            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at      = $sentAtCarbon;
            $conversation->unread_count         = $conversation->unread_count + 1;
            $conversation->status               = 'open';
            $conversation->save();

            // 5) อัปเดต last_seen_at ของ contact
            $contact->last_seen_at = $sentAtCarbon;
            $contact->save();

            // 6) ผูก log (ถ้ามี)
            if ($log) {
                $log->line_account_id      = $account->id;
                $log->line_conversation_id = $conversation->id;
                $log->line_contact_id      = $contact->id;
                $log->line_message_id      = $message->id;
                $log->is_processed         = true;
                $log->processed_at         = now();
                $log->save();
            }

            // 7) preload relation
            $message->setRelation('conversation', $conversation);
            $message->setRelation('contact', $contact);

            return $message;
        });

        // ====== BROADCAST REAL-TIME ไปหน้าแอดมิน (เวอร์ชันเก่า) ======
        /** @var LineConversation $conversation */
        $conversation = $message->conversation ?? $message->conversation()->first();
        event(new LineIncomingMessage($account, $conversation, $message));

        return $message;
    }

    /**
     * เวอร์ชันเก่าอีกอัน (เก็บ reference) - อย่าใช้
     */
    public function handleIncomingMessage__(LineAccount $account, array $event, ?LineWebhookLog $log = null): LineMessage
    {
        $userId      = Arr::get($event, 'source.userId');
        $messageId   = Arr::get($event, 'message.id');
        $messageType = Arr::get($event, 'message.type');
        $text        = Arr::get($event, 'message.text');
        $sentAt      = Arr::get($event, 'timestamp');

        $sentAtCarbon = $sentAt
            ? now()->setTimestamp((int) floor($sentAt / 1000))
            : now();

        return DB::transaction(function () use (
            $account,
            $userId,
            $messageId,
            $messageType,
            $text,
            $sentAtCarbon,
            $event,
            $log
        ) {
            // 1) contact
            $contact = $this->getOrCreateContact($account, $userId);

            // 2) conversation
            $conversation = $this->getOrCreateConversation($account, $contact);

            // 3) message inbound
            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id'      => $account->id,
                'line_contact_id'      => $contact->id,
                'direction'            => 'inbound',
                'source'               => 'user',
                'type'                 => $messageType ?? 'text',
                'line_message_id'      => $messageId,
                'text'                 => $messageType === 'text' ? $text : null,
                'payload'              => $event,
                'meta'                 => null,
                'sender_employee_id'   => null,
                'sender_bot_key'       => null,
                'sent_at'              => $sentAtCarbon,
            ]);

            // 4) update conversation summary
            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at      = $sentAtCarbon;
            $conversation->unread_count         = $conversation->unread_count + 1;
            $conversation->status               = 'open';
            $conversation->save();

            // 5) ผูก log (ถ้ามี)
            if ($log) {
                $log->line_account_id      = $account->id;
                $log->line_conversation_id = $conversation->id;
                $log->line_contact_id      = $contact->id;
                $log->line_message_id      = $message->id;
                $log->is_processed         = true;
                $log->processed_at         = now();
                $log->save();
            }

            // 6) broadcast real-time (เวอร์ชันเก่า)
            DB::afterCommit(function () use ($conversation, $message) {
                $freshConversation = $conversation->fresh(['contact', 'account']);

                event(new LineOAChatMessageReceived(
                    $freshConversation ?? $conversation,
                    $message
                ));
            });

            return $message;
        });
    }

    /**
     * เวอร์ชันหลัก (ใช้จริงตอนนี้)
     * รองรับทุก type: text / sticker / image / video / audio / location ...
     */
    public function handleIncomingMessage(LineAccount $account, array $event, ?LineWebhookLog $log = null): LineMessage
    {
        $userId      = Arr::get($event, 'source.userId');
        $messageId   = Arr::get($event, 'message.id');
        $messageType = Arr::get($event, 'message.type'); // text | sticker | image ...
        $text        = Arr::get($event, 'message.text');
        $sentAt      = Arr::get($event, 'timestamp');

        // timestamp ms → sec
        $sentAtCarbon = $sentAt
            ? now()->setTimestamp((int) floor($sentAt / 1000))
            : now();

        return DB::transaction(function () use (
            $account,
            $event,
            $userId,
            $messageId,
            $messageType,
            $text,
            $sentAtCarbon,
            $log
        ) {
            // 1) contact
            $contact = $this->getOrCreateContact($account, $userId);

// เรียก updateProfile เฉพาะเคส profile ยังไม่มี
            if (empty($contact->display_name) && empty($contact->picture_url)) {
                try {
                    $contact = $this->updateContactProfile($account, $userId);
                } catch (\Throwable $e) {
                    // กัน error ไม่ให้พัง flow
                }
            }

            // 2) conversation
            $conversation = $this->getOrCreateConversation($account, $contact);

            // 3) message inbound
            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id'      => $account->id,
                'line_contact_id'      => $contact->id,
                'direction'            => 'inbound',
                'source'               => 'user',
                'type'                 => $messageType ?? 'text',
                'line_message_id'      => $messageId,
                'text'                 => $messageType === 'text' ? $text : null,
                'payload'              => $event,
                'meta'                 => null,
                'sender_employee_id'   => null,
                'sender_bot_key'       => null,
                'sent_at'              => $sentAtCarbon,
            ]);

            // 3.1 ถ้าเป็นรูป ให้พยายามดึง binary จาก LINE แล้วแปลงเป็น URL สำหรับ frontend
            if ($messageType === 'image') {
                $this->attachImageContentIfNeeded($account, $message);
            }

            // 4) update conversation summary
            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at      = $sentAtCarbon;
            $conversation->unread_count         = $conversation->unread_count + 1;
            if ($conversation->status === null) {
                $conversation->status = 'open';
            }
            $conversation->save();

            // 5) update contact last_seen
            $contact->last_seen_at = $sentAtCarbon;
            $contact->save();

            // 6) ผูก log (ถ้ามี)
            if ($log) {
                $log->line_account_id      = $account->id;
                $log->line_conversation_id = $conversation->id;
                $log->line_contact_id      = $contact->id;
                $log->line_message_id      = $message->id;
                $log->is_processed         = true;
                $log->processed_at         = now();
                $log->save();
            }

            // 7) reload relation (pattern เดิม)
            $conversation = $conversation->fresh(['contact.member', 'account']);

            $message->setRelation('conversation', $conversation);
            $message->setRelation('contact', $contact);

            // 8) broadcast realtime ให้หน้าแอดมิน
            DB::afterCommit(function () use ($conversation, $message) {
                event(new LineOAChatMessageReceived(
                    $conversation,
                    $message
                ));
            });

            return $message;
        });
    }

    /**
     * ใช้ตอนฝั่งแอดมินตอบ (outbound จาก agent) - TEXT
     */
    public function createOutboundMessageFromAgent(
        LineConversation $conversation,
        string $text,
        int $employeeId,
        ?array $meta = null
    ): LineMessage {
        $now = now();

        return DB::transaction(function () use ($conversation, $text, $employeeId, $meta, $now) {
            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id'      => $conversation->line_account_id,
                'line_contact_id'      => $conversation->line_contact_id,
                'direction'            => 'outbound',
                'source'               => 'agent',
                'type'                 => 'text',
                'line_message_id'      => null,
                'text'                 => $text,
                'payload'              => null,
                'meta'                 => $meta,
                'sender_employee_id'   => $employeeId,
                'sender_bot_key'       => null,
                'sent_at'              => $now,
            ]);

            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at      = $now;
            $conversation->unread_count         = 0;
            $conversation->save();

            return $message;
        });
    }

    /**
     * ใช้ตอนฝั่งแอดมินส่ง "รูป" ออกไป
     */
    public function createOutboundImageFromAgent(
        LineConversation $conversation,
        UploadedFile $file,
        int $employeeId,
        ?array $meta = null
    ): LineMessage {
        $now = now();

        return DB::transaction(function () use ($conversation, $file, $employeeId, $meta, $now) {
            // upload รูปไปที่ storage (disk public)
            $path = $file->store('line-oa/images', 'public');
            $url  = Storage::disk('public')->url($path);

            $payload = [
                'message' => [
                    'type'       => 'image',
                    'contentUrl' => $url,
                    'previewUrl' => $url,
                    'originalContentUrl' => $url,
                    'previewImageUrl' => $url,
                ],
            ];

            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id'      => $conversation->line_account_id,
                'line_contact_id'      => $conversation->line_contact_id,
                'direction'            => 'outbound',
                'source'               => 'agent',
                'type'                 => 'image',
                'line_message_id'      => null,
                'text'                 => null,
                'payload'              => $payload,
                'meta'                 => $meta,
                'sender_employee_id'   => $employeeId,
                'sender_bot_key'       => null,
                'sent_at'              => $now,
            ]);

            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at      = $now;
            $conversation->unread_count         = 0;
            $conversation->save();

            return $message;
        });
    }

    /**
     * ดึง profile จาก LINE แล้วอัปเดตลง LineContact
     */
    public function updateContactProfile(LineAccount $account, string $lineUserId): LineContact
    {
        $contact = $this->getOrCreateContact($account, $lineUserId);

        $result = $this->messagingClient->getProfile($account, $lineUserId);

        if (! ($result['success'] ?? false)) {
            return $contact;
        }

        $body = $result['body'] ?? [];

        $contact->display_name   = $body['displayName']   ?? $contact->display_name;
        $contact->picture_url    = $body['pictureUrl']    ?? $contact->picture_url;
        $contact->status_message = $body['statusMessage'] ?? $contact->status_message;
        $contact->last_seen_at   = $contact->last_seen_at ?? now();
        $contact->save();

        return $contact;
    }

    /**
     * หา/สร้าง contact จาก line_account + line_user_id
     */
    public function getOrCreateContact(LineAccount $account, string $lineUserId): LineContact
    {
        /** @var LineContact $contact */
        $contact = LineContact::where('line_account_id', $account->id)
            ->where('line_user_id', $lineUserId)
            ->first();

        if (! $contact) {
            $contact = LineContact::create([
                'line_account_id' => $account->id,
                'line_user_id'    => $lineUserId,
                'display_name'    => null,
                'picture_url'     => null,
                'status_message'  => null,
                'member_id'       => null,
                'member_username' => null,
                'member_mobile'   => null,
                'tags'            => [],
                'last_seen_at'    => null,
                'blocked_at'      => null,
            ]);
        }

        return $contact;
    }

    /**
     * หา/สร้าง conversation สำหรับ contact นี้ใน OA นี้
     */
    protected function getOrCreateConversation_(LineAccount $account, LineContact $contact): LineConversation
    {
        /** @var LineConversation $conversation */
        $conversation = LineConversation::where('line_account_id', $account->id)
            ->where('line_contact_id', $contact->id)
            ->where('status', 'open')
            ->first();

        if (! $conversation) {
            $conversation = LineConversation::create([
                'line_account_id'        => $account->id,
                'line_contact_id'        => $contact->id,
                'status'                 => 'open',
                'last_message_preview'   => null,
                'last_message_at'        => null,
                'unread_count'           => 0,
                'assigned_employee_id'   => null,
                'locked_by_employee_id'  => null,
            ]);
        }

        return $conversation;
    }

    protected function getOrCreateConversation(LineAccount $account, LineContact $contact): LineConversation
    {
        /** @var LineConversation $conversation */
        $conversation = LineConversation::where('line_account_id', $account->id)
            ->where('line_contact_id', $contact->id)
            ->where(function ($q) {
                // ใช้ห้องเดิมทุกกรณีที่ “ยังไม่ปิดเคส”
                $q->whereNull('status')
                    ->orWhereIn('status', ['open', 'assigned']);
            })
            ->orderByDesc('id')
            ->first();

        if (! $conversation) {
            $conversation = LineConversation::create([
                'line_account_id'        => $account->id,
                'line_contact_id'        => $contact->id,
                'status'                 => 'open',
                'last_message_preview'   => null,
                'last_message_at'        => null,
                'unread_count'           => 0,
                'assigned_employee_id'   => null,
                'locked_by_employee_id'  => null,
            ]);
        }

        return $conversation;
    }

    /**
     * helper: ดึง binary image จาก LINE + เซฟ + อัปเดต payload ให้ Vue ใช้ contentUrl ได้
     */
    protected function attachImageContentIfNeeded(LineAccount $account, LineMessage $message): void
    {
        try {
            $payload = $message->payload ?? [];
            $msgPayload = $payload['message'] ?? null;

            if (! is_array($msgPayload)) {
                return;
            }

            // ถ้ามี contentUrl / previewUrl แล้ว ไม่ต้องทำอะไร
            if (! empty($msgPayload['contentUrl']) || ! empty($msgPayload['previewUrl'])) {
                return;
            }

            $contentProviderType = $msgPayload['contentProvider']['type'] ?? null;
            if ($contentProviderType !== 'line') {
                // ถ้าเป็น external หรืออื่น ๆ ไม่ยุ่ง ให้ payload เดิมไป
                return;
            }

            $lineMessageId = $message->line_message_id;
            if (! $lineMessageId) {
                return;
            }

            $result = $this->messagingClient->downloadMessageContent($account, $lineMessageId);

            if (! ($result['success'] ?? false)) {
                \Log::channel('line_oa')->warning('[LineChat] ดึง content รูปจาก LINE ไม่สำเร็จ', [
                    'message_id'     => $message->id,
                    'line_message_id'=> $lineMessageId,
                    'status'         => $result['status'] ?? null,
                ]);
                return;
            }

            $binary = $result['body'] ?? null;
            if ($binary === null || $binary === '') {
                \Log::channel('line_oa')->warning('[LineChat] ดึง content รูปจาก LINE ได้ body ว่าง', [
                    'message_id'     => $message->id,
                    'line_message_id'=> $lineMessageId,
                ]);
                return;
            }

            // เซฟไฟล์ลง disk public
            $ext  = 'jpg'; // LINE image ส่วนใหญ่เป็น jpeg; ถ้าจะละเอียดค่อยตรวจ header ทีหลังได้
            $path = 'line-oa/inbound/'.$lineMessageId.'.'.$ext;

            Storage::disk('public')->put($path, $binary);
            $url = Storage::disk('public')->url($path);

            $payload['message']['contentUrl'] = $url;
            $payload['message']['previewUrl'] = $payload['message']['previewUrl'] ?? $url;

            $message->payload = $payload;
            $message->save();
        } catch (\Throwable $e) {
            \Log::channel('line_oa')->error('[LineChat] exception ขณะดึง content รูปจาก LINE', [
                'message_id'     => $message->id,
                'line_message_id'=> $message->line_message_id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * สร้างข้อความ preview ให้ใช้ใน list conversation
     */
    protected function buildPreviewText(LineMessage $message): string
    {
        if ($message->type === 'text' && $message->text) {
            $text = $message->text;

            // ตัดความยาวไม่ให้ยาวเกินไป
            return mb_strimwidth($text, 0, 100, '...');
        }

        // สำหรับประเภทอื่น ๆ เช่น image/sticker/template
        return '['.$message->type.']';
    }

    public function markConversationAsRead(LineConversation $conversation): void
    {
        $conversation->unread_count = 0;
        $conversation->save();
    }

    /**
     * ปิดห้องสนทนา
     */
    public function closeConversation(LineConversation $conversation, ?int $employeeId = null): void
    {
        $conversation->status       = 'closed';
        $conversation->closed_at    = now();
        $conversation->closed_by    = $employeeId;
        $conversation->unread_count = 0;
        $conversation->save();
    }
}
