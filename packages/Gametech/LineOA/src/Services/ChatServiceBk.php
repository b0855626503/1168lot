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

class ChatServiceBk
{
    /**
     * LINE Messaging client สำหรับยิง API ออกไปยัง LINE
     */
    protected LineMessagingClient $messagingClient;

    public function __construct(LineMessagingClient $messagingClient)
    {
        $this->messagingClient = $messagingClient;
    }

    /**
     * จัดการข้อความ inbound จาก LINE (ข้อความ text จากลูกค้า)
     *
     * @param  array  $event  event จาก LINE
     * @param  LineWebhookLog|null  $log  (เผื่อใช้ในอนาคต ถ้าจะผูก log เพิ่ม)
     */
    public function handleIncomingMessage_(LineAccount $account, array $event, ?LineWebhookLog $log = null): LineMessage
    {
        $userId = Arr::get($event, 'source.userId');       // line userId
        $messageId = Arr::get($event, 'message.id');
        $messageType = Arr::get($event, 'message.type');
        $text = Arr::get($event, 'message.text');
        $sentAt = Arr::get($event, 'timestamp');

        // timestamp จาก LINE เป็น ms => แปลงเป็น sec แล้วสร้าง Carbon
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
                'line_account_id' => $account->id,
                'line_contact_id' => $contact->id,
                'direction' => 'inbound',
                'source' => 'user',
                'type' => $messageType ?? 'text',
                'line_message_id' => $messageId,
                'text' => $messageType === 'text' ? $text : null,
                'payload' => $event,
                'meta' => null,
                'sender_employee_id' => null,
                'sender_bot_key' => null,
                'sent_at' => $sentAtCarbon,
            ]);

            // 4) อัปเดต conversation summary
            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $sentAtCarbon;
            $conversation->unread_count = $conversation->unread_count + 1;
            $conversation->status = 'open';
            $conversation->save();

            // 5) อัปเดต last_seen_at ของ contact
            $contact->last_seen_at = $sentAtCarbon;
            $contact->save();

            // 6) ผูก log (ถ้ามี)
            if ($log) {
                $log->line_account_id = $account->id;
                $log->line_conversation_id = $conversation->id;
                $log->line_contact_id = $contact->id;
                $log->line_message_id = $message->id;
                $log->is_processed = true;
                $log->processed_at = now();
                $log->save();
            }

            // 7) โหลด relation ไว้ใช้หลังออกจาก transaction
            $message->setRelation('conversation', $conversation);
            $message->setRelation('contact', $contact);

            return $message;
        });

        // ====== BROADCAST REAL-TIME ไปหน้าแอดมิน ======
        /** @var LineConversation $conversation */
        $conversation = $message->conversation ?? $message->conversation()->first();
        event(new LineIncomingMessage($account, $conversation, $message));

        return $message;

    }

    public function handleIncomingMessage__(LineAccount $account, array $event, ?LineWebhookLog $log = null): LineMessage
    {
        $userId = Arr::get($event, 'source.userId');
        $messageId = Arr::get($event, 'message.id');
        $messageType = Arr::get($event, 'message.type');
        $text = Arr::get($event, 'message.text');
        $sentAt = Arr::get($event, 'timestamp');

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
                'line_account_id' => $account->id,
                'line_contact_id' => $contact->id,
                'direction' => 'inbound',
                'source' => 'user',
                'type' => $messageType ?? 'text',
                'line_message_id' => $messageId,
                'text' => $messageType === 'text' ? $text : null,
                'payload' => $event,
                'meta' => null,
                'sender_employee_id' => null,
                'sender_bot_key' => null,
                'sent_at' => $sentAtCarbon,
            ]);

            // 4) update conversation summary
            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $sentAtCarbon;
            $conversation->unread_count = $conversation->unread_count + 1;
            $conversation->status = 'open';
            $conversation->save();

            // 5) ผูก log (ถ้ามี)
            if ($log) {
                $log->line_account_id = $account->id;
                $log->line_conversation_id = $conversation->id;
                $log->line_contact_id = $contact->id;
                $log->line_message_id = $message->id;
                $log->is_processed = true;
                $log->processed_at = now();
                $log->save();
            }

            // 6) broadcast real-time ให้ทุกหน้าทีมงานที่เปิดอยู่รู้ว่ามีข้อความใหม่
            //    ใช้ afterCommit กันเคส transaction rollback แล้ว event ยิงออกไปก่อน
            DB::afterCommit(function () use ($conversation, $message) {
                // โหลดความสัมพันธ์ contact + account ให้ list ด้านซ้ายอัปเดตได้
                $freshConversation = $conversation->fresh(['contact', 'account']);

                event(new LineOAChatMessageReceived(
                    $freshConversation ?? $conversation,
                    $message
                ));
            });

            return $message;
        });
    }

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

            // 2) conversation
            $conversation = $this->getOrCreateConversation($account, $contact);

            // 3) message inbound (เพิ่มรองรับ sticker)
            /** @var LineMessage $message */

            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id' => $account->id,
                'line_contact_id' => $contact->id,
                'direction' => 'inbound',
                'source' => 'user',
                'type' => $messageType ?? 'text',
                'line_message_id' => $messageId,
                'text' => $messageType === 'text' ? $text : null,
                'payload' => $event,
                'meta' => null,
                'sender_employee_id' => null,
                'sender_bot_key' => null,
                'sent_at' => $sentAtCarbon,
            ]);

            // update summary
            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at      = $sentAtCarbon;
            $conversation->unread_count         = $conversation->unread_count + 1;
            $conversation->status               = 'open';
            $conversation->save();

            // update contact
            $contact->last_seen_at = $sentAtCarbon;
            $contact->save();

            // log
            if ($log) {
                $log->line_account_id      = $account->id;
                $log->line_conversation_id = $conversation->id;
                $log->line_contact_id      = $contact->id;
                $log->line_message_id      = $message->id;
                $log->is_processed         = true;
                $log->processed_at         = now();
                $log->save();
            }

            // keep your original relations EXACTLY
            $conversation = $conversation->fresh(['contact', 'account']);

            // attach to message
            $message->setRelation('conversation', $conversation);
            $message->setRelation('contact', $contact);

            // Broadcast realtime (ตามของโบ๊ท)
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
     * ใช้ตอนฝั่งแอดมินตอบ (outbound จาก agent)
     *
     * NOTE: ตอนทำ UI หลังบ้านเราจะเรียก method นี้
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
                'line_account_id' => $conversation->line_account_id,
                'line_contact_id' => $conversation->line_contact_id,
                'direction' => 'outbound',
                'source' => 'agent',
                'type' => 'text',
                'line_message_id' => null,
                'text' => $text,
                'payload' => null,
                'meta' => $meta,
                'sender_employee_id' => $employeeId,
                'sender_bot_key' => null,
                'sent_at' => $now,
            ]);

            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $now;
            $conversation->unread_count = 0;
            $conversation->save();

            return $message;
        });
    }

    public function createOutboundImageFromAgent(
        LineConversation $conversation,
        UploadedFile $file,
        int $employeeId,
        ?array $meta = null
    ): LineMessage {
        $now = now();

        return DB::transaction(function () use ($conversation, $file, $employeeId, $meta, $now) {
            // 1) upload รูปไปที่ storage (disk public หรือ disk ที่เป็น https)
            $path = $file->store('line-oa/images', 'public'); // ปรับ disk ตามโปรเจ็กต์โบ๊ท
            $url  = Storage::disk('public')->url($path);

            $payload = [
                'message' => [
                    'type'        => 'image',
                    'contentUrl'  => $url,
                    'previewUrl'  => $url,
                ],
            ];

            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'line_contact_id' => $conversation->line_contact_id,
                'direction' => 'outbound',
                'source' => 'agent',
                'type' => 'image',
                'line_message_id' => null,
                'text' => null,
                'payload' => $payload,
                'meta' => $meta,
                'sender_employee_id' => $employeeId,
                'sender_bot_key' => null,
                'sent_at' => $now,
            ]);

            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $now;
            $conversation->unread_count = 0;
            $conversation->save();

            return $message;
        });
    }

    /**
     * ดึง profile จาก LINE แล้วอัปเดตลง LineContact
     *
     * ใช้ได้ทั้งจาก follow event หรือ message แรกของลูกค้า
     */
    public function updateContactProfile(LineAccount $account, string $lineUserId): LineContact
    {
        // ให้แน่ใจก่อนว่ามี contact ในระบบ
        $contact = $this->getOrCreateContact($account, $lineUserId);

        $result = $this->messagingClient->getProfile($account, $lineUserId);

        if (! ($result['success'] ?? false)) {
            // กรณีเรียก LINE API ไม่ผ่าน ก็คืน contact เดิมไปเฉย ๆ
            return $contact;
        }

        $body = $result['body'] ?? [];

        $contact->display_name = $body['displayName'] ?? $contact->display_name;
        $contact->picture_url = $body['pictureUrl'] ?? $contact->picture_url;
        $contact->status_message = $body['statusMessage'] ?? $contact->status_message;
        $contact->last_seen_at = $contact->last_seen_at ?? now();
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
                'line_user_id' => $lineUserId,
                'display_name' => null,
                'picture_url' => null,
                'status_message' => null,
                'member_id' => null,
                'member_username' => null,
                'member_mobile' => null,
                'tags' => [],
                'last_seen_at' => null,
                'blocked_at' => null,
            ]);
        }

        return $contact;
    }

    /**
     * หา/สร้าง conversation สำหรับ contact นี้ใน OA นี้
     *
     * ตอนนี้ใช้ policy ง่าย ๆ: 1 contact ต่อ 1 open conversation
     */
    protected function getOrCreateConversation(LineAccount $account, LineContact $contact): LineConversation
    {
        /** @var LineConversation $conversation */
        $conversation = LineConversation::where('line_account_id', $account->id)
            ->where('line_contact_id', $contact->id)
            ->where('status', 'open')
            ->first();

        if (! $conversation) {
            $conversation = LineConversation::create([
                'line_account_id' => $account->id,
                'line_contact_id' => $contact->id,
                'status' => 'open',
                'last_message_preview' => null,
                'last_message_at' => null,
                'unread_count' => 0,
                'assigned_employee_id' => null,
                'locked_by_employee_id' => null,
            ]);
        }

        return $conversation;
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
     * ปิดห้องสนทนา (เช่น ลูกค้าจบเคสแล้ว)
     *
     * @param  int|null  $employeeId  พนักงานที่กดปิด (optional)
     */
    public function closeConversation(LineConversation $conversation, ?int $employeeId = null): void
    {
        $conversation->status = 'closed';
        $conversation->closed_at = now();
        $conversation->closed_by = $employeeId;
        $conversation->unread_count = 0;
        $conversation->save();
    }
}
