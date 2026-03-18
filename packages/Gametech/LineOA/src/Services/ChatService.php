<?php

namespace Gametech\LineOA\Services;

use Gametech\LineOA\Events\LineOAChatConversationUpdated;
use Gametech\LineOA\Events\LineOAChatMessageReceived;
use Gametech\LineOA\Models\LineAccount;
use Gametech\LineOA\Models\LineContact;
use Gametech\LineOA\Models\LineConversation;
use Gametech\LineOA\Models\LineMessage;
use Gametech\LineOA\Models\LineWebhookLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    /**
     * LINE Messaging client สำหรับยิง/ดึงข้อมูลจาก LINE
     */
    protected LineMessagingClient $messagingClient;

    protected TranslationService $translator;

    public function __construct(LineMessagingClient $messagingClient, TranslationService $translator)
    {
        $this->messagingClient = $messagingClient;
        $this->translator = $translator;
    }

    /**
     * เวอร์ชันหลัก (ใช้จริงตอนนี้)
     * รองรับทุก type: text / sticker / image / video / audio / location ...
     */
    public function handleIncomingMessage(LineAccount $account, array $event, ?LineWebhookLog $log = null): LineMessage
    {
        $userId = Arr::get($event, 'source.userId');
        $messageId = Arr::get($event, 'message.id');
        $messageType = Arr::get($event, 'message.type'); // text | sticker | image ...
        $text = Arr::get($event, 'message.text');
        $sentAt = Arr::get($event, 'timestamp');

        // ------------------------------------------------------------------
        // ดึง token ต่าง ๆ จาก event ให้ครอบคลุมทั้ง camelCase / snake_case
        // และทั้งที่อยู่ใน message.* กับ root event
        // ------------------------------------------------------------------

        // markAsReadToken
        $markAsReadToken = Arr::get($event, 'message.markAsReadToken')
            ?? Arr::get($event, 'message.mark_as_read_token')
            ?? Arr::get($event, 'markAsReadToken')
            ?? Arr::get($event, 'mark_as_read_token');

        // quoteToken
        $quoteToken = Arr::get($event, 'message.quoteToken')
            ?? Arr::get($event, 'message.quote_token')
            ?? Arr::get($event, 'quoteToken')
            ?? Arr::get($event, 'quote_token');

        // quotedMessageId
        $quotedMessageId = Arr::get($event, 'message.quotedMessageId')
            ?? Arr::get($event, 'message.quoted_message_id')
            ?? Arr::get($event, 'quotedMessageId')
            ?? Arr::get($event, 'quoted_message_id');

        // replyToken (สำหรับใช้ต่อ/เก็บ meta)
        $replyToken = Arr::get($event, 'replyToken')
            ?? Arr::get($event, 'reply_token');

        // ------------------------------------------------------------------
        // เตรียม meta base ที่จะใช้กับทุก message type (ไม่ใช่แค่ text)
        // ------------------------------------------------------------------
        $baseMeta = [];

        if ($markAsReadToken) {
            $baseMeta['markAsReadToken'] = $markAsReadToken;
            $baseMeta['mark_as_read_token'] = $markAsReadToken;
        }

        if ($quoteToken) {
            $baseMeta['quoteToken'] = $quoteToken;
            $baseMeta['quote_token'] = $quoteToken;
        }

        if ($quotedMessageId) {
            $baseMeta['quotedMessageId'] = $quotedMessageId;
            $baseMeta['quoted_message_id'] = $quotedMessageId;
        }

        if ($replyToken) {
            $baseMeta['replyToken'] = $replyToken;
            $baseMeta['reply_token'] = $replyToken;
        }

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
            $log,
            $quotedMessageId,
            $baseMeta
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

            // 2) conversation (รองรับ reuse ห้องเดิม + auto reopen)
            $conversation = $this->getOrCreateConversation($account, $contact);

            // 3) meta เบื้องต้นจาก token ต่าง ๆ
            $meta = $baseMeta;

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
                'meta' => $meta ?: null,
                'sender_employee_id' => null,
                'sender_bot_key' => null,
                'sent_at' => $sentAtCarbon,
            ]);

            // 3.1 ถ้าเป็นรูป ให้พยายามดึง binary จาก LINE แล้วแปลงเป็น URL สำหรับ frontend
            // 3.1) ถ้าเป็น media ที่ LINE เก็บไว้เอง (contentProvider.type = line)
            //      LINE จะไม่ให้ URL เล่น/ดูตรง ๆ → ต้อง download binary มาเก็บเอง แล้วเติม contentUrl
            if (in_array($messageType, ['image', 'audio', 'video'], true)) {
                $this->attachLineMediaContentIfNeeded($account, $message);
            }

            // 3.2 ถ้าเป็นข้อความ text และเปิดใช้ translation → detect language + แปลให้ agent อ่าน
            if ($messageType === 'text') {
                $this->handleInboundTextLanguageAndTranslation($conversation, $contact, $message);
            }

            // 3.3 ถ้าลูกค้ากด reply ข้อความเก่า (มี quotedMessageId)
            //      → map ไปยัง LineMessage ต้นทางด้วย line_message_id แล้วผูก meta.reply_to
            if ($quotedMessageId) {
                try {
                    /** @var LineMessage|null $quoted */
                    $quoted = LineMessage::query()
                        ->where('line_conversation_id', $conversation->id)
                        ->where('line_message_id', $quotedMessageId)
                        ->first();

                    if ($quoted) {
                        // เตรียมข้อความ preview สำหรับ quote
                        $displayText = '';
                        $quotedMeta = $quoted->meta;

                        if (! is_array($quotedMeta)) {
                            $quotedMeta = $quotedMeta ? (array) $quotedMeta : [];
                        }

                        if ($quoted->type === 'text') {
                            // ถ้าเป็น outbound ที่เคยแปล → ใช้ translated_text เป็นหลัก
                            $outboundTrans = $quotedMeta['translation_outbound'] ?? null;

                            if (is_array($outboundTrans) && ! empty($outboundTrans['translated_text'])) {
                                $displayText = (string) $outboundTrans['translated_text'];
                            } else {
                                $displayText = (string) $quoted->text;
                            }
                        } else {
                            // ถ้าไม่ใช่ text → ขึ้น label ตามประเภท
                            switch ($quoted->type) {
                                case 'image':
                                    $displayText = '[รูปภาพ]';
                                    break;
                                case 'sticker':
                                    $displayText = '[สติ๊กเกอร์]';
                                    break;
                                case 'video':
                                    $displayText = '[วิดีโอ]';
                                    break;
                                case 'audio':
                                    $displayText = '[เสียง]';
                                    break;
                                case 'location':
                                    $displayText = '[ตำแหน่งที่ตั้ง]';
                                    break;
                                default:
                                    $displayText = '['.($quoted->type ?: 'ข้อความ').']';
                                    break;
                            }
                        }

                        // ตัดความยาวไม่เกิน ~80 char
                        $displayText = mb_strimwidth($displayText, 0, 80, '...');

                        $metaCurrent = $message->meta;
                        if (! is_array($metaCurrent)) {
                            $metaCurrent = $metaCurrent ? (array) $metaCurrent : [];
                        }

                        $metaCurrent['reply_to'] = [
                            'id' => $quoted->id,
                            'text' => $displayText,
                            'direction' => $quoted->direction,
                            'source' => $quoted->source,
                            'type' => $quoted->type,
                            'sent_at' => optional($quoted->sent_at)->toIso8601String(),
                        ];

                        $message->meta = $metaCurrent;
                        $message->save();
                    }
                } catch (\Throwable $e) {
                    \Log::channel('line_oa')->warning('[LineChat] attach reply_to by quotedMessageId failed', [
                        'message_id' => $message->id,
                        'quoted_message_id' => $quotedMessageId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 4) update conversation summary
            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $sentAtCarbon;
            $conversation->unread_count = $conversation->unread_count + 1;

            // safety guard: ถ้า status ว่างหรือถูกปิดอยู่ ให้กลับเป็น open
            if ($conversation->status === null || $conversation->status === 'closed') {
                $conversation->status = 'open';
                $conversation->closed_by_employee_id = null;
                $conversation->closed_by_employee_name = null;
                $conversation->closed_at = null;
            }

            $conversation->save();

            // 5) update contact last_seen
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

            // 7) reload relation (pattern เดิม)
            $conversation = $conversation->fresh(['contact.member', 'account']);

            $message->setRelation('conversation', $conversation);
            $message->setRelation('contact', $contact);

            // 8) broadcast realtime ให้หน้าแอดมิน
            DB::afterCommit(function () use ($conversation, $message) {
                // ยิง event เดิมให้ message ไปถึงห้องแชต
                event(new LineOAChatMessageReceived(
                    $conversation,
                    $message
                ));

                // ยิง event ห้อง เพื่ออัปเดต list ซ้าย (line_account / last_message / unread)
                event(new LineOAChatConversationUpdated($conversation));
            });

            return $message;
        });
    }

    protected function attachLineMediaContentIfNeeded(LineAccount $account, LineMessage $message): void
    {
        try {
            $payload = $message->payload ?? [];
            $msgPayload = $payload['message'] ?? null;

            if (! is_array($msgPayload)) {
                return;
            }

            // ถ้ามี contentUrl แล้ว (เช่นเคยประมวลผลไว้แล้ว) ไม่ต้องทำซ้ำ
            if (! empty($msgPayload['contentUrl'])) {
                return;
            }

            $contentProviderType = $msgPayload['contentProvider']['type'] ?? null;
            if ($contentProviderType !== 'line') {
                // ถ้าเป็น external หรืออื่น ๆ ไม่ยุ่ง
                return;
            }

            $lineMessageId = (string) ($message->line_message_id ?? '');
            if ($lineMessageId === '') {
                return;
            }

            /** @var LineMessagingClient $client */
            $client = app(LineMessagingClient::class);

            $result = $client->downloadMessageContent($account, $lineMessageId);

            if (! ($result['success'] ?? false)) {
                Log::channel('line_oa')->warning('[LineChat] downloadMessageContent failed', [
                    'message_id' => $message->id,
                    'line_message_id' => $lineMessageId,
                    'type' => $message->type,
                    'status' => $result['status'] ?? null,
                    'error' => $result['error'] ?? null,
                ]);

                return;
            }

            $binary = $result['body'] ?? null;
            if ($binary === null || $binary === '') {
                Log::channel('line_oa')->warning('[LineChat] downloadMessageContent empty body', [
                    'message_id' => $message->id,
                    'line_message_id' => $lineMessageId,
                    'type' => $message->type,
                ]);

                return;
            }

            $mime = $result['content_type'] ?? null;
            $ext = $this->guessExtensionByMime($mime, $message->type);

            // แยกโฟลเดอร์ตามชนิด (จัดระเบียบ + ง่ายต่อการตั้ง retention policy ในอนาคต)
            $folder = match ($message->type) {
                'audio' => 'line-oa/inbound/audio',
                'video' => 'line-oa/inbound/video',
                default => 'line-oa/inbound/image',
            };

            $path = $folder.'/'.$lineMessageId.'.'.$ext;

            Storage::disk('public')->put($path, $binary);
            $url = Storage::disk('public')->url($path);

            // update payload ให้ Vue ใช้เปิด/เล่นได้
            $payload['message']['contentUrl'] = $url;

            // สำหรับรูป: ใส่ previewUrl ให้ด้วย (UI เดิมมักใช้ previewUrl)
            if ($message->type === 'image') {
                $payload['message']['previewUrl'] = $payload['message']['previewUrl'] ?? $url;
            }

            $message->payload = $payload;

            // เติม meta ช่วย debug / UI ได้ (ไม่บังคับ frontend ใช้)
            $meta = $message->meta;
            if (! is_array($meta)) {
                $meta = $meta ? (array) $meta : [];
            }

            $meta['media'] = array_merge($meta['media'] ?? [], [
                'url' => $url,
                'mime' => $mime,
                'ext' => $ext,
                'size' => $result['content_length'] ?? null,
                'duration' => $payload['message']['duration'] ?? null,
            ]);

            $message->meta = $meta;
            $message->save();
        } catch (\Throwable $e) {
            Log::channel('line_oa')->error('[LineChat] attachLineMediaContentIfNeeded exception', [
                'message_id' => $message->id,
                'line_message_id' => $message->line_message_id,
                'type' => $message->type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * เดา extension จาก Content-Type
     *
     * หมายเหตุ:
     * - audio ของ LINE มักเป็น audio/mp4 (m4a)
     * - video มักเป็น video/mp4
     */
    protected function guessExtensionByMime(?string $mime, ?string $fallbackType = null): string
    {
        $mime = $mime ? strtolower(trim(explode(';', $mime)[0])) : null;

        if ($mime) {
            return match ($mime) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',

                'audio/mpeg' => 'mp3',
                'audio/mp3' => 'mp3',
                'audio/wav', 'audio/x-wav' => 'wav',
                'audio/ogg' => 'ogg',
                'audio/aac' => 'aac',
                'audio/mp4', 'audio/m4a', 'audio/x-m4a' => 'm4a',

                'video/mp4' => 'mp4',
                'video/quicktime' => 'mov',
                'video/webm' => 'webm',
                default => $this->fallbackExtensionByType($fallbackType),
            };
        }

        return $this->fallbackExtensionByType($fallbackType);
    }

    protected function fallbackExtensionByType(?string $type): string
    {
        return match ($type) {
            'audio' => 'm4a',
            'video' => 'mp4',
            'image' => 'jpg',
            default => 'bin',
        };
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

        $conversation->loadMissing('account');

        $account = $conversation->account;

        if (! $account || $account->status !== 'active') {
            Log::channel('line_oa')->warning('[LineChat] createOutboundMessageFromAgent blocked: account not active', [
                'conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'account_status' => $account->status ?? null,
                'employee_id' => $employeeId,
            ]);

            // ตรงนี้มี 2 แนวทาง แล้วแต่คุณจะเลือกใช้งาน

            // 1) ถ้าคุณรับได้กับการโยน exception (caller ต้อง try/catch เอง)
            throw new \RuntimeException('LINE account is not active.');
            // 2) หรือถ้าอยากให้เมธอดนี้ “ไม่ถูกเรียกเลย” ตอน account ปิด
            //    แนะนำให้ย้าย if-block นี้ไปอยู่ในเมธอดระดับสูงกว่า (เช่น sendAgentTextMessage ใน ChatService)
            //    แทนที่จะเช็กใน createOutboundMessageFromAgent (จะไม่ต้องเปลี่ยน contract ที่นี่)
        }
        // เตรียมข้อมูลแปล สำหรับข้อความขาออก (แต่ยังไม่เปลี่ยนค่า text เดิม)
        $targetLang = 'th';
        $translation = null;

        if ($this->translator->isEnabled()) {
            $targetLang = $this->translator->resolveTargetLanguage(
                $conversation->outgoing_language,
                optional($conversation->contact)->preferred_language,
                'th'
            );

            // ถ้าภาษาปลายทางไม่ใช่ไทย → แปลจากไทยไปภาษาลูกค้า
            if ($targetLang !== 'th') {
                $translation = $this->translator->translate($text, $targetLang, 'th');
            }
        }

        $now = now();

        return DB::transaction(function () use ($conversation, $text, $employeeId, $meta, $now, $translation, $targetLang) {
            $metaPayload = $meta ?? [];
            if (! is_array($metaPayload)) {
                $metaPayload = (array) $metaPayload;
            }

            if ($translation) {
                $metaPayload['translation_outbound'] = [
                    'provider' => $translation['provider'] ?? null,
                    'original_text' => $translation['original_text'] ?? $text,
                    'translated_text' => $translation['translated_text'] ?? $text,
                    'source_language' => $translation['source'] ?? 'th',
                    'target_language' => $translation['target'] ?? $targetLang,
                ];
            }

            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'line_contact_id' => $conversation->line_contact_id,
                'direction' => 'outbound',
                'source' => 'agent',
                'type' => 'text',
                'line_message_id' => null,
                'text' => $text, // เก็บข้อความที่ agent พิม (ไทย)
                'payload' => null,
                'meta' => $metaPayload,
                'sender_employee_id' => $employeeId,
                'sender_bot_key' => null,
                'sent_at' => $now,
            ]);

            // ถ้าห้องปิดอยู่ หรือ status ยังไม่ถูกกำหนด → เปิดห้องอีกครั้ง
            if ($conversation->status === 'closed' || $conversation->status === null) {
                $conversation->status = 'open';
                $conversation->closed_by_employee_id = null;
                $conversation->closed_by_employee_name = null;
                $conversation->closed_at = null;

            }

            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $now;
            $conversation->unread_count = 0;
            $conversation->save();

            DB::afterCommit(function () use ($conversation, $message) {

                // conversation with relations
                $convFresh = $conversation->fresh([
                    'contact.member',
                    'account',
                    'registerSessions' => function ($q) {
                        $q->where('status', 'in_progress');
                    },
                ]) ?? $conversation;

                // message with relations
                $msgFresh = $message->fresh([
                    'conversation.contact.member',
                    'conversation.account',
                ]) ?? $message;

                // สำหรับ list ห้องด้านซ้าย
                event(new LineOAChatConversationUpdated($convFresh));

                // สำหรับ timeline ของห้องแชต (bubble)
                broadcast(new LineOAChatMessageReceived($convFresh, $msgFresh))->toOthers();
            });

            return $message;
        });
    }

    public function createOutboundMessageFromBot(
        LineConversation $conversation,
        string $text,
        ?string $botKey = null,
        ?array $meta = null
    ): LineMessage {
        $now = now();

        $conversation->loadMissing('account');

        $account = $conversation->account;

        if (! $account || $account->status !== 'active') {
            Log::channel('line_oa')->warning('[LineChat] createOutboundMessageFromAgent blocked: account not active', [
                'conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'account_status' => $account->status ?? null,
                'employee_id' => 0,
            ]);

            // ตรงนี้มี 2 แนวทาง แล้วแต่คุณจะเลือกใช้งาน

            // 1) ถ้าคุณรับได้กับการโยน exception (caller ต้อง try/catch เอง)
            throw new \RuntimeException('LINE account is not active.');
            // 2) หรือถ้าอยากให้เมธอดนี้ “ไม่ถูกเรียกเลย” ตอน account ปิด
            //    แนะนำให้ย้าย if-block นี้ไปอยู่ในเมธอดระดับสูงกว่า (เช่น sendAgentTextMessage ใน ChatService)
            //    แทนที่จะเช็กใน createOutboundMessageFromAgent (จะไม่ต้องเปลี่ยน contract ที่นี่)
        }

        return DB::transaction(function () use ($conversation, $text, $botKey, $meta, $now) {

            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'line_contact_id' => $conversation->line_contact_id,
                'direction' => 'outbound',
                'source' => 'bot',
                'type' => 'text',
                'line_message_id' => null,
                'text' => $text,
                'payload' => null,
                'meta' => $meta,
                'sender_employee_id' => null,
                'sender_bot_key' => $botKey,
                'sent_at' => $now,
            ]);

            // อัปเดต conversation ให้ list ห้องกับ header ใช้
            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $now;
            // policy ตอนนี้: บอทตอบไม่เพิ่ม unread
            // $conversation->unread_count      = 0;
            $conversation->save();

            DB::afterCommit(function () use ($conversation, $message) {

                // conversation with relations
                $convFresh = $conversation->fresh([
                    'contact.member',
                    'account',
                    'registerSessions' => function ($q) {
                        $q->where('status', 'in_progress');
                    },
                ]) ?? $conversation;

                // message with relations
                $msgFresh = $message->fresh([
                    'conversation.contact.member',
                    'conversation.account',
                ]) ?? $message;

                // สำหรับ list ห้องด้านซ้าย
                event(new LineOAChatConversationUpdated($convFresh));

                // สำหรับ timeline ของห้องแชต (bubble)
                event(new LineOAChatMessageReceived($convFresh, $msgFresh));
            });

            return $message;
        });
    }

    public function createOutboundStickerFromAgent(
        LineConversation $conversation,
        string $packageId,
        string $stickerId,
        int $employeeId,
        ?array $meta = null
    ): LineMessage {
        $now = now();

        $conversation->loadMissing('account');

        $account = $conversation->account;

        if (! $account || $account->status !== 'active') {
            Log::channel('line_oa')->warning('[LineChat] createOutboundMessageFromAgent blocked: account not active', [
                'conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'account_status' => $account->status ?? null,
                'employee_id' => $employeeId,
            ]);

            // ตรงนี้มี 2 แนวทาง แล้วแต่คุณจะเลือกใช้งาน

            // 1) ถ้าคุณรับได้กับการโยน exception (caller ต้อง try/catch เอง)
            throw new \RuntimeException('LINE account is not active.');
            // 2) หรือถ้าอยากให้เมธอดนี้ “ไม่ถูกเรียกเลย” ตอน account ปิด
            //    แนะนำให้ย้าย if-block นี้ไปอยู่ในเมธอดระดับสูงกว่า (เช่น sendAgentTextMessage ใน ChatService)
            //    แทนที่จะเช็กใน createOutboundMessageFromAgent (จะไม่ต้องเปลี่ยน contract ที่นี่)
        }

        return DB::transaction(function () use ($conversation, $packageId, $stickerId, $employeeId, $meta, $now) {
            // เตรียม meta ให้เป็น array
            $metaPayload = $meta ?? [];
            if (! is_array($metaPayload)) {
                $metaPayload = (array) $metaPayload;
            }

            // ฝังข้อมูลสติกเกอร์ลง meta เพื่อให้ UI นำไปใช้แสดงผลได้
            $metaPayload['sticker'] = [
                'package_id' => $packageId,
                'packageId' => $packageId,
                'sticker_id' => $stickerId,
                'stickerId' => $stickerId,
                'stickerResourceType' => 'STATIC',
            ];

            // ✅ payload ที่เราจะยิงออกไปจริง ๆ (สำหรับเก็บลง DB)
            $payloadForLine = [
                'type' => 'sticker',
                'packageId' => $packageId,
                'stickerId' => $stickerId,
            ];

            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'line_contact_id' => $conversation->line_contact_id,
                'direction' => 'outbound',
                'source' => 'agent',
                'type' => 'sticker',
                'line_message_id' => null,
                'text' => null,     // ไม่มีข้อความตัวอักษร
                'payload' => $payloadForLine,     // ถ้าอยากเก็บ raw sticker payload เพิ่ม ค่อยมาเติมทีหลังได้
                'meta' => $metaPayload,
                'sender_employee_id' => $employeeId,
                'sender_bot_key' => null,
                'sent_at' => $now,
            ]);

            // ถ้าห้องปิดอยู่ หรือ status ยังไม่ถูกกำหนด → เปิดห้องอีกครั้ง (อิง logic เดียวกับข้อความ text)
            if ($conversation->status === 'closed' || $conversation->status === null) {
                $conversation->status = 'open';
                $conversation->closed_by_employee_id = null;
                $conversation->closed_by_employee_name = null;
                $conversation->closed_at = null;
            }

            // อัปเดต preview + เวลาข้อความล่าสุด
            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $now;
            $conversation->unread_count = 0;
            $conversation->save();

            // broadcast ให้ list ซ้ายของทุกคนอัปเดต (อิง pattern เดียวกับฟังก์ชันที่คุณส่งมา)
            DB::afterCommit(function () use ($conversation, $message) {

                // conversation with relations
                $convFresh = $conversation->fresh([
                    'contact.member',
                    'account',
                    'registerSessions' => function ($q) {
                        $q->where('status', 'in_progress');
                    },
                ]) ?? $conversation;

                // message with relations
                $msgFresh = $message->fresh([
                    'conversation.contact.member',
                    'conversation.account',
                ]) ?? $message;

                // สำหรับ list ห้องด้านซ้าย
                event(new LineOAChatConversationUpdated($convFresh));

                // สำหรับ timeline ของห้องแชต (bubble)
                broadcast(new LineOAChatMessageReceived($convFresh, $msgFresh))->toOthers();
            });

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

        $conversation->loadMissing('account');

        $account = $conversation->account;

        if (! $account || $account->status !== 'active') {
            Log::channel('line_oa')->warning('[LineChat] createOutboundMessageFromAgent blocked: account not active', [
                'conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'account_status' => $account->status ?? null,
                'employee_id' => $employeeId,
            ]);

            // ตรงนี้มี 2 แนวทาง แล้วแต่คุณจะเลือกใช้งาน

            // 1) ถ้าคุณรับได้กับการโยน exception (caller ต้อง try/catch เอง)
            throw new \RuntimeException('LINE account is not active.');
            // 2) หรือถ้าอยากให้เมธอดนี้ “ไม่ถูกเรียกเลย” ตอน account ปิด
            //    แนะนำให้ย้าย if-block นี้ไปอยู่ในเมธอดระดับสูงกว่า (เช่น sendAgentTextMessage ใน ChatService)
            //    แทนที่จะเช็กใน createOutboundMessageFromAgent (จะไม่ต้องเปลี่ยน contract ที่นี่)
        }

        return DB::transaction(function () use ($conversation, $file, $employeeId, $meta, $now) {
            // upload รูปไปที่ storage (disk public)
            $path = $file->store('line-oa/images', 'public');
            $url = Storage::disk('public')->url($path);

            $payload = [
                'message' => [
                    'type' => 'image',
                    'contentUrl' => $url,
                    'previewUrl' => $url,
                    'originalContentUrl' => $url,
                    'previewImageUrl' => $url,
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

            // ถ้าห้องปิดอยู่ หรือ status ยังไม่ถูกกำหนด → เปิดห้องอีกครั้ง
            if ($conversation->status === 'closed' || $conversation->status === null) {
                $conversation->status = 'open';
                $conversation->closed_by_employee_id = null;
                $conversation->closed_by_employee_name = null;
                $conversation->closed_at = null;
            }

            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $now;
            $conversation->unread_count = 0;
            $conversation->save();

            DB::afterCommit(function () use ($conversation, $message) {

                // conversation with relations
                $convFresh = $conversation->fresh([
                    'contact.member',
                    'account',
                    'registerSessions' => function ($q) {
                        $q->where('status', 'in_progress');
                    },
                ]) ?? $conversation;

                // message with relations
                $msgFresh = $message->fresh([
                    'conversation.contact.member',
                    'conversation.account',
                ]) ?? $message;

                // สำหรับ list ห้องด้านซ้าย
                event(new LineOAChatConversationUpdated($convFresh));

                // สำหรับ timeline ของห้องแชต (bubble)
                broadcast(new LineOAChatMessageReceived($convFresh, $msgFresh))->toOthers();
            });

            return $message;
        });
    }

    public function createOutboundImageFromAgentUrl(
        LineConversation $conversation,
        string $url,
        int $employeeId,
        ?array $meta = null
    ): LineMessage {
        $now = now();

        $conversation->loadMissing('account');

        $account = $conversation->account;

        if (! $account || $account->status !== 'active') {
            Log::channel('line_oa')->warning('[LineChat] createOutboundImageFromAgentUrl blocked: account not active', [
                'conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'account_status' => $account->status ?? null,
                'employee_id' => $employeeId,
                'url' => $url,
            ]);

            throw new \RuntimeException('LINE account is not active.');
        }

        // กัน URL ว่าง/มั่วแบบเร็ว ๆ (ไม่ต้อง validate หนัก ๆ)
        $url = trim($url);
        if ($url === '') {
            throw new \InvalidArgumentException('Image url is empty.');
        }

        return DB::transaction(function () use ($conversation, $url, $employeeId, $meta, $now) {
            $payload = [
                'message' => [
                    'type' => 'image',
                    'contentUrl' => $url,
                    'previewUrl' => $url,
                    'originalContentUrl' => $url,
                    'previewImageUrl' => $url,
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

            if ($conversation->status === 'closed' || $conversation->status === null) {
                $conversation->status = 'open';
                $conversation->closed_by_employee_id = null;
                $conversation->closed_by_employee_name = null;
                $conversation->closed_at = null;
            }

            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $now;
            $conversation->unread_count = 0;
            $conversation->save();

            DB::afterCommit(function () use ($conversation, $message) {
                $convFresh = $conversation->fresh([
                    'contact.member',
                    'account',
                    'registerSessions' => function ($q) {
                        $q->where('status', 'in_progress');
                    },
                ]) ?? $conversation;

                $msgFresh = $message->fresh([
                    'conversation.contact.member',
                    'conversation.account',
                ]) ?? $message;

                event(new LineOAChatConversationUpdated($convFresh));
                broadcast(new LineOAChatMessageReceived($convFresh, $msgFresh))->toOthers();
            });

            return $message;
        });
    }

    /**
     * ใช้ตอนฝั่งแอดมินส่ง Quick Reply / Template ออกไป
     * (ข้อความที่มาจาก LineTemplate + ถูก render แล้ว)
     */
    public function createOutboundQuickReplyFromAgent(
        LineConversation $conversation,
        string $previewText,
        int $employeeId,
        array $payload,
        ?array $meta = null
    ): LineMessage {
        $now = now();

        $conversation->loadMissing('account');

        $account = $conversation->account;

        if (! $account || $account->status !== 'active') {
            Log::channel('line_oa')->warning('[LineChat] createOutboundMessageFromAgent blocked: account not active', [
                'conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'account_status' => $account->status ?? null,
                'employee_id' => $employeeId,
            ]);

            // ตรงนี้มี 2 แนวทาง แล้วแต่คุณจะเลือกใช้งาน

            // 1) ถ้าคุณรับได้กับการโยน exception (caller ต้อง try/catch เอง)
            throw new \RuntimeException('LINE account is not active.');
            // 2) หรือถ้าอยากให้เมธอดนี้ “ไม่ถูกเรียกเลย” ตอน account ปิด
            //    แนะนำให้ย้าย if-block นี้ไปอยู่ในเมธอดระดับสูงกว่า (เช่น sendAgentTextMessage ใน ChatService)
            //    แทนที่จะเช็กใน createOutboundMessageFromAgent (จะไม่ต้องเปลี่ยน contract ที่นี่)
        }

        return DB::transaction(function () use ($conversation, $previewText, $employeeId, $payload, $meta, $now) {
            $metaPayload = $meta ?? [];
            if (! is_array($metaPayload)) {
                $metaPayload = (array) $metaPayload;
            }

            /** @var LineMessage $message */
            $message = LineMessage::create([
                'line_conversation_id' => $conversation->id,
                'line_account_id' => $conversation->line_account_id,
                'line_contact_id' => $conversation->line_contact_id,
                'direction' => 'outbound',
                'source' => 'quick_reply',
                'type' => 'text', // ในหลังบ้านให้แสดงเป็น bubble ข้อความ
                'line_message_id' => null,
                'text' => $previewText,
                'payload' => $payload,
                'meta' => $metaPayload,
                'sender_employee_id' => $employeeId,
                'sender_bot_key' => null,
                'sent_at' => $now,
            ]);

            // ถ้าห้องปิดอยู่ หรือ status ยังไม่ถูกกำหนด → เปิดห้องอีกครั้ง
            if ($conversation->status === 'closed' || $conversation->status === null) {
                $conversation->status = 'open';
                $conversation->closed_by_employee_id = null;
                $conversation->closed_by_employee_name = null;
                $conversation->closed_at = null;
            }

            $conversation->last_message_preview = $this->buildPreviewText($message);
            $conversation->last_message_at = $now;
            $conversation->unread_count = 0;
            $conversation->save();

            DB::afterCommit(function () use ($conversation, $message) {

                // conversation with relations
                $convFresh = $conversation->fresh([
                    'contact.member',
                    'account',
                    'registerSessions' => function ($q) {
                        $q->where('status', 'in_progress');
                    },
                ]) ?? $conversation;

                // message with relations
                $msgFresh = $message->fresh([
                    'conversation.contact.member',
                    'conversation.account',
                ]) ?? $message;

                // สำหรับ list ห้องด้านซ้าย
                event(new LineOAChatConversationUpdated($convFresh));

                // สำหรับ timeline ของห้องแชต (bubble)
                broadcast(new LineOAChatMessageReceived($convFresh, $msgFresh))->toOthers();
            });

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
     * - พยายาม reuse ห้องที่ยังไม่ปิดเคส (status = null/open/assigned)
     * - ถ้าไม่มีเลย → fallback ไปใช้ห้องล่าสุดทุกสถานะ (รวม closed)
     * - ถ้าเจอห้องที่ closed/null → reopen เป็น open
     */
    protected function getOrCreateConversation(LineAccount $account, LineContact $contact): LineConversation
    {
        /** @var LineConversation|null $conversation */
        $conversation = LineConversation::where('line_account_id', $account->id)
            ->where('line_contact_id', $contact->id)
            ->where(function ($q) {
                // ใช้ห้องเดิมทุกกรณีที่ “ยังไม่ปิดเคส”
                $q->whereNull('status')
                    ->orWhereIn('status', ['open', 'assigned']);
            })
            ->orderByDesc('id')
            ->first();

        // ถ้าไม่มีห้องที่เปิดอยู่เลย → ลองเอาห้องล่าสุดทุกสถานะ (รวม closed)
        if (! $conversation) {
            $conversation = LineConversation::where('line_account_id', $account->id)
                ->where('line_contact_id', $contact->id)
                ->orderByDesc('id')
                ->first();
        }

        // ถ้ายังไม่เคยมีห้องเลย → สร้างใหม่
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

            return $conversation;
        }

        // ถ้ามีห้องเดิมแล้ว แต่สถานะเป็น closed หรือยังไม่เคย set → reopen เป็น open
        if ($conversation->status === 'closed' || $conversation->status === null) {
            $conversation->status = 'open';
            $conversation->closed_by_employee_id = null;
            $conversation->closed_by_employee_name = null;
            $conversation->closed_at = null;
            $conversation->save();
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
                    'message_id' => $message->id,
                    'line_message_id' => $lineMessageId,
                    'status' => $result['status'] ?? null,
                ]);

                return;
            }

            $binary = $result['body'] ?? null;
            if ($binary === null || $binary === '') {
                \Log::channel('line_oa')->warning('[LineChat] ดึง content รูปจาก LINE ได้ body ว่าง', [
                    'message_id' => $message->id,
                    'line_message_id' => $lineMessageId,
                ]);

                return;
            }

            // เซฟไฟล์ลง disk public
            $ext = 'jpg'; // LINE image ส่วนใหญ่เป็น jpeg; ถ้าจะละเอียดค่อยตรวจ header ทีหลังได้
            $path = 'line-oa/inbound/'.$lineMessageId.'.'.$ext;

            Storage::disk('public')->put($path, $binary);
            $url = Storage::disk('public')->url($path);

            $payload['message']['contentUrl'] = $url;
            $payload['message']['previewUrl'] = $payload['message']['previewUrl'] ?? $url;

            $message->payload = $payload;
            $message->save();
        } catch (\Throwable $e) {
            \Log::channel('line_oa')->error('[LineChat] exception ขณะดึง content รูปจาก LINE', [
                'message_id' => $message->id,
                'line_message_id' => $message->line_message_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * จัดการภาษา/การแปลสำหรับข้อความ inbound แบบ text
     *
     * - detect language จากข้อความลูกค้า
     * - อัปเดต language fields บน contact / conversation
     * - แปลข้อความให้ agent อ่าน (target = 'th') แล้วเก็บไว้ใน meta.translation_inbound
     */
    protected function handleInboundTextLanguageAndTranslation(
        LineConversation $conversation,
        LineContact $contact,
        LineMessage $message
    ): void {
        if (! $this->translator->isEnabled()) {
            return;
        }

        $text = trim((string) $message->text);
        if ($text === '') {
            return;
        }

        $targetLang = 'th';

        $result = $this->translator->translate($text, $targetLang, null);

        $detected = $result['detected_source'] ?? $result['source'] ?? null;

        // อัปเดต language บน contact/conversation
        if ($detected) {
            $contact->last_detected_language = $detected;

            if (empty($contact->preferred_language)) {
                $contact->preferred_language = $detected;
            }
            $contact->save();

            $conversation->incoming_language = $detected;

            // ถ้ายังไม่เคยตั้ง outgoing_language และภาษาลูกค้าไม่ใช่ไทย → default ให้ตามลูกค้า
            if (empty($conversation->outgoing_language) && $detected !== $targetLang) {
                $conversation->outgoing_language = $detected;
            }
            $conversation->save();
        }

        // เก็บผลการแปลลง meta.translation_inbound โดยไม่แก้ text เดิม
        $meta = $message->meta;
        if (! is_array($meta)) {
            $meta = $meta ? (array) $meta : [];
        }

        $meta['translation_inbound'] = [
            'provider' => $result['provider'] ?? null,
            'original_text' => $result['original_text'] ?? $text,
            'translated_text' => $result['translated_text'] ?? $text,
            'source_language' => $result['source'] ?? null,
            'detected_source' => $detected,
            'target_language' => $result['target'] ?? $targetLang,
            'for_agent_display_lang' => $targetLang,
        ];

        $message->meta = $meta;
        $message->save();
    }

    /**
     * สร้างข้อความ preview ให้ใช้ใน list conversation
     */
    protected function buildPreviewText(LineMessage $message): string
    {
        if ($message->type === 'text' && $message->text) {
            $text = $message->text;

            // ตัดความยาวไม่ให้ยาวเกินไป
            return mb_strimwidth($text, 0, 40, '...');
        }

        // สำหรับประเภทอื่น ๆ เช่น image/sticker/template
        return '['.$message->type.']';
    }

    public function markConversationAsRead(LineConversation $conversation): void
    {
        $conversation->unread_count = 0;
        $conversation->save();

        DB::afterCommit(function () use ($conversation) {
            event(new LineOAChatConversationUpdated(
                $conversation->fresh(['contact.member', 'account']) ?? $conversation
            ));
        });
    }

    /**
     * ปิดห้องสนทนา
     */
    public function closeConversation(LineConversation $conversation, ?int $employeeId = null): void
    {
        $conversation->status = 'closed';
        $conversation->closed_at = now();
        $conversation->closed_by = $employeeId;
        $conversation->unread_count = 0;
        $conversation->save();

        DB::afterCommit(function () use ($conversation) {
            event(new LineOAChatConversationUpdated(
                $conversation->fresh(['contact.member', 'account']) ?? $conversation
            ));
        });
    }
}
