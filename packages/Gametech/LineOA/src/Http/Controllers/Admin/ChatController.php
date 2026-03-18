<?php

namespace Gametech\LineOA\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Admin\Models\Admin;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\LineOA\Events\LineOAChatConversationUpdated;
use Gametech\LineOA\Events\LineOAChatTypingUpdated;
use Gametech\LineOA\Events\LineOAConversationAssigned;
use Gametech\LineOA\Events\LineOAConversationClosed;
use Gametech\LineOA\Events\LineOAConversationLocked;
use Gametech\LineOA\Events\LineOAConversationOpen;
use Gametech\LineOA\Models\LineContact;
use Gametech\LineOA\Models\LineConversation;
use Gametech\LineOA\Models\LineConversationNote;
use Gametech\LineOA\Models\LineMessage;
use Gametech\LineOA\Models\LineRegisterSession;
use Gametech\LineOA\Models\LineTemplate;
use Gametech\LineOA\Services\ChatService;
use Gametech\LineOA\Services\LineMessagingClient;
use Gametech\LineOA\Services\RegisterFlowService;
use Gametech\LineOA\Support\UrlHelper;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ChatController extends AppBaseController
{
    protected ChatService $chat;

    protected LineMessagingClient $lineMessaging;

    public function __construct(ChatService $chat, LineMessagingClient $lineMessaging)
    {
        $this->chat = $chat;
        $this->lineMessaging = $lineMessaging;
    }

    /**
     * แสดงหน้าแชต (Blade + Vue UI)
     */
    public function page()
    {
        return view('admin::module.line-oa.index');
    }

    /**
     * ดึง list ห้องแชต (sidebar ซ้าย)
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status', 'open'); // open | closed (UI)
        $accountId = $request->get('account_id');
        $q = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 5);
        $scope = $request->get('scope', 'all'); // all | mine
        $page = max(1, (int) $request->get('page', 1));

        $query = LineConversation::query()
            ->with([
                'contact.member',
                'account',
                'registerSessions' => function ($q) {
                    $q->where('status', 'in_progress');
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        // ===== filter ตาม OA =====
        if ($accountId) {
            $query->where('line_account_id', $accountId);
        }

        // ===== filter ตาม scope =====
        if ($scope === 'mine') {
            $employee = Auth::guard('admin')->user();
            if ($employee) {
                // ให้ตรงกับที่ assign ตอนรับเรื่อง
                $employeeId = $employee->code ?? $employee->id ?? null;
                if ($employeeId) {
                    $query->where('assigned_employee_id', $employeeId);
                }
            }
        }

        // ===== filter ตาม status =====
        if ($status === 'closed') {
            // เคสที่ปิดแล้วเท่านั้น
            $query->where('status', 'closed');
        } elseif ($status === 'assigned') {
            // เคสที่ปิดแล้วเท่านั้น
            $query->where('status', 'assigned');

        } else {
            // “ยังไม่ปิดเคส”
            $query->where(function ($qBuilder) {
                $qBuilder->whereNull('status')
                    ->orWhereIn('status', ['open', 'assigned', 'closed']);
            });
        }

        // ===== คำค้นหา =====
        if ($q !== '') {
            $query->whereHas('contact', function ($qQuery) use ($q) {
                $qQuery->where('display_name', 'like', '%'.$q.'%')
                    ->orWhere('member_username', 'like', '%'.$q.'%')
                    ->orWhere('member_mobile', 'like', '%'.$q.'%');
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        Log::channel('line_oa')->info('pagination', ['page' => $paginator]);

        $data = [
            'data' => $paginator->getCollection()->map(function (LineConversation $conv) {
                return [
                    'id' => $conv->id,

                    // ✅ สำคัญ: top-level ids (หลายหน้าจะ filter/route จาก 2 ตัวนี้)
                    'line_account_id' => (int) $conv->line_account_id,
                    'line_contact_id' => (int) $conv->line_contact_id,

                    'status' => $conv->status,
                    'last_message' => $conv->last_message_preview,
                    'last_message_at' => optional($conv->last_message_at)->toIso8601String(),
                    'unread_count' => $conv->unread_count,
                    'is_registering' => $conv->is_registering,
                    // *** ที่ต้องส่งเพิ่ม ***
                    'assigned_employee_id' => $conv->assigned_employee_id,
                    'assigned_employee_name' => $conv->assigned_employee_name,
                    'assigned_at' => optional($conv->assigned_at)->toIso8601String(),

                    'locked_by_employee_id' => $conv->locked_by_employee_id,
                    'locked_by_employee_name' => $conv->locked_by_employee_name,
                    'locked_at' => optional($conv->locked_at)->toIso8601String(),

                    'closed_by_employee_id' => $conv->closed_by_employee_id,
                    'closed_by_employee_name' => $conv->closed_by_employee_name,
                    'closed_at' => optional($conv->closed_at)->toIso8601String(),
                    'is_pinned' => (bool) $conv->is_pinned,

                    'line_account' => [
                        'id' => $conv->account?->id,
                        'name' => $conv->account?->name,
                    ],
                    'contact' => [
                        'id' => $conv->contact?->id,
                        'display_name' => $conv->contact?->display_name,
                        'member_id' => $conv->contact?->member_id,
                        'member_username' => $conv->contact?->member_username,
                        'member_mobile' => $conv->contact?->member_mobile,
                        'picture_url' => $conv->contact?->picture_url,
                        'member_name' => $conv->contact?->member?->name,
                        'member_bank_name' => $conv->contact?->member?->bank?->name_th,
                        'member_acc_no' => $conv->contact?->member?->acc_no,
                    ],
                ];
            }),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return response()->json($data);
    }

    /**
     * ดึงรายละเอียดห้อง + messages ล่าสุด
     */
    public function show(Request $request, LineConversation $conversation): JsonResponse
    {
        $limit = (int) $request->get('limit', 50);
        $beforeId = $request->get('before_id');
        $previousId = $request->get('previous_id');

        $conversation->load([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]);

        // ===== เคลียร์ unread ของห้องก่อนหน้า (ถ้ามีส่ง previous_id มา) =====
        if ($previousId && (int) $previousId !== (int) $conversation->id) {
            /** @var \Gametech\LineOA\Models\LineConversation|null $prevConv */
            $prevConv = LineConversation::query()->find($previousId);

            if ($prevConv && $prevConv->unread_count > 0) {
                $prevConv->unread_count = 0;
                $prevConv->save();

                DB::afterCommit(function () use ($prevConv) {
                    $conv = $prevConv->fresh([
                        'contact.member',
                        'account',
                        'registerSessions' => function ($q) {
                            $q->where('status', 'in_progress');
                        },
                    ]) ?? $prevConv;
                    event(new LineOAChatConversationUpdated($conv));
                });
            }
        }

        $messagesQuery = LineMessage::query()
            ->where('line_conversation_id', $conversation->id)
            ->orderByDesc('id');

        if ($beforeId) {
            $messagesQuery->where('id', '<', $beforeId);
        }

        $messages = $messagesQuery
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $markAsReadToken = null;
        foreach ($messages as $msg) {
            if ($msg->direction === 'inbound' && $msg->source === 'user') {
                $meta = $msg->meta ?? [];
                if (! empty($meta['mark_as_read_token'])) {
                    // เก็บตัวสุดท้าย (ล่าสุด) ทับไปเรื่อย ๆ
                    $markAsReadToken = $meta['mark_as_read_token'];
                }
            }
        }

        // clear unread
        if ($conversation->unread_count > 0) {

            // 1) ถ้ามี token + account → ยิงไปที่ LINE
            if ($markAsReadToken && $conversation->account) {
                $result = $this->lineMessaging->markMessagesAsRead($conversation->account, $markAsReadToken);

                if (! $result['success']) {
                    Log::warning('[LineChat] markMessagesAsRead ไม่สำเร็จ', [
                        'conversation_id' => $conversation->id,
                        'account_id' => $conversation->account?->id,
                        'error' => $result['error'] ?? null,
                        'status' => $result['status'] ?? null,
                    ]);
                }
            }

            // 2) ไม่ว่าจะ mark สำเร็จหรือไม่ → ถือว่าฝั่งระบบ “ถูกเปิดอ่านแล้ว”
            $conversation->unread_count = 0;
            $conversation->save();

            DB::afterCommit(function () use ($conversation) {
                $conv = $conversation->fresh([
                    'contact.member',
                    'account',
                    'registerSessions' => function ($q) {
                        $q->where('status', 'in_progress');
                    },
                ]) ?? $conversation;

                event(new LineOAChatConversationUpdated($conv));
            });
        }

        $data = [
            'conversation' => [
                'id' => $conversation->id,

                // ✅ สำคัญ: top-level ids (หลายหน้าจะ filter/route จาก 2 ตัวนี้)
                'line_account_id' => (int) $conversation->line_account_id,
                'line_contact_id' => (int) $conversation->line_contact_id,

                'status' => $conversation->status,
                'last_message_at' => optional($conversation->last_message_at)->toDateTimeString(),
                'unread_count' => $conversation->unread_count,
                'is_registering' => $conversation->is_registering,
                // *** ส่งเพิ่ม ***
                'assigned_employee_id' => $conversation->assigned_employee_id,
                'assigned_employee_name' => $conversation->assigned_employee_name,
                'assigned_at' => optional($conversation->assigned_at)->toIso8601String(),

                'locked_by_employee_id' => $conversation->locked_by_employee_id,
                'locked_by_employee_name' => $conversation->locked_by_employee_name,
                'locked_at' => optional($conversation->locked_at)->toIso8601String(),

                'closed_by_employee_id' => $conversation->closed_by_employee_id,
                'closed_by_employee_name' => $conversation->closed_by_employee_name,
                'closed_at' => optional($conversation->closed_at)->toIso8601String(),

                'is_pinned' => (bool) $conversation->is_pinned,

                'incoming_language' => $conversation->incoming_language,
                'outgoing_language' => $conversation->outgoing_language,

                'line_account' => [
                    'id' => $conversation->account?->id,
                    'name' => $conversation->account?->name,
                ],
                'contact' => [
                    'id' => $conversation->contact?->id,
                    'display_name' => $conversation->contact?->display_name,
                    'line_user_id' => $conversation->contact?->line_user_id,
                    'member_id' => $conversation->contact?->member_id,
                    'member_username' => $conversation->contact?->member_username,
                    'member_mobile' => $conversation->contact?->member_mobile,
                    'picture_url' => $conversation->contact?->picture_url,
                    'blocked_at' => optional($conversation->contact?->blocked_at)->toDateTimeString(),

                    'member_name' => $conversation->contact?->member?->name,
                    'member_bank_name' => $conversation->contact?->member?->bank?->name_th,
                    'member_acc_no' => $conversation->contact?->member?->acc_no,

                    'preferred_language' => $conversation->contact?->preferred_language,
                    'last_detected_language' => $conversation->contact?->last_detected_language,
                ],
            ],
            'messages' => $messages->map(function (LineMessage $m) {
                return [
                    'id' => $m->id,
                    'direction' => $m->direction,
                    'source' => $m->source,
                    'type' => $m->type,
                    'text' => $m->text,
                    'sent_at' => optional($m->sent_at)->toIso8601String(),
                    'sender_employee_id' => $m->sender_employee_id,
                    'sender_bot_key' => $m->sender_bot_key,
                    'meta' => $m->meta,
                    'payload' => $m->payload,
                    'is_pinned' => (bool) $m->is_pinned,
                ];
            }),
        ];

        return response()->json($data);
    }

    /**
     * สร้างโครง reply_to meta ให้ครอบคลุมทุกประเภท message
     *
     * structure ตัวอย่าง:
     *  - id, type, direction, source, sent_at
     *  - text        : ข้อความ preview ตัดความยาว
     *  - raw_text    : ข้อความดิบจาก message->text
     *  - preview_image: สำหรับ image/sticker/video
     *  - sticker     : สำหรับ type=sticker (packageId, stickerId, type)
     *  - image       : สำหรับ type=image (original, preview)
     *  - video       : สำหรับ type=video (original, preview)
     *  - audio       : สำหรับ type=audio (url, duration)
     *  - location    : สำหรับ type=location (title, address, lat, lng)
     */
    protected function buildReplyToMeta(LineMessage $replyTo): array
    {
        // base payload decode
        $payload = $replyTo->payload ?? [];
        if (is_string($payload)) {
            try {
                $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $payload = [];
            }
        }

        $messagePayload = [];
        if (is_array($payload)) {
            $messagePayload = $payload['message'] ?? $payload;
        }

        // base meta
        $rawText = (string) $replyTo->text;

        // preview text
        $previewText = $rawText;
        if ($previewText === '') {
            switch ($replyTo->type) {
                case 'image':
                    $previewText = '[รูปภาพ]';
                    break;
                case 'sticker':
                    $previewText = '[สติ๊กเกอร์]';
                    break;
                case 'video':
                    $previewText = '[วิดีโอ]';
                    break;
                case 'audio':
                    $previewText = '[เสียง]';
                    break;
                case 'location':
                    $previewText = '[ตำแหน่งที่ตั้ง]';
                    break;
                default:
                    $previewText = '['.($replyTo->type ?: 'ข้อความ').']';
                    break;
            }
        }
        $previewText = mb_strimwidth($previewText, 0, 80, '...');

        $meta = [
            'id' => $replyTo->id,
            'type' => $replyTo->type,
            'direction' => $replyTo->direction,
            'source' => $replyTo->source,
            'sent_at' => optional($replyTo->sent_at)->toIso8601String(),
            'text' => $previewText,
            'raw_text' => $rawText,
        ];

        // cast meta เดิม (ของ message ต้นทาง) เผื่อเอาข้อมูลมาช่วย
        $rawMeta = $replyTo->meta ?? [];
        if (is_string($rawMeta)) {
            try {
                $rawMeta = json_decode($rawMeta, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $rawMeta = [];
            }
        }
        if (! is_array($rawMeta)) {
            $rawMeta = [];
        }

        // ---------- แยกตาม type ----------
        switch ($replyTo->type) {
            case 'sticker':
                $pkg = $messagePayload['packageId'] ?? $messagePayload['package_id'] ?? null;
                $sid = $messagePayload['stickerId'] ?? $messagePayload['sticker_id'] ?? null;
                $type = $messagePayload['stickerResourceType'] ?? null;

                // fallback จาก meta.sticker (เช่น outbound agent)
                $metaSticker = $rawMeta['sticker'] ?? null;
                if (is_array($metaSticker)) {
                    $pkg = $pkg ?: ($metaSticker['packageId'] ?? $metaSticker['package_id'] ?? null);
                    $sid = $sid ?: ($metaSticker['stickerId'] ?? $metaSticker['sticker_id'] ?? null);
                    $type = $type ?: ($metaSticker['stickerResourceType'] ?? null);
                }

                if ($pkg && $sid) {
                    $type = $type ?: 'STATIC';

                    $meta['sticker'] = [
                        'packageId' => $pkg,
                        'package_id' => $pkg,
                        'stickerId' => $sid,
                        'sticker_id' => $sid,
                        'stickerResourceType' => $type,
                    ];

                    // preview_image สำหรับ sticker
                    if ($type === 'STATIC') {
                        $previewImage = "https://stickershop.line-scdn.net/stickershop/v1/sticker/{$sid}/android/sticker.png";
                    } elseif ($type === 'ANIMATION' || $type === 'ANIMATION_SOUND') {
                        $previewImage = "https://stickershop.line-scdn.net/stickershop/v1/sticker/{$sid}/android/sticker_animation.png";
                    } elseif ($type === 'POPUP') {
                        $previewImage = "https://stickershop.line-scdn.net/stickershop/v1/sticker/{$sid}/android/sticker_popup.png";
                    } else {
                        $previewImage = "https://stickershop.line-scdn.net/stickershop/v1/sticker/{$sid}/android/sticker.png";
                    }

                    $meta['preview_image'] = $previewImage;
                }
                break;

            case 'image':
                // รองรับทั้ง inbound (originalContentUrl/previewImageUrl) และ outbound (contentUrl/previewUrl)
                $original = $messagePayload['originalContentUrl']
                    ?? $messagePayload['contentUrl']
                    ?? $messagePayload['url']
                    ?? null;

                $preview = $messagePayload['previewImageUrl']
                    ?? $messagePayload['previewUrl']
                    ?? $messagePayload['thumbnailUrl']
                    ?? $original;

                if ($original) {
                    if (! preg_match('~^https?://~i', $original)) {
                        $original = url($original);
                    }
                }
                if ($preview) {
                    if (! preg_match('~^https?://~i', $preview)) {
                        $preview = url($preview);
                    }
                }

                if ($original || $preview) {
                    $meta['image'] = [
                        'original' => $original,
                        'preview' => $preview ?: $original,
                    ];
                    $meta['preview_image'] = $preview ?: $original;
                }
                break;

            case 'video':
                $original = $messagePayload['originalContentUrl']
                    ?? $messagePayload['contentUrl']
                    ?? $messagePayload['url']
                    ?? null;

                $preview = $messagePayload['previewImageUrl']
                    ?? $messagePayload['previewUrl']
                    ?? $messagePayload['thumbnailUrl']
                    ?? null;

                if ($original && ! preg_match('~^https?://~i', $original)) {
                    $original = url($original);
                }
                if ($preview && ! preg_match('~^https?://~i', $preview)) {
                    $preview = url($preview);
                }

                if ($original || $preview) {
                    $meta['video'] = [
                        'original' => $original,
                        'preview' => $preview,
                    ];
                    $meta['preview_image'] = $preview ?: null;
                }
                break;

            case 'audio':
                $audioUrl = $messagePayload['originalContentUrl']
                    ?? $messagePayload['contentUrl']
                    ?? $messagePayload['url']
                    ?? null;

                if ($audioUrl && ! preg_match('~^https?://~i', $audioUrl)) {
                    $audioUrl = url($audioUrl);
                }

                $duration = $messagePayload['duration'] ?? null;

                if ($audioUrl || $duration) {
                    $meta['audio'] = [
                        'url' => $audioUrl,
                        'duration' => $duration,
                    ];
                }
                break;

            case 'location':
                $title = $messagePayload['title'] ?? null;
                $address = $messagePayload['address'] ?? null;
                $latitude = $messagePayload['latitude'] ?? null;
                $longitude = $messagePayload['longitude'] ?? null;

                $meta['location'] = [
                    'title' => $title,
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ];
                break;

            default:
                // type อื่น ๆ ถ้ามี logic เสริมในอนาคต ก็เติมได้
                break;
        }

        return $meta;
    }

    /**
     * ส่งสัญญาณ "กำลังพิมพ์" ไปให้ทุกคนที่เปิดหน้าแอดมิน (global list ซ้าย)
     *
     * Route:
     *   POST /admin/line-oa/typing
     *
     * Body:
     *   - conversation_id: int (required)
     *   - is_typing: bool (required)
     */
    public function typing(Request $request): JsonResponse
    {
        $data = $request->validate([
            'conversation_id' => ['required', 'integer', 'min:1'],
            'is_typing' => ['required'],
        ]);

        $conversationId = (int) $data['conversation_id'];
        $isTyping = filter_var($data['is_typing'], FILTER_VALIDATE_BOOLEAN);

        $employee = Auth::guard('admin')->user();
        $employeeId = (int) ($employee?->code ?? 0);

        if ($employeeId <= 0) {
            return response()->json(['message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)'], 403);
        }

        $employeeName = (string) ($employee->user_name ?? ('EMP#'.$employeeId));

        // กันสแปม: จำกัดส่งถี่สุด 1 ครั้ง / 800ms ต่อ (employee+conversation+state)
        $throttleKey = 'lineoa:typing:'.$conversationId.':'.$employeeId.':'.($isTyping ? '1' : '0');
        if (Cache::has($throttleKey)) {
            return response()->json(['message' => 'throttled', 'data' => ['sent' => false]]);
        }
        Cache::put($throttleKey, 1, now()->addMilliseconds(800));

        event(new LineOAChatTypingUpdated(
            $conversationId,
            $employeeId,
            $employeeName,
            $isTyping
        ));

        return response()->json([
            'message' => 'success',
            'data' => [
                'sent' => true,
                'conversation_id' => $conversationId,
                'is_typing' => $isTyping,
            ],
        ]);
    }

    /**
     * ส่ง TEXT จาก admin
     */
    public function reply(Request $request, LineConversation $conversation): JsonResponse
    {
        // ... (ส่วนนี้เหมือนเดิมตามที่คุณส่งมา)
        // NOTE: ผมไม่ได้แก้ reply/replyImage/replySticker ในไฟล์เต็มนี้
        // เพราะจุดที่คุณโฟกัสคือ Template Quick Reply
        // (คุณสามารถคงของเดิมไว้ได้ 100%)

        // เพื่อให้ไฟล์นี้ “ครบถ้วนตามที่คุณส่งมา” ผมขอคงส่วน reply()/replyImage()/replySticker
        // ไว้เหมือนเดิม (ตามต้นฉบับของคุณ) — ตัดออกใน snippet นี้ไม่ได้
        // แต่ในข้อความด้านบนคุณใส่มาครบอยู่แล้ว จึงคงตามนั้น
        // -------------------------
        // เริ่ม: โค้ด reply() ของคุณ (คงเดิม)
        $data = $request->validate([
            'text' => ['required', 'string'],
            'reply_to_message_id' => ['nullable', 'integer'],
            'quick_reply_items' => ['nullable', 'array'],
        ]);

        $text = trim($data['text']);

        // รองรับกรณี UI ส่ง "template message JSON" มาในช่อง text โดยตรง
        // เช่น {"type":"text_quick_reply","text":"...","quick_reply_items":[...]}
        // จะช่วยให้ Quick Reply ติดไปด้วย โดยไม่กระทบข้อความปกติ
        if ($text !== '' && str_starts_with(ltrim($text), '{')) {
            $tmp = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($tmp) && isset($tmp['type'])) {
                $tType = (string) ($tmp['type'] ?? '');
                if ($tType === 'text_quick_reply' || $tType === 'image_quick_reply') {
                    if (! empty($tmp['text']) && is_string($tmp['text'])) {
                        $text = trim($tmp['text']);
                    }
                    if (empty($data['quick_reply_items']) && ! empty($tmp['quick_reply_items']) && is_array($tmp['quick_reply_items'])) {
                        $data['quick_reply_items'] = $tmp['quick_reply_items'];
                    }
                }
            }
        }

        Log::channel('line_oa')->warning('[Reply] start', [
            'data' => $data
        ]);

        if ($text === '') {
            return response()->json([
                'message' => 'ข้อความห้ามว่าง',
            ], 422);
        }

        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // ถ้าห้องเคยถูกปิดไว้ แล้วทีมงานตอบใหม่ → เปิดสถานะกลับเป็น open
        if ($conversation->status === 'closed') {
            $conversation->status = 'open';
            $conversation->closed_by_employee_id = null;
            $conversation->closed_by_employee_name = null;
            $conversation->closed_at = null;
            $conversation->save();
        }

        // meta พื้นฐาน
        $meta = [
            'employee_name' => $employee->user_name ?? null,
        ];

        // -------------------------
        // ใส่ข้อมูล reply_to ลงใน meta (ถ้ามี)
        // -------------------------
        $replyToId = $data['reply_to_message_id'] ?? null;

        if ($replyToId) {
            $replyTo = LineMessage::query()
                ->where('line_conversation_id', $conversation->id)
                ->where('id', $replyToId)
                ->first();

            if ($replyTo) {
                $meta['reply_to'] = $this->buildReplyToMeta($replyTo);
            }
        }

        // --------- สร้าง message outbound ฝั่งระบบเรา ---------
        /** @var LineMessage $message */
        $message = $this->chat->createOutboundMessageFromAgent(
            $conversation,
            $text,
            $employeeId,
            $meta
        );

        $conversation->loadMissing(['account', 'contact.member']);
        $account = $conversation->account;
        $contact = $conversation->contact;

        // -------------------------
        // เลือกข้อความที่จะใช้เป็น "เนื้อ" การตอบลูกค้า (หลังแปลแล้ว ถ้ามี)
        // -------------------------
        $lineText = $text;

        $msgMeta = $message->meta;
        if (is_array($msgMeta)) {
            $outboundTrans = $msgMeta['translation_outbound'] ?? null;

            // ถ้ามีข้อความแปล → ใช้ตัวแปลเป็นเนื้อหลักในการคุยกับลูกค้า
            if (is_array($outboundTrans) && ! empty($outboundTrans['translated_text'])) {
                $lineText = $outboundTrans['translated_text'];
            }
        }

        // -------------------------
        // Quick Reply (optional)
        // - รองรับกรณีเลือก template ประเภท "ข้อความ + quick reply" จาก UI
        // - ถ้า build แล้วไม่มี items จะได้ null และไม่กระทบของเดิม
        // -------------------------
        $quickReply = null;
        if (! empty($data['quick_reply_items']) && is_array($data['quick_reply_items'])) {
            $quickReply = $this->buildLineQuickReply($data['quick_reply_items'], []);
        }

        Log::channel('line_oa')->warning('[Reply] Quick Reply (optional)', [
            'data' => $quickReply
        ]);

        // -------------------------
        // สร้าง payload ที่จะส่งไป LINE
        // - ถ้ามี reply_to และเป็น text → ใช้ Flex message (แบบ B)
        // - ถ้าไม่มี reply_to → pushText() ปกติ
        // -------------------------
        if ($account && $contact && $contact->line_user_id) {
            $lineUserId = $contact->line_user_id;

            $replyMeta = is_array($msgMeta) ? ($msgMeta['reply_to'] ?? null) : null;
            $hasReply = is_array($replyMeta) && ! empty($replyMeta['text']);

            if ($hasReply && (($replyMeta['type'] ?? 'text') === 'text')) {

                $quoted = (string) $replyMeta['text'];
                $altText = "ตอบกลับ: {$quoted}\n".$lineText;
                $altText = mb_strimwidth($altText, 0, 390, '...', 'UTF-8');

                // -------------------------
                // เตรียมข้อมูล header: avatar + display name
                // -------------------------
                $contactRelation = $conversation->contact;
                $member = $contactRelation?->member;

                // ตรวจว่า message ต้นทางเป็นของลูกค้าจริงไหม
                $isCustomerMessage =
                    (($replyMeta['direction'] ?? null) === 'inbound') &&
                    (($replyMeta['source'] ?? null) === 'user');

                if ($isCustomerMessage) {
                    // เคสตอบกลับข้อความลูกค้า → ใช้ชื่อ + รูปลูกค้า
                    $headerName =
                        $contactRelation->display_name
                        ?? $member->name
                        ?? $contactRelation->name
                        ?? $contactRelation->line_name
                        ?? 'ลูกค้า';

                    $headerAvatarUrl = $contactRelation->picture_url ?? null;
                } else {
                    // เคสตอบกลับข้อความของพนักงานเอง หรือ outbound อื่น ๆ
                    $headerName = 'พนักงาน';
                    $headerAvatarUrl = null; // ไม่ต้องมีรูป
                }

                // -------------------------
                // สร้าง contents ของ header
                // -------------------------
                $headerContents = [];

                if (! empty($headerAvatarUrl)) {
                    $headerContents[] = [
                        'type' => 'image',
                        'url' => $headerAvatarUrl,
                        'size' => 'xs',
                        'aspectRatio' => '1:1',
                        'aspectMode' => 'cover',
                        'gravity' => 'center',
                        'flex' => 0,
                        'margin' => 'sm',
                        'align' => 'start',
                    ];
                }

                $headerContents[] = [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'xs',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => $headerName,
                            'weight' => 'bold',
                            'size' => 'sm',
                            'wrap' => true,
                        ],
                        [
                            'type' => 'text',
                            'text' => 'ตอบกลับข้อความก่อนหน้า',
                            'size' => 'xs',
                            'color' => '#888888',
                        ],
                    ],
                ];

                // -------------------------
                // ประกอบ Flex bubble
                // -------------------------
                $flex = [
                    'type' => 'flex',
                    'altText' => $altText,
                    'contents' => [
                        'type' => 'bubble',
                        'body' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'spacing' => 'sm',
                            'contents' => [
                                // แถวบน: avatar + ชื่อ + label
                                [
                                    'type' => 'box',
                                    'layout' => 'horizontal',
                                    'spacing' => 'md',
                                    'alignItems' => 'center',
                                    'contents' => $headerContents,
                                ],

                                // กล่องเทา: ข้อความเดิม (quoted)
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'backgroundColor' => '#F5F5F5',
                                    'cornerRadius' => 'md',
                                    'paddingAll' => '8px',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => $quoted,
                                            'size' => 'sm',
                                            'wrap' => true,
                                            'color' => '#555555',
                                        ],
                                    ],
                                ],

                                // ข้อความตอบกลับของเรา (หรือข้อความแปล)
                                [
                                    'type' => 'text',
                                    'text' => $lineText,
                                    'wrap' => true,
                                    'size' => 'md',
                                ],
                            ],
                        ],
                    ],
                ];

                Log::channel('line_oa')->warning('[Reply] pushMessages', [
                    'account' => $account,
                    'lineUserId' => $lineUserId,
                    'flex' => $flex,
                ]);

                $result = $this->lineMessaging->pushMessages(
                    $account,
                    $lineUserId,
                    [$flex]
                );

                if (($result['success'] ?? false)) {
                    // ===== ดึง sentMessages.id และ quoteToken มาเก็บลง message =====
                    $body = $result['body'] ?? null;
                    if (is_array($body)) {
                        $sent = $body['sentMessages'] ?? null;
                        if (is_array($sent) && ! empty($sent[0])) {
                            $first = $sent[0];
                            $lineMessageId = $first['id'] ?? null;
                            $quoteToken = $first['quoteToken'] ?? null;

                            $metaForMsg = $message->meta;
                            if (! is_array($metaForMsg)) {
                                $metaForMsg = $metaForMsg ? (array) $metaForMsg : [];
                            }

                            if ($quoteToken) {
                                $metaForMsg['quote_token'] = $quoteToken;
                            }

                            // จะเก็บ sentMessages ทั้งชุดไว้ใน meta เผื่อ debug ทีหลังด้วย
                            $metaForMsg['sent_messages'] = $sent;

                            if ($lineMessageId) {
                                $message->line_message_id = $lineMessageId;
                            }

                            $message->meta = $metaForMsg;
                            $message->save();
                        }
                    }
                } else {
                    Log::channel('line_oa')->warning('[LineChat] ส่ง Flex reply ไป LINE ไม่สำเร็จ', [
                        'conversation_id' => $conversation->id,
                        'contact_id' => $contact->id,
                        'status' => $result['status'] ?? null,
                        'error' => $result['error'] ?? null,
                    ]);
                }
            } else {
                // ไม่มี reply_to หรือไม่ใช่ text → ส่งเป็นข้อความปกติ
                if ($quickReply) {
                    Log::channel('line_oa')->warning('[Reply] ไม่มี reply_to หรือไม่ใช่ text → ส่งเป็นข้อความปกติ', [
                        'quickReply' => $quickReply,

                    ]);

                    $result = $this->lineMessaging->pushMessages(
                        $account,
                        $lineUserId,
                        [[
                            'type' => 'text',
                            'text' => $lineText,
                            'quickReply' => $quickReply,
                        ]]
                    );


                } else {
                    $result = $this->lineMessaging->pushText(
                        $account,
                        $lineUserId,
                        $lineText
                    );
                }

                if (($result['success'] ?? false)) {
                    // ===== ดึง sentMessages.id และ quoteToken มาเก็บลง message =====
                    $body = $result['body'] ?? null;
                    if (is_array($body)) {
                        $sent = $body['sentMessages'] ?? null;
                        if (is_array($sent) && ! empty($sent[0])) {
                            $first = $sent[0];
                            $lineMessageId = $first['id'] ?? null;
                            $quoteToken = $first['quoteToken'] ?? null;

                            $metaForMsg = $message->meta;
                            if (! is_array($metaForMsg)) {
                                $metaForMsg = $metaForMsg ? (array) $metaForMsg : [];
                            }

                            if ($quoteToken) {
                                $metaForMsg['quote_token'] = $quoteToken;
                            }
                            $metaForMsg['sent_messages'] = $sent;

                            if ($lineMessageId) {
                                $message->line_message_id = $lineMessageId;
                            }

                            $message->meta = $metaForMsg;
                            $message->save();
                        }
                    }
                } else {
                    Log::channel('line_oa')->warning('[LineChat] ส่งข้อความไป LINE ไม่สำเร็จ', [
                        'conversation_id' => $conversation->id,
                        'contact_id' => $contact->id,
                        'error' => $result['error'] ?? null,
                        'status' => $result['status'] ?? null,
                    ]);
                }
            }
        } else {
            Log::channel('line_oa')->warning('[LineChat] ไม่สามารถส่งข้อความไป LINE ได้ (ไม่พบ account/contact/line_user_id)', [
                'conversation_id' => $conversation->id,
            ]);
        }

        return response()->json([
            'message' => 'success',
            'data' => [
                'id' => $message->id,
                'direction' => $message->direction,
                'source' => $message->source,
                'type' => $message->type,
                'text' => $message->text, // ในระบบเก็บเฉพาะข้อความที่ agent พิมพ์
                'sent_at' => optional($message->sent_at)->toIso8601String(),
                'sender_employee_id' => $message->sender_employee_id,
                'sender_bot_key' => $message->sender_bot_key,
                'meta' => $message->meta,      // มี reply_to / quote_token / sent_messages ให้หลังบ้านใช้
                'payload' => $message->payload,
                'is_pinned' => (bool) $message->is_pinned,
            ],
        ]);
        // จบ reply() เดิม
        // -------------------------
    }

    /**
     * ส่ง IMAGE จาก admin
     */
    public function replyImage(Request $request, LineConversation $conversation): JsonResponse
    {
        // (คงเดิมตามที่คุณส่งมา)
        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB
            'reply_to_message_id' => ['nullable', 'integer'], // <<== เพิ่ม
        ]);

        $file = $request->file('image');

        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // ถ้าห้องเคยถูกปิด → เปิดใหม่
        if ($conversation->status === 'closed') {
            $conversation->status = 'open';
            $conversation->closed_by_employee_id = null;
            $conversation->closed_by_employee_name = null;
            $conversation->closed_at = null;
            $conversation->save();
        }

        // -------------------------------------------------------------
        // meta พื้นฐาน
        // -------------------------------------------------------------
        $meta = [
            'employee_name' => $employee->user_name ?? null,
        ];

        // -------------------------------------------------------------
        // รองรับ reply_to (เหมือน reply() และ replySticker)
        // -------------------------------------------------------------
        $replyToId = $request->input('reply_to_message_id');

        if ($replyToId) {
            $replyTo = LineMessage::query()
                ->where('line_conversation_id', $conversation->id)
                ->where('id', $replyToId)
                ->first();

            if ($replyTo) {
                $meta['reply_to'] = $this->buildReplyToMeta($replyTo);
            }
        }

        // -------------------------------------------------------------
        // สร้าง LineMessage outbound image (เก็บ meta reply_to ด้วย)
        // -------------------------------------------------------------
        $message = $this->chat->createOutboundImageFromAgent(
            $conversation,
            $file,
            $employeeId,
            $meta
        );

        // เตรียม URL ส่ง LINE
        $payloadMsg = $message->payload['message'] ?? [];
        $originalUrl = $payloadMsg['contentUrl'] ?? null;
        $previewUrl = $payloadMsg['previewUrl'] ?? $originalUrl;

        if ($originalUrl) {
            $originalUrl = url($originalUrl);
        }
        if ($previewUrl) {
            $previewUrl = url($previewUrl);
        }

        $conversation->loadMissing(['account', 'contact.member']);
        $account = $conversation->account;
        $contact = $conversation->contact;

        if ($account && $contact && $contact->line_user_id && $originalUrl) {

            $result = $this->lineMessaging->sendImageMessage(
                $account,
                $contact->line_user_id,
                $originalUrl,
                $previewUrl
            );

            if (! empty($result['success'])) {

                // เหมือน reply(): เก็บ line_message_id + quote_token
                $body = $result['body'] ?? null;

                if (is_array($body)) {
                    $sent = $body['sentMessages'] ?? null;

                    if (is_array($sent) && ! empty($sent[0])) {
                        $first = $sent[0];
                        $lineMessageId = $first['id'] ?? null;
                        $quoteToken = $first['quoteToken'] ?? null;

                        $metaForMsg = $message->meta ?? [];
                        if (! is_array($metaForMsg)) {
                            $metaForMsg = (array) $metaForMsg;
                        }

                        if ($quoteToken) {
                            $metaForMsg['quote_token'] = $quoteToken;
                        }

                        $metaForMsg['sent_messages'] = $sent;

                        if ($lineMessageId) {
                            $message->line_message_id = $lineMessageId;
                        }

                        $message->meta = $metaForMsg;
                        $message->save();
                    }
                }
            } else {
                Log::channel('line_oa')->warning('[LineChat] ส่งรูปไป LINE ไม่สำเร็จ', [
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'image_url' => $originalUrl,
                    'error' => $result['error'] ?? null,
                    'status' => $result['status'] ?? null,
                ]);
            }
        }

        // -------------------------------------------------------------
        // response กลับไปหลังบ้าน
        // -------------------------------------------------------------
        return response()->json([
            'message' => 'success',
            'data' => [
                'id' => $message->id,
                'direction' => $message->direction,
                'source' => $message->source,
                'type' => $message->type,
                'text' => $message->text,
                'sent_at' => optional($message->sent_at)->toIso8601String(),
                'sender_employee_id' => $message->sender_employee_id,
                'meta' => $message->meta,
                'payload' => $message->payload,
                'is_pinned' => (bool) $message->is_pinned,
            ],
        ]);
    }

    public function replySticker(Request $request, LineConversation $conversation): JsonResponse
    {
        // (คงเดิมตามที่คุณส่งมา)
        $data = $request->validate([
            'package_id' => ['required', 'string'],
            'sticker_id' => ['required', 'string'],
            'reply_to_message_id' => ['nullable', 'integer'], // รองรับ reply เหมือน reply()
        ]);

        $packageId = trim($data['package_id']);
        $stickerId = trim($data['sticker_id']);

        if ($packageId === '' || $stickerId === '') {
            return response()->json([
                'message' => 'ต้องระบุ package_id และ sticker_id',
            ], 422);
        }

        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // meta พื้นฐาน
        $meta = [
            'employee_code' => $employee->code ?? null,
            'employee_name' => $employee->user_name ?? null,
        ];

        // -------------------------
        // ใส่ข้อมูล reply_to ลงใน meta (ถ้ามี)
        // -------------------------
        $replyToId = $data['reply_to_message_id'] ?? null;

        if ($replyToId) {
            $replyTo = LineMessage::query()
                ->where('line_conversation_id', $conversation->id)
                ->where('id', $replyToId)
                ->first();

            if ($replyTo) {
                $meta['reply_to'] = $this->buildReplyToMeta($replyTo);
            }
        }

        // 1) บันทึกข้อความ outbound sticker ลง DB
        /** @var LineMessage $message */
        $message = $this->chat->createOutboundStickerFromAgent(
            $conversation,
            $packageId,
            $stickerId,
            $employeeId,
            $meta
        );

        // 2) ยิงสติกเกอร์ไปที่ LINE จริง ๆ
        $conversation->loadMissing(['account', 'contact']);
        $account = $conversation->account;
        $contact = $conversation->contact;

        if ($account && $contact && $contact->line_user_id) {
            $result = $this->lineMessaging->pushSticker(
                $account,
                $contact->line_user_id,
                $packageId,
                $stickerId
            );

            if (! empty($result['success'])) {
                // ===== ดึง sentMessages.id และ quoteToken มาเก็บลง message (เหมือน reply() ) =====
                $body = $result['body'] ?? null;
                if (is_array($body)) {
                    $sent = $body['sentMessages'] ?? null;
                    if (is_array($sent) && ! empty($sent[0])) {
                        $first = $sent[0];
                        $lineMessageId = $first['id'] ?? null;
                        $quoteToken = $first['quoteToken'] ?? null;

                        $metaForMsg = $message->meta;
                        if (! is_array($metaForMsg)) {
                            $metaForMsg = $metaForMsg ? (array) $metaForMsg : [];
                        }

                        if ($quoteToken) {
                            // เก็บแบบเดียวกับฝั่ง reply text
                            $metaForMsg['quote_token'] = $quoteToken;
                        }

                        // เก็บ sentMessages ทั้งชุดไว้ debug ทีหลัง
                        $metaForMsg['sent_messages'] = $sent;

                        if ($lineMessageId) {
                            $message->line_message_id = $lineMessageId;
                        }

                        $message->meta = $metaForMsg;
                        $message->save();
                    }
                }
            } else {
                Log::warning('[LineChat] ส่งสติกเกอร์ไป LINE ไม่สำเร็จ', [
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'error' => $result['error'] ?? null,
                    'status' => $result['status'] ?? null,
                ]);
            }
        } else {
            Log::warning('[LineChat] ไม่สามารถส่งสติกเกอร์ไป LINE ได้ (ไม่พบ account/contact/line_user_id)', [
                'conversation_id' => $conversation->id,
            ]);
        }

        return response()->json([
            'message' => 'success',
            'data' => [
                'id' => $message->id,
                'direction' => $message->direction,
                'source' => $message->source,
                'type' => $message->type, // 'sticker'
                'text' => $message->text,
                'sent_at' => optional($message->sent_at)->toDateTimeString(),
                'sender_employee_id' => $message->sender_employee_id,
                'meta' => $message->meta,   // ตอนนี้จะมี reply_to, quote_token, sent_messages
                'is_pined' => (bool) $message->is_pined, // คง field เดิมไม่ไปแตะ
            ],
        ]);
    }

    /**
     * ส่งข้อความจาก LINE template (รองรับ JSON หลายข้อความ เช่น text + image)
     *
     * POST /admin/line-oa/conversations/{conversation}/reply-template
     */
    public function replyTemplate(LineConversation $conversation, Request $request)
    {
        // wrapper → ส่งต่อไปตัวจริง
        return $this->replyTemplateText($conversation, $request);
    }

    /**
     * ตัวจริงของการส่ง “ข้อความจาก Template”
     * - รับ template_code หรือ template_id จาก request
     * - สร้าง LineMessage (outbound)
     * - ส่งออกไป LINE ผ่าน ChatService
     * - persist quoteToken/sentMessages
     */
    public function replyTemplateText($arg1, $arg2 = null): \Illuminate\Http\JsonResponse
    {
        /** @var \Illuminate\Http\Request $request */
        /** @var \Gametech\LineOA\Models\LineConversation $conversation */

        if ($arg1 instanceof \Illuminate\Http\Request) {
            $request = $arg1;
            $conversation = $arg2;
        } else {
            $conversation = $arg1;
            $request = ($arg2 instanceof \Illuminate\Http\Request) ? $arg2 : request();
        }

        if (!($conversation instanceof \Gametech\LineOA\Models\LineConversation)) {
            throw new \InvalidArgumentException('replyTemplateText(): invalid arguments. Expect (Request, LineConversation) or (LineConversation, Request|null).');
        }

        $data = $request->validate([
            'template_id' => ['required', 'integer'],
            'vars' => ['array'],
            'preview_only' => ['sometimes', 'boolean'],
            'reply_to_message_id' => ['nullable', 'integer'],

            // ✅ เพิ่ม: ข้อความที่ผู้ใช้แก้ใน textarea (optional)
            'override_text' => ['nullable', 'string'],
        ]);

        $overrideText = isset($data['override_text']) ? trim((string) $data['override_text']) : '';
        if ($overrideText === '') {
            $overrideText = null;
        }

        $employee = \Auth::guard('admin')->user();
        if (!$employee || !$employee->code) {
            return response()->json(['message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)'], 403);
        }

        $employeeId = (int) $employee->code;
        $employeeName = $employee->user_name ?? 'พนักงาน';

        /** @var \Gametech\LineOA\Models\LineTemplate|null $template */
        $template = \Gametech\LineOA\Models\LineTemplate::query()
            ->where('id', $data['template_id'])
            ->where(function ($q) {
                $q->where('enabled', 1)->orWhereNull('enabled');
            })
            ->first();

        if (!$template) {
            return response()->json(['message' => 'ไม่พบข้อความด่วนที่เลือก'], 404);
        }

        // ===== เตรียม vars =====
        $conversation->loadMissing(['contact.member', 'contact.member.bank', 'account']);
        $contact = $conversation->contact;
        $member = $contact?->member;
        $bank = $member?->bank;

        $baseVars = [
            'display_name' => $contact->display_name ?? $member?->name ?? 'ลูกค้า',
            'username' => $member?->user_name ?? '',
            'member_id' => $member?->code ?? '',
            'phone' => $member?->mobile ?? '',
            'bank_name' => $bank?->name_th ?? '',
            'game_user' => $member?->game_user ?? '',
            'bank_code' => $member?->bank_code ?? '',
            'account_no' => $member?->acc_no ?? '',
            'login_url' => UrlHelper::loginUrl(),
            'site_name' => config('app.name'),
            'support_name' => trim(($employee->name ?? '') . ' ' . ($employee->surname ?? '')),
        ];

        $vars = array_merge($baseVars, $data['vars'] ?? []);

        // ===== parse template =====
        $structured = $this->normalizeTemplateMessage($template->message);
        $items = $structured['messages'] ?? [];

        $quickReply = null;
        if (!empty($structured['quick_reply_items']) && is_array($structured['quick_reply_items'])) {
            $quickReply = $this->buildLineQuickReply($structured['quick_reply_items'], $vars);
        }

        if (!count($items)) {
            return response()->json(['message' => 'template ไม่มีข้อความ'], 422);
        }

        $lineMessages = [];
        $hasText = false;
        $quickReplyAttached = false;

        foreach ($items as $item) {
            $kind = $item['kind'] ?? 'text';

            if ($kind === 'text') {
                $text = $this->applyTemplatePlaceholders((string) ($item['text'] ?? ''), $vars);
                if ($text !== '') {
                    $hasText = true;

                    $msg = ['type' => 'text', 'text' => $text];

                    if (!$quickReplyAttached && $quickReply) {
                        $msg['quickReply'] = $quickReply;
                        $quickReplyAttached = true;
                    }

                    $lineMessages[] = $msg;
                }
            }

            if ($kind === 'image') {
                $url = $this->applyTemplatePlaceholders((string) ($item['original'] ?? ''), $vars);
                if ($url !== '') {
                    $msg = [
                        'type' => 'image',
                        'originalContentUrl' => $url,
                        'previewImageUrl' => $url,
                    ];

                    if (!$quickReplyAttached && $quickReply) {
                        $msg['quickReply'] = $quickReply;
                        $quickReplyAttached = true;
                    }

                    $lineMessages[] = $msg;
                }
            }
        }

        if (!count($lineMessages)) {
            return response()->json(['message' => 'template ไม่มีข้อความที่ส่งได้'], 422);
        }

        // ✅ APPLY override_text: ทับข้อความของ text message ตัวแรก (ถ้ามี)
        if ($overrideText !== null) {
            foreach ($lineMessages as $i => $m) {
                if (($m['type'] ?? null) === 'text') {
                    $lineMessages[$i]['text'] = $overrideText;
                    $hasText = true; // กันเคสเผื่อ ๆ
                    break;
                }
            }
        }

        // ===== PREVIEW ONLY =====
        if ($request->boolean('preview_only')) {
            // ✅ ถ้ามี override_text ให้ preview ตามนั้นด้วย
            $previewText = $overrideText;

            if ($previewText === null) {
                $previewText = $hasText ? ($lineMessages[0]['text'] ?? null) : null;
            }

            return response()->json([
                'data' => [
                    'text' => $previewText,
                    'line_messages' => $lineMessages,
                ],
            ]);
        }

        // ===== เปิดเคสถ้าปิดอยู่ =====
        if ($conversation->status === 'closed') {
            $conversation->update([
                'status' => 'open',
                'closed_by_employee_id' => null,
                'closed_by_employee_name' => null,
                'closed_at' => null,
            ]);
        }

        // ===== meta =====
        $meta = [
            'employee_code' => $employeeId,
            'employee_name' => $employeeName,
            'template_id' => $template->id,
            'template_key' => $template->key,
            'template_title' => $template->title,
        ];

        // ===== CASE A: image-only =====
        if (!$hasText && ($lineMessages[0]['type'] ?? null) === 'image') {

            $message = $this->chat->createOutboundImageFromAgentUrl(
                $conversation,
                (string) $lineMessages[0]['originalContentUrl'],
                $employeeId,
                $meta
            );

        } else {

            // ===== CASE B: มี text =====
            $previewText = $overrideText;

            if ($previewText === null) {
                $previewText = collect($lineMessages)->firstWhere('type', 'text')['text'] ?? '';
            }

            $message = $this->chat->createOutboundQuickReplyFromAgent(
                $conversation,
                $previewText,
                $employeeId,
                [
                    'template_id' => $template->id,
                    'line_messages' => $lineMessages,
                    'vars' => $vars,
                    'override_text' => $overrideText, // ✅ เก็บไว้เผื่อ audit/debug
                ],
                $meta
            );
        }

        // ===== ส่ง LINE + เก็บผลลัพธ์เหมือน reply() =====
        $account = $conversation->account;
        $contact = $conversation->contact;

        if ($account && $contact && $contact->line_user_id) {

            $result = $this->lineMessaging->pushMessages(
                $account,
                $contact->line_user_id,
                $lineMessages
            );

            if (! empty($result['success'])) {
                // เก็บ sentMessages.id / quoteToken ลง meta
                $body = $result['body'] ?? null;

                if (is_array($body)) {
                    $sent = $body['sentMessages'] ?? null;

                    if (is_array($sent) && ! empty($sent[0])) {
                        $first = $sent[0];
                        $lineMessageId = $first['id'] ?? null;
                        $quoteToken = $first['quoteToken'] ?? null;

                        $metaForMsg = $message->meta;
                        if (! is_array($metaForMsg)) {
                            $metaForMsg = $metaForMsg ? (array) $metaForMsg : [];
                        }

                        if ($quoteToken) {
                            $metaForMsg['quote_token'] = $quoteToken;
                        }

                        // เก็บทั้งชุด: ใช้ debug mapping ระหว่างหลาย message ได้
                        $metaForMsg['sent_messages'] = $sent;

                        if ($lineMessageId) {
                            $message->line_message_id = $lineMessageId;
                        }

                        $message->meta = $metaForMsg;
                        $message->save();
                    }
                }
            } else {
                Log::channel('line_oa')->warning('[LineChat] ส่ง template ไป LINE ไม่สำเร็จ', [
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'template_id' => $template->id,
                    'status' => $result['status'] ?? null,
                    'error' => $result['error'] ?? null,
                    'line_messages' => $lineMessages,
                ]);
            }

        } else {
            Log::channel('line_oa')->warning('[LineChat] ไม่สามารถส่ง template ไป LINE ได้ (ไม่พบ account/contact/line_user_id)', [
                'conversation_id' => $conversation->id,
                'template_id' => $template->id,
            ]);
        }

        return response()->json(['data' => $message]);
    }

    /**
     * Helper กลาง: persist quoteToken/sentMessages (รวมศูนย์)
     * - กันโค้ดซ้ำ
     * - วันไหน LINE เปลี่ยนฟิลด์ แก้ที่เดียวจบ
     */
    private function persistLineSendResult(LineMessage $message, array $result): void
    {
        // NOTE:
        // - ไม่ assume ว่ามี column quote_token/sent_message_json ใน DB
        // - เก็บลง meta แบบเดียวกับ reply()/replySticker()/replyImage()

        $body = $result['body'] ?? null;

        if (! is_array($body)) {
            return;
        }

        $sentMessages = $body['sentMessages'] ?? null;
        if (! is_array($sentMessages) || empty($sentMessages[0])) {
            return;
        }

        $first = $sentMessages[0];
        $quoteToken = $first['quoteToken'] ?? null;
        $lineMessageId = $first['id'] ?? null;

        $metaForMsg = $message->meta;
        if (! is_array($metaForMsg)) {
            $metaForMsg = $metaForMsg ? (array) $metaForMsg : [];
        }

        if ($quoteToken) {
            $metaForMsg['quote_token'] = $quoteToken;
        }

        $metaForMsg['sent_messages'] = $sentMessages;

        if ($lineMessageId) {
            $message->line_message_id = $lineMessageId;
        }

        $message->meta = $metaForMsg;
        $message->save();
    }

    private function buildLineQuickReply(array $quickReplyItems, array $vars): ?array
    {
        $items = [];

        foreach ($quickReplyItems as $it) {
            if (!is_array($it)) continue;

            $label = (string) ($it['label'] ?? '');
            $label = $this->applyTemplatePlaceholders($label, $vars);
            $label = trim($label);

            if ($label === '') continue;

            $actionType = (string) ($it['action_type'] ?? '');
            $value = (string) ($it['value'] ?? '');
            $value = $this->applyTemplatePlaceholders($value, $vars);
            $value = trim($value);

            if ($actionType === '') continue;

            $action = null;

            if ($actionType === 'uri') {
                if ($value === '') continue;
                $action = [
                    'type'  => 'uri',
                    'label' => mb_substr($label, 0, 20),
                    'uri'   => $value,
                ];
            } elseif ($actionType === 'message') {
                if ($value === '') continue;
                $action = [
                    'type'  => 'message',
                    'label' => mb_substr($label, 0, 20),
                    'text'  => $value,
                ];
            } elseif ($actionType === 'postback') {
                if ($value === '') continue;
                $action = [
                    'type'  => 'postback',
                    'label' => mb_substr($label, 0, 20),
                    'data'  => $value,
                    // 'displayText' => $label, // ถ้าต้องการให้แสดงในช่องแชต
                ];
            } else {
                // action_type อื่น ๆ ยังไม่รองรับ → ข้ามเงียบ ๆ
                continue;
            }

            $node = [
                'type'   => 'action',
                'action' => $action,
            ];

            $img = (string) ($it['image_url'] ?? '');
            $img = $this->applyTemplatePlaceholders($img, $vars);
            $img = trim($img);

            if ($img !== '') {
                $node['imageUrl'] = $img;
            }

            $items[] = $node;
        }

        if (!count($items)) {
            return null;
        }

        return ['items' => $items];
    }

    private function normalizeTemplateMessage($message): array
    {
        // ถ้าเป็น array อยู่แล้ว
        $decoded = null;

        if (is_array($message)) {
            $decoded = $message;
        } elseif (is_string($message) && $message !== '') {
            $tmp = json_decode($message, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
                $decoded = $tmp;
            }
        }

        // 1) โครงใหม่ของคุณ: { type, text, image_url, quick_reply_items }
        if (is_array($decoded) && isset($decoded['type'])) {
            $type = (string) ($decoded['type'] ?? 'text');

            $out = [
                'version' => 1,
                'messages' => [],
            ];

            // quick reply items
            if (isset($decoded['quick_reply_items']) && is_array($decoded['quick_reply_items'])) {
                $out['quick_reply_items'] = $decoded['quick_reply_items'];
            }

            if ($type === 'image' || $type === 'image_quick_reply') {
                $url = (string) ($decoded['image_url'] ?? '');
                if ($url !== '') {
                    $out['messages'][] = [
                        'kind' => 'image',
                        'original' => $url,
                        'preview' => $url,
                    ];
                }
            } else {
                // text / text_quick_reply (default)
                $text = (string) ($decoded['text'] ?? '');
                if ($text !== '') {
                    $out['messages'][] = [
                        'kind' => 'text',
                        'text' => $text,
                    ];
                }
            }

            return $out;
        }

        // 2) โครงเดิม: {version, messages:[...]} (ถ้ามี)
        if (is_array($decoded) && isset($decoded['messages']) && is_array($decoded['messages'])) {
            // ถ้ามี quick_reply_items ในโครงเดิมก็ส่งต่อได้
            $out = $decoded;
            if (! isset($out['version'])) {
                $out['version'] = 1;
            }

            return $out;
        }

        // 3) ถ้าไม่ใช่ JSON → ถือเป็นข้อความธรรมดา
        $text = is_string($message) ? $message : '';
        $text = trim($text);

        if ($text === '') {
            return ['version' => 1, 'messages' => []];
        }

        return [
            'version' => 1,
            'messages' => [
                ['kind' => 'text', 'text' => $text],
            ],
        ];
    }

    protected function normalizeTemplateMessage_($raw): array
    {
        // (คงเดิมตามที่คุณส่งมา)
        if (is_array($raw)) {
            if (isset($raw['messages']) && is_array($raw['messages'])) {
                return [
                    'version' => $raw['version'] ?? 1,
                    'messages' => $raw['messages'],
                ];
            }

            if ($raw) {
                return [
                    'version' => $raw['version'] ?? 1,
                    'messages' => $raw,
                ];
            }

            return [
                'version' => 1,
                'messages' => [],
            ];
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if (isset($decoded['messages']) && is_array($decoded['messages'])) {
                    return [
                        'version' => $decoded['version'] ?? 1,
                        'messages' => $decoded['messages'],
                    ];
                }

                if ($decoded) {
                    return [
                        'version' => $decoded['version'] ?? 1,
                        'messages' => $decoded,
                    ];
                }
            }

            return [
                'version' => 1,
                'messages' => [
                    [
                        'kind' => 'text',
                        'text' => $raw,
                    ],
                ],
            ];
        }

        return [
            'version' => 1,
            'messages' => [],
        ];
    }

    protected function applyTemplatePlaceholders(string $text, array $vars): string
    {
        if ($text === '') {
            return $text;
        }

        return preg_replace_callback('/\{(\w+)\}/u', function ($m) use ($vars) {
            $key = $m[1];

            if (array_key_exists($key, $vars)) {
                return (string) $vars[$key];
            }

            return $m[0];
        }, $text);
    }

    /**
     * ดึงรายการ Quick Reply สำหรับห้องแชตนี้
     *
     * Route:
     *   GET /admin/line-oa/conversations/{conversation}/quick-replies
     *   (ฝั่ง JS เรียกผ่าน this.apiUrl('conversations/{id}/quick-replies'))
     */
    public function quickReplies(Request $request, LineConversation $conversation): JsonResponse
    {
        // ถ้าต้องการ filter ตาม OA สามารถใช้ $conversation->line_account_id ได้ในอนาคต
        // ตอนนี้เอาแบบ global quick_reply ทั้งระบบก่อน
        $query = LineTemplate::query()
            ->where('category', 'quick_reply')
            ->where('enabled', true)
            ->orderBy('id', 'asc');

        $templates = $query->get();

        $conversation->loadMissing([
            'contact.member',
            'contact.member.bank',
        ]);

        /** @var \Gametech\Admin\Models\Employee|null $employee */
        $employee = Auth::guard('admin')->user();

        // ====== เตรียม vars สำหรับแทนตัวแปรใน preview ======
        $contact = $conversation->contact;
        $member = $contact?->member;
        $bank = $member?->bank;

        $displayName =
            $contact->display_name
            ?? $member?->name
            ?? $contact->name
            ?? $contact->line_name
            ?? 'ลูกค้า';

        $username =
            $member?->user_name
            ?? $contact->member_username
            ?? '';

        $memberId =
            $member?->code
            ?? $contact->member_id
            ?? '';

        $phone =
            $member?->mobile
            ?? $member?->tel
            ?? $contact->member_mobile
            ?? '';

        $bankName =
            ($bank->name_th ?? null)
            ?? ($bank->name ?? null)
            ?? $member?->bank_name
            ?? $contact->member_bank_name
            ?? '';

        $bankCode =
            $member?->bank_code
            ?? $contact->member_bank_code
            ?? '';

        $accountNo =
            $member?->acc_no
            ?? $member?->account_no
            ?? $contact->member_acc_no
            ?? '';

        $supportName = $employee
            ? trim(($employee->name ?? '').' '.($employee->surname ?? ''))
            : '';

        $baseVars = [
            'display_name' => $displayName,
            'username' => $username,
            'member_id' => $memberId,
            'phone' => $phone,
            'bank_name' => $bankName,
            'game_user' => $member?->game_user ?? '',
            'bank_code' => $bankCode,
            'account_no' => $accountNo,
            'login_url' => UrlHelper::loginUrl(),
            'site_name' => config('app.name', config('app.domain_url')),
            'support_name' => $supportName,
        ];

        $items = $templates->map(function (LineTemplate $t) use ($baseVars) {
            $label = $t->title
                ?? $t->description
                ?? $t->key
                ?? ('Template #'.$t->id);

            $rawMessage = $t->message ?? null;

            $body = '';
            $meta = [
                'type' => null,
                'has_quick_reply' => false,
                'quick_reply_count' => 0,
                'has_image' => false,
                'image_url' => '', // ✅ เพิ่ม
            ];

            /**
             * 1) รองรับกรณี model cast message เป็น array อยู่แล้ว
             */
            if (is_array($rawMessage)) {
                // ----- โครงใหม่: { type, text, image_url, quick_reply_items } -----
                if (isset($rawMessage['type'])) {
                    $meta['type'] = (string) $rawMessage['type'];

                    $text = (string) ($rawMessage['text'] ?? '');
                    if ($text !== '') {
                        $body = $text;
                    }

                    $imageUrl = (string) ($rawMessage['image_url'] ?? '');
                    if ($imageUrl !== '') {
                        $meta['has_image'] = true;
                        $meta['image_url'] = $imageUrl; // ✅ เพิ่ม
                    }

                    $qrItems = $rawMessage['quick_reply_items'] ?? null;
                    if (is_array($qrItems) && count($qrItems)) {
                        $meta['has_quick_reply'] = true;
                        $meta['quick_reply_count'] = count($qrItems);
                    }

                    // ถ้าเป็น image แต่ไม่มี text → ทำ preview ให้ไม่ว่าง
                    if ($body === '' && $meta['has_image']) {
                        $body = '[รูปภาพ]';
                    }
                } // ----- โครงเดิม: { messages: [ {kind:'text', text:'...'} ] } -----
                elseif (isset($rawMessage['messages']) && is_array($rawMessage['messages'])) {
                    foreach ($rawMessage['messages'] as $m) {
                        if (($m['kind'] ?? null) === 'text' && ! empty($m['text'])) {
                            $body = (string) $m['text'];
                            break;
                        }
                    }

                    if ($body === '' && count($rawMessage['messages'])) {
                        $first = $rawMessage['messages'][0];
                        if (! empty($first['text'])) {
                            $body = (string) $first['text'];
                        }
                    }
                }
            }

            /**
             * 2) message เป็น string → decode JSON ได้ก็ parse ทั้งโครงใหม่/เก่า
             */
            if ($body === '' && is_string($rawMessage) && $rawMessage !== '') {
                $decoded = json_decode($rawMessage, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // ----- โครงใหม่ -----
                    if (isset($decoded['type'])) {
                        $meta['type'] = (string) $decoded['type'];

                        $text = (string) ($decoded['text'] ?? '');
                        if ($text !== '') {
                            $body = $text;
                        }

                        $imageUrl = (string) ($decoded['image_url'] ?? '');
                        if ($imageUrl !== '') {
                            $meta['has_image'] = true;
                            $meta['image_url'] = $imageUrl; // ✅ เพิ่ม
                        }

                        $qrItems = $decoded['quick_reply_items'] ?? null;
                        if (is_array($qrItems) && count($qrItems)) {
                            $meta['has_quick_reply'] = true;
                            $meta['quick_reply_count'] = count($qrItems);
                        }

                        if ($body === '' && $meta['has_image']) {
                            $body = '[รูปภาพ]';
                        }
                    } // ----- โครงเดิม -----
                    elseif (isset($decoded['messages']) && is_array($decoded['messages'])) {
                        foreach ($decoded['messages'] as $m) {
                            if (($m['kind'] ?? null) === 'text' && ! empty($m['text'])) {
                                $body = (string) $m['text'];
                                break;
                            }
                        }
                        if ($body === '' && count($decoded['messages'])) {
                            $first = $decoded['messages'][0];
                            if (! empty($first['text'])) {
                                $body = (string) $first['text'];
                            }
                        }
                    }
                } else {
                    // เป็น text ธรรมดา
                    $body = $rawMessage;
                }
            }

            $body = (string) $body;

            // แทน placeholder ด้วยข้อมูลลูกค้าจริง
            if ($body !== '') {
                $body = $this->applyTemplatePlaceholders($body, $baseVars);
            }

            // ตัดให้สั้นสำหรับ preview
            $preview = $body !== ''
                ? Str::limit(preg_replace('/\s+/u', ' ', $body), 80)
                : '';

            // ทำ suffix ให้ทีมรู้ว่าเป็น template แบบไหน (กันสับสน)
            if ($preview !== '' && $meta['has_quick_reply']) {
                $preview .= ' (+QR '.$meta['quick_reply_count'].')';
            }

            return [
                'id' => $t->id,
                'key' => $t->key ?? null,
                'label' => $label,
                'category' => $t->category,
                'preview' => $preview,
                'body_preview' => $body,

                // เพิ่ม meta เล็กน้อยให้ frontend ใช้แสดง badge ได้ (ไม่บังคับ)
                'meta' => $meta,
            ];
        });

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * ดึง content รูปของ message สำหรับ frontend (proxy / lazy download)
     *
     * Route (แนะนำ):
     *   GET /admin/line-oa/messages/{message}/content
     */
    public function content(LineMessage $message)
    {
        if ($message->type !== 'image') {
            abort(404);
        }

        try {
            $payloadMsg = data_get($message->payload, 'message', []);
            $path = $payloadMsg['path'] ?? null;
            $url = $payloadMsg['contentUrl'] ?? ($payloadMsg['previewUrl'] ?? null);

            // 1) ถ้ามี path และไฟล์อยู่ใน disk → stream
            if ($path && Storage::disk('public')->exists($path)) {
                $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
                $content = Storage::disk('public')->get($path);

                return response($content, 200)->header('Content-Type', $mime);
            }

            // 2) ถ้า payload มี URL แบบ https อยู่แล้ว → redirect ไปเลย
            if ($url && preg_match('#^https?://#i', $url)) {
                return redirect($url);
            }

            // 3) ถ้า contentProvider.type = line → ลองโหลดจาก LINE ตอนนี้
            $contentProviderType = data_get($message->payload, 'message.contentProvider.type');
            if ($contentProviderType === 'line' && $message->line_message_id) {
                $conversation = $message->conversation()->with('account')->first();
                $account = $conversation?->account;

                if ($account) {
                    $res = $this->lineMessaging->downloadMessageContent($account, $message->line_message_id, 'image');

                    if ($res && ! empty($res['path'])) {
                        // update payload
                        $payloadMsg['contentUrl'] = $res['url'];
                        $payloadMsg['previewUrl'] = $res['url'];
                        $payloadMsg['path'] = $res['path'];

                        $payload = $message->payload ?? [];
                        $payload['message'] = $payloadMsg;
                        $message->payload = $payload;
                        $message->save();

                        // stream ไฟล์ที่เพิ่งเซฟ
                        $path = $res['path'];
                        if (Storage::disk('public')->exists($path)) {
                            $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
                            $content = Storage::disk('public')->get($path);

                            return response($content, 200)->header('Content-Type', $mime);
                        }

                        return redirect($res['url']);
                    }

                    // ถ้าโหลดไม่ได้ (404 จาก LINE) → log แล้ว 404
                    Log::channel('line_oa')->warning('[LineChat] ดึง content รูปจาก LINE ไม่สำเร็จ', [
                        'message_id' => $message->id,
                        'line_message_id' => $message->line_message_id,
                    ]);
                }
            }

            // 4) สุดท้ายถ้าไม่มีอะไรเลย → 404
            Log::channel('line_oa')->warning('[LineChat] ไม่พบ content รูปสำหรับ message', [
                'message_id' => $message->id,
                'line_message_id' => $message->line_message_id,
            ]);

            abort(404);
        } catch (\Throwable $e) {
            Log::channel('line_oa')->error('[LineChat] exception ใน content()', [
                'message_id' => $message->id,
                'line_message_id' => $message->line_message_id,
                'error' => $e->getMessage(),
            ]);

            abort(500);
        }
    }

    public function findMember(Request $request): JsonResponse
    {
        $memberId = trim((string) $request->get('member_id', ''));

        if ($memberId === '') {
            return response()->json([
                'message' => 'member_id ห้ามว่าง',
            ], 422);
        }

        try {
            // หมายเหตุ:
            // - ตรงนี้ปรับให้ตรงระบบจริงของโบ๊ทได้เลย
            // - ตัวอย่าง: ใช้ repository กลางของ Member
            /** @var \Prettus\Repository\Contracts\RepositoryInterface $memberRepo */
            $memberRepo = app('Gametech\Member\Repositories\MemberRepository');

            $member = $memberRepo->findWhere([
                'user_name' => $memberId,
            ])->first();

            if (! $member) {
                // กันเคสอยากหาจาก id ด้วย
                $member = $memberRepo->findWhere([
                    'tel' => $memberId,
                ])->first();
            }

            if (! $member) {
                return response()->json([
                    'message' => 'ไม่พบสมาชิกตาม Member ID ที่ระบุ',
                ], 404);
            }

            // ตัดให้เหลือ field ที่ front ใช้จริง
            $data = [
                'id' => $member->id ?? $member->code ?? $memberId,
                'name' => $member->name ?? ($member->full_name ?? null),
                'username' => $member->username ?? ($member->user_name ?? null),
                'mobile' => $member->mobile ?? ($member->tel ?? null),
            ];

            return response()->json([
                'message' => 'success',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::channel('line_oa')->error('[LineOA] findMember error', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'ค้นหาสมาชิกไม่สำเร็จ กรุณาลองใหม่',
            ], 500);
        }
    }

    public function loadBank(Request $request): JsonResponse
    {

        try {
            // หมายเหตุ:
            // - ตรงนี้ปรับให้ตรงระบบจริงของโบ๊ทได้เลย
            // - ตัวอย่าง: ใช้ repository กลางของ Member
            /** @var \Prettus\Repository\Contracts\RepositoryInterface $bankRepo */
            $bankRepo = app('Gametech\Payment\Repositories\BankRepository');

            $default = [
                'value' => '',
                'text' => '== เลือกธนาคาร ==',
            ];

            $banks = $bankRepo->findWhere([
                'enable' => 'Y',
                'show_regis' => 'Y',
            ])->sortBy('name_th')
                ->map(fn ($item) => [
                    'value' => $item->code,
                    'text' => $item->name_th,
                ])->values()->prepend($default);

            return response()->json([
                'message' => 'success',
                'bank' => $banks,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'กรุณาลองใหม่',
            ], 500);
        }
    }

    public function checkBank(Request $request): JsonResponse
    {
        $result = [
            'success' => false,
            'firstname' => null,
            'lastname' => null,
        ];

        $bankCode = $request->input('bank_code');
        $account_no = $request->input('account_no');

        try {
            /** @var \Gametech\LineOA\Services\RegisterFlowService $flow */
            $flow = app(RegisterFlowService::class);

            // normalize ให้เป็นมาตรฐานเดียวกับ flow สมัครหลัก
            $normalizedAccount = $flow->normalizeAccountNo($account_no);

            if (! $normalizedAccount) {
                return response()->json([
                    'message' => 'เลขบัญชีไม่ถูกต้อง',
                    'success' => false,
                ], 200);
            }

            // ใช้ logic เดียวกับระบบสมัครปกติ
            if ($flow->isBankAccountAlreadyUsed($bankCode, $normalizedAccount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'เลขบัญชี มีในระบบแล้ว ไม่มาสารถใช้ได้',
                ]);
            }

            $apiBankCode = $this->mapBankCodeForExternalApi($bankCode);
            if (! $apiBankCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'ระบบไม่รองรับ ธนาคารดังกล่าว',
                ]);
            }

            try {
                $postData = [
                    'toBankAccNumber' => $normalizedAccount,
                    'toBankAccNameCode' => $apiBankCode,
                ];

                $response = Http::withHeaders([
                    'x-api-key' => 'af96aa1c-e1f5-4c22-ab96-7f5453704aa9',
                ])->asJson()->post('https://me2me.biz/getname.php', $postData);
            } catch (\Throwable $e) {
                // connect error / timeout → ปล่อยให้ไปถามชื่อเอง
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ]);
            }

            if (! $response->successful()) {
                // status code != 200 → ปล่อยให้ไปถามชื่อเอง
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ]);
            }

            $json = $response->json();

            $status = (bool) data_get($json, 'status');
            $msg = (string) (data_get($json, 'msg', '') ?? '');

            if (! $status) {
                // เคส status=false แยกตามเงื่อนไขที่ต้องการ
                if (Str::contains($msg, 'ข้อมูลเลขบัญชีปลายทางไม่ถูกต้อง')) {
                    // ให้ถามเลขบัญชีใหม่
                    $result['message'] = $msg;
                } elseif (Str::contains($msg, 'ไม่รองรับ')) {
                    // เช่น "toBankAccNameCode : LHBT ไม่รองรับ" → ไป step ถัดไป
                    $result['message'] = $msg;
                }

                return response()->json($result);
            }

            // ดึงชื่อ-นามสกุลจาก API และ normalize
            $rawFullname = (string) data_get($json, 'data.accountName', '');
            $cleanFullname = $flow->cleanInvisibleAndSpaces($rawFullname);

            if ($cleanFullname === '') {
                return response()->json($result);
            }

            $fullname = $flow->splitNameUniversal($cleanFullname);

            $firstname = $fullname['firstname'] ?? '';
            $lastname = $fullname['lastname'] ?? '';

            if ($firstname === '' || $lastname === '') {
                return response()->json($result);
            }

            $result['success'] = true;
            $result['firstname'] = $firstname;
            $result['lastname'] = $lastname;

            return response()->json($result);

        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'กรุณาลองใหม่',
            ], 500);
        }
    }

    protected function mapBankCodeForExternalApi(string $bankcode): ?string
    {
        switch ((string) $bankcode) {
            case '1':
                return 'BBL';
            case '2':
                return 'KBANK';
            case '3':
                return 'KTB';
            case '4':
                return 'SCB';
            case '5':
                return 'GHB';
            case '6':
                return 'KKP';
            case '7':
                return 'CIMB';
            case '19':
            case '15':
            case '10':
                return 'TTB';
            case '11':
                return 'BAY';
            case '12':
                return 'UOB';
            case '13':
                return 'LHB';
            case '14':
                return 'GSB';
            case '17':
                return 'BAAC';
            default:
                return null;
        }
    }

    public function checkPhone(Request $request): JsonResponse
    {
        $phone = $request->input('phone');

        try {
            /** @var \Gametech\LineOA\Services\RegisterFlowService $flow */
            $flow = app(RegisterFlowService::class);

            // normalize ให้เป็นมาตรฐานเดียวกับ flow สมัครหลัก
            $normalizedPhone = $flow->normalizePhone($phone);

            if (! $normalizedPhone) {
                return response()->json([
                    'message' => 'เบอร์โทรไม่ถูกต้อง',
                    'bank' => false,
                ], 200);
            }

            // ใช้ logic เดียวกับระบบสมัครปกติ
            $exists = $flow->isPhoneAlreadyUsed($normalizedPhone);

            return response()->json([
                'message' => 'success',
                'bank' => $exists,    // เหมือนของเดิม: bank = true ถ้าซ้ำ
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'กรุณาลองใหม่',
            ], 500);
        }
    }

    public function checkUser(Request $request): JsonResponse
    {
        $username = $request->input('username');

        try {
            /** @var \Gametech\LineOA\Services\RegisterFlowService $flow */
            $flow = app(RegisterFlowService::class);

            // ใช้ logic เดียวกับระบบสมัครปกติ
            $exists = $flow->isUsernameAlreadyUsed($username);

            return response()->json([
                'message' => 'success',
                'duplicate' => $exists,    // true = ซ้ำ, false = ใช้ได้
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'กรุณาลองใหม่',
            ], 500);
        }
    }

    public function registerMember(Request $request): JsonResponse
    {
        try {
            /** @var \Gametech\LineOA\Services\RegisterFlowService $flow */
            $flow = app(RegisterFlowService::class);

            // อ่านโหมดจาก frontend ('phone' หรือ 'username')
            $mode = $request->input('register_mode', 'phone');

            // ค่าที่ใช้ร่วมทุกโหมด
            $bankCode = trim((string) $request->input('bank_code'));
            $accountNo = trim((string) $request->input('account_no'));
            $name = trim((string) $request->input('name'));
            $surname = trim((string) $request->input('surname'));

            // ตรวจความครบถ้วนของฟิลด์หลัก
            if (! $bankCode || ! $accountNo || ! $name || ! $surname) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน',
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | MODE 1: สมัครด้วยเบอร์โทร (โทร = login id แบบเดิม)
            |--------------------------------------------------------------------------
            */
            if ($mode === 'phone') {

                $phone = $request->input('phone');
                $normalizedPhone = $flow->normalizePhone($phone);

                if (! $normalizedPhone) {
                    return response()->json([
                        'success' => false,
                        'message' => 'เบอร์โทรไม่ถูกต้อง',
                    ], 200);
                }

                if ($flow->isPhoneAlreadyUsed($normalizedPhone)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'เบอร์นี้มีอยู่ในระบบแล้ว',
                    ], 200);
                }

                // normalize เลขบัญชี
                $normalizedAccount = $flow->normalizeAccountNo($accountNo);
                if (! $normalizedAccount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'เลขบัญชีไม่ถูกต้อง',
                    ], 200);
                }

                // กรณี TW
                $isTw = (strtoupper($bankCode) === 'TW' || (string) $bankCode === '18');
                if ($isTw && $normalizedAccount !== $normalizedPhone) {
                    return response()->json([
                        'success' => false,
                        'message' => 'สำหรับธนาคาร TW เลขบัญชีต้องเป็นเบอร์โทรเท่านั้น',
                    ], 200);
                }

                // เช็คบัญชีซ้ำ
                if ($flow->isBankAccountAlreadyUsed($bankCode, $normalizedAccount)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'เลขบัญชี มีในระบบแล้ว ไม่สามารถใช้ได้',
                    ], 200);
                }

                // payload สำหรับ phone-mode
                $payload = [
                    'phone' => $normalizedPhone,
                    'bank_code' => $bankCode,
                    'account_no' => $normalizedAccount,
                    'name' => $name,
                    'surname' => $surname,
                    'created_from' => 'line_staff',
                    'register_mode' => 'phone',
                ];
            } /*
            |--------------------------------------------------------------------------
            | MODE 2: สมัครด้วย Username
            |--------------------------------------------------------------------------
            */
            elseif ($mode === 'username') {

                $username = strtolower(trim((string) $request->input('username')));

                // ตรวจรูปแบบ username
                if (! preg_match('/^[a-z0-9]+$/', $username)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'รูปแบบยูสเซอร์เนมไม่ถูกต้อง (ใช้ a-z0-9 เท่านั้น)',
                    ], 200);
                }

                if ($flow->isUsernameAlreadyUsed($username)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ยูสเซอร์เนมนี้มีอยู่แล้ว',
                    ], 200);
                }

                // normalize เลขบัญชีให้เหมือนเดิม
                $normalizedAccount = $flow->normalizeAccountNo($accountNo);
                if (! $normalizedAccount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'เลขบัญชีไม่ถูกต้อง',
                    ], 200);
                }

                // กรณี TW: username-mode ไม่ได้ใช้เบอร์อยู่แล้ว จึงไม่จำเป็นต้องบังคับเลขบัญชี == phone
                // ถ้าต้องการ enforce เงื่อนไขใหม่ให้บอกได้

                // เช็คบัญชีซ้ำ
                if ($flow->isBankAccountAlreadyUsed($bankCode, $normalizedAccount)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'เลขบัญชี มีในระบบแล้ว ไม่สามารถใช้ได้',
                    ], 200);
                }

                // Payload สำหรับ username-mode
                $payload = [
                    'username' => $username,
                    'bank_code' => $bankCode,
                    'account_no' => $normalizedAccount,
                    'name' => $name,
                    'surname' => $surname,
                    'created_from' => 'line_staff',
                    'register_mode' => 'username',
                ];
            } /*
            |--------------------------------------------------------------------------
            | ถ้า mode ผิด → error
            |--------------------------------------------------------------------------
            */
            else {
                return response()->json([
                    'success' => false,
                    'message' => 'โหมดสมัครไม่ถูกต้อง',
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | เรียก RegisterFlowService → สมัครจริง
            |--------------------------------------------------------------------------
            */
            $result = $flow->registerFromStaff($payload);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'สมัครสมาชิกไม่สำเร็จ',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'สมัครสมาชิกสำเร็จ',
                'member' => $result['member'] ?? null,
            ], 200);

        } catch (\Throwable $e) {

            Log::channel('line_oa')->error('[LineOA] registerMember error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่',
            ], 500);
        }
    }

    public function attachMember(Request $request, LineContact $contact): JsonResponse
    {
        $memberId = trim((string) $request->input('member_id', ''));
        $display_name = trim((string) $request->input('display_name', ''));

        if ($memberId === '') {
            return response()->json([
                'message' => 'member_id ห้ามว่าง',
            ], 422);
        }
        if ($display_name === '') {
            return response()->json([
                'message' => 'Display Name ห้ามว่าง',
            ], 422);
        }

        // ดึงข้อมูล member มาใส่เพิ่ม (optional)
        $memberName = null;
        $memberUsername = null;
        $memberMobile = null;
        $memberBankName = null;
        $memberAccNo = null;
        $memberDisplay = $display_name;

        try {
            /** @var \Prettus\Repository\Contracts\RepositoryInterface $memberRepo */
            $memberRepo = app('Gametech\Member\Repositories\MemberRepository');

            $member = $memberRepo->findWhere([
                'code' => $memberId,
            ])->first();

            if (! $member) {
                $member = $memberRepo->find($memberId);
            }

            if ($member) {
                $memberName = $member->name ?? null;
                $memberUsername = $member->user_name ?? null;
                $memberMobile = $member->tel ?? null;
                $memberBankName = $member->bank?->name_th ?? null;
                $memberAccNo = $member->acc_no ?? null;
            }
        } catch (\Throwable $e) {
            // ถ้าดึง member พัง ไม่เป็นไร แค่ log ไว้ แล้วผูกเฉพาะ member_id
            Log::channel('line_oa')->warning('[LineOA] attachMember: cannot load member detail', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
        }

        // เตรียม payload สำหรับอัปเดตทุก LineContact ที่มี line_user_id เดียวกัน
        $update = [
            'member_id' => $memberId,
            'display_name' => $memberDisplay,
        ];

        if ($memberUsername !== null) {
            $update['member_username'] = $memberUsername;
        }

        if ($memberMobile !== null) {
            $update['member_mobile'] = $memberMobile;
        }

        // ถ้าอยากเก็บชื่อ/ธนาคาร/เลขบัญชีลง contact ด้วย เปิดส่วนนี้ได้
        // if ($memberName !== null) {
        //     $update['member_name'] = $memberName;
        // }
        // if ($memberBankName !== null) {
        //     $update['member_bank_name'] = $memberBankName;
        // }
        // if ($memberAccNo !== null) {
        //     $update['member_acc_no'] = $memberAccNo;
        // }

        // อัปเดตทุก contact ที่มี line_user_id เดียวกัน
        LineContact::where('line_user_id', $contact->line_user_id)->update($update);

        // reload contact ปัจจุบันให้ใช้ค่าล่าสุด
        $contact->refresh();

        return response()->json([
            'message' => 'success',
            'data' => [
                'id' => $contact->id,
                'display_name' => $contact->display_name,
                'member_id' => $contact->member_id,
                'member_username' => $contact->member_username,
                'member_mobile' => $contact->member_mobile,
                'member_name' => $memberName,
                'member_bank_name' => $memberBankName,
                'member_acc_no' => $memberAccNo,
                'picture_url' => $contact->picture_url,
            ],
        ]);
    }

    public function accept(Request $request, LineConversation $conversation): JsonResponse
    {
        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;
        $employeeName = $employee->user_name ?? ($employee->name ?? 'พนักงาน');

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // ห้ามรับเรื่องถ้าปิดเคสแล้ว
        if ($conversation->status === 'closed') {
            return response()->json([
                'message' => 'แชตที่เลือก เสร็จสิ้นไปแล้ว',
            ], 409);
        }

        // ถ้ามีคนรับเรื่องไว้แล้ว และไม่ใช่เราเอง
        //        if ($conversation->assigned_employee_id &&
        //            (int) $conversation->assigned_employee_id !== (int) $employeeId) {
        //
        //            return response()->json([
        //                'message' => 'ห้องนี้ถูกพนักงานคนอื่นรับผิดชอบแล้ว',
        //            ], 409);
        //        }

        // เซต owner (assigned)
        $conversation->assigned_employee_id = (int) $employeeId;
        $conversation->assigned_employee_name = $employeeName;
        $conversation->assigned_at = now();

        // สถานะห้อง
        if ($conversation->status !== 'closed') {
            $conversation->status = 'assigned';
        }

        // optional: lock ห้องให้ตัวเองด้วย (ใช้ locked_by_employee_id)
        $conversation->locked_by_employee_id = (int) $employeeId;
        $conversation->locked_by_employee_name = $employeeName;
        $conversation->locked_at = now();

        $conversation->save();

        $conversationFresh = $conversation->fresh([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]) ?? $conversation;

        DB::afterCommit(function () use ($conversationFresh) {
            event(new LineOAChatConversationUpdated($conversationFresh));
            event(new LineOAConversationAssigned($conversationFresh));
        });

        return response()->json([
            'message' => 'success',
            'data' => $conversationFresh,
        ]);
    }

    public function assignees(): JsonResponse
    {
        // ดึงจากตาราง Admin หรือ Employee ตามโครงของโบ๊ท
        $items = Admin::query()
            ->where('enable', 'Y')
            ->orderBy('user_name')
            ->get([
                'code',
                'user_name',
                'name',
            ]);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function assign(Request $request, LineConversation $conversation): JsonResponse
    {
        // อนุญาตให้เป็น null ได้ สำหรับเคส "ไม่มีผู้รับผิดชอบ"
        $data = $request->validate([
            'employee_id' => ['nullable', 'integer'],
        ]);

        $employeeId = $data['employee_id'] ?? null;

        // เคส: ไม่มีผู้รับผิดชอบ (กดตัวเลือกแรก)
        if ($employeeId === null) {
            $conversation->assigned_employee_id = null;
            $conversation->assigned_employee_name = null;
            $conversation->assigned_at = null;
            $conversation->save();

            $conversationFresh = $conversation->fresh([
                'contact.member',
                'account',
                'registerSessions' => function ($q) {
                    $q->where('status', 'in_progress');
                },
            ]) ?? $conversation;

            DB::afterCommit(function () use ($conversationFresh) {
                event(new LineOAChatConversationUpdated($conversationFresh));
                // จะยิง LineOAConversationAssigned ด้วยหรือเปล่า แล้วแต่คุณ:
                event(new LineOAConversationAssigned($conversationFresh));
            });

            return response()->json([
                'message' => 'success',
                'data' => $conversationFresh,
            ]);
        }

        // เคส: มีผู้รับผิดชอบเป็นพนักงานคนหนึ่ง
        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Admin::find($employeeId);

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้งานที่เลือก',
            ], 404);
        }

        // ตรงนี้คุณเลือกได้ว่าจะเก็บเป็น id หรือ code
        $conversation->assigned_employee_id = $employee->code; // หรือ $employee->id
        $conversation->assigned_employee_name = $employee->user_name ?? $employee->name;
        $conversation->assigned_at = now();
        $conversation->save();

        $conversationFresh = $conversation->fresh([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]) ?? $conversation;

        DB::afterCommit(function () use ($conversationFresh) {
            event(new LineOAChatConversationUpdated($conversationFresh));
            event(new LineOAConversationAssigned($conversationFresh));
        });

        return response()->json([
            'message' => 'success',
            'data' => $conversationFresh,
        ]);
    }

    /**
     * ล็อกห้อง (บอกว่าตอนนี้ใครกำลังใช้งานห้องนี้)
     *
     * Route:
     *   POST /admin/line-oa/conversations/{conversation}/lock
     */
    public function lock(Request $request, LineConversation $conversation): JsonResponse
    {
        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;
        $employeeName = $employee->user_name ?? ($employee->name ?? 'พนักงาน');

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // ถ้ามีคนอื่นล็อกอยู่ และไม่ใช่เราเอง
        if ($conversation->locked_by_employee_id &&
            (int) $conversation->locked_by_employee_id !== (int) $employeeId) {

            return response()->json([
                'message' => 'ห้องนี้กำลังใช้งานโดย '.($conversation->locked_by_employee_name ?: 'พนักงานคนอื่น'),
            ], 409);
        }

        $conversation->locked_by_employee_id = (int) $employeeId;
        $conversation->locked_by_employee_name = $employeeName;
        $conversation->locked_at = now();
        $conversation->save();

        $conversationFresh = $conversation->fresh([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]) ?? $conversation;

        DB::afterCommit(function () use ($conversationFresh) {
            event(new LineOAChatConversationUpdated($conversationFresh));
            event(new LineOAConversationLocked($conversationFresh));
        });

        return response()->json([
            'message' => 'success',
            'data' => $conversationFresh,
        ]);
    }

    /**
     * ปลดล็อกห้อง
     *
     * Route:
     *   POST /admin/line-oa/conversations/{conversation}/unlock
     */
    public function unlock(Request $request, LineConversation $conversation): JsonResponse
    {
        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // ป้องกันไม่ให้คนอื่นมาปลดล็อกห้องที่เราใช้งานอยู่
        if ($conversation->locked_by_employee_id &&
            (int) $conversation->locked_by_employee_id !== (int) $employeeId) {

            return response()->json([
                'message' => 'ห้องนี้ถูกล็อกโดยพนักงานคนอื่น',
            ], 403);
        }

        $conversation->locked_by_employee_id = null;
        $conversation->locked_by_employee_name = null;
        $conversation->locked_at = null;
        $conversation->save();

        $conversationFresh = $conversation->fresh([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]) ?? $conversation;

        DB::afterCommit(function () use ($conversationFresh) {
            event(new LineOAChatConversationUpdated($conversationFresh));
            event(new LineOAConversationLocked($conversationFresh)); // ใช้ event เดิม แต่ payload lock เป็น null
        });

        return response()->json([
            'message' => 'success',
            'data' => $conversationFresh,
        ]);
    }

    public function close(Request $request, LineConversation $conversation): JsonResponse
    {
        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;
        $employeeName = $employee->user_name ?? ($employee->name ?? 'พนักงาน');

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // ถ้าปิดอยู่แล้ว ไม่ต้องทำอะไร
        if ($conversation->status === 'closed') {
            $conversationFresh = $conversation->fresh([
                'contact.member',
                'account',
                'registerSessions' => function ($q) {
                    $q->where('status', 'in_progress');
                },
            ]) ?? $conversation;

            DB::afterCommit(function () use ($conversationFresh) {
                event(new LineOAChatConversationUpdated($conversationFresh));
                event(new LineOAConversationClosed($conversationFresh));
            });

            return response()->json([
                'message' => 'success',
                'data' => $conversationFresh,
            ]);
        }

        // เซตสถานะเป็น closed
        $conversation->status = 'closed';
        $conversation->closed_by_employee_id = $employeeId;
        $conversation->closed_by_employee_name = $employeeName;
        $conversation->closed_at = now();

        // ปลดล็อกห้องด้วย (กันกรณีค้างล็อก)
        $conversation->locked_by_employee_id = null;
        $conversation->locked_by_employee_name = null;
        $conversation->locked_at = null;

        $conversation->save();

        $conversationFresh = $conversation->fresh([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]) ?? $conversation;

        DB::afterCommit(function () use ($conversationFresh) {
            event(new LineOAChatConversationUpdated($conversationFresh));
            event(new LineOAConversationClosed($conversationFresh));
        });

        return response()->json([
            'message' => 'success',
            'data' => $conversationFresh,
        ]);
    }

    public function open(Request $request, LineConversation $conversation): JsonResponse
    {
        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;
        $employeeName = $employee->user_name ?? ($employee->name ?? 'พนักงาน');

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // ===== ป้องกันลูกค้าเดียวกันมี open ซ้อนหลายห้อง =====
        $contactId = $conversation->line_contact_id;
        $accountId = $conversation->line_account_id;

        $existingOpen = LineConversation::query()
            ->where('line_contact_id', $contactId)
            ->where('line_account_id', $accountId)
            ->whereIn('status', ['open', 'assigned'])
            ->where('id', '!=', $conversation->id)
            ->first();

        if ($existingOpen) {
            $existingOpen->load([
                'contact.member',
                'account',
                'registerSessions' => function ($q) {
                    $q->where('status', 'in_progress');
                },
            ]);

            // ไม่ต้องถือว่า error ให้ frontend พาไปห้องนี้แทน
            return response()->json([
                'message' => 'มีห้องที่เปิดอยู่สำหรับลูกค้าคนนี้แล้ว ระบบจะพาไปยังห้องนั้น',
                'data' => $existingOpen,
            ]);
        }
        // ===============================================

        // ถ้าเปิดอยู่แล้ว ไม่ต้องทำอะไร
        if ($conversation->status !== 'closed') {
            $conversationFresh = $conversation->fresh([
                'contact.member',
                'account',
                'registerSessions' => function ($q) {
                    $q->where('status', 'in_progress');
                },
            ]) ?? $conversation;

            DB::afterCommit(function () use ($conversationFresh) {
                event(new LineOAChatConversationUpdated($conversationFresh));
                event(new LineOAConversationOpen($conversationFresh));
            });

            return response()->json([
                'message' => 'success',
                'data' => $conversationFresh,
            ]);
        }

        // เซตสถานะเป็น open (กลับไปสถานะเริ่มต้นหลังจากเสร็จสิ้น)
        $conversation->status = 'open';
        $conversation->closed_by_employee_id = null;
        $conversation->closed_by_employee_name = null;
        $conversation->closed_at = null;

        // ไม่บังคับล็อกห้องอัตโนมัติเมื่อกด Inbox
        $conversation->locked_by_employee_id = null;
        $conversation->locked_by_employee_name = null;
        $conversation->locked_at = null;

        $conversation->save();

        $conversationFresh = $conversation->fresh([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]) ?? $conversation;

        DB::afterCommit(function () use ($conversationFresh) {
            event(new LineOAChatConversationUpdated($conversationFresh));
            event(new LineOAConversationOpen($conversationFresh));
        });

        return response()->json([
            'message' => 'success',
            'data' => $conversationFresh,
        ]);
    }

    public function cancelRegister(LineConversation $conversation)
    {
        // หา session ค้าง
        $session = LineRegisterSession::where('line_conversation_id', $conversation->id)
            ->where('status', 'in_progress')
            ->orderByDesc('id')
            ->first();

        if (! $session) {
            return response()->json([
                'message' => 'ไม่มี flow สมัครที่กำลังทำงาน',
            ], 404);
        }

        // ยกเลิก session
        $session->status = 'cancelled';
        $session->current_step = RegisterFlowService::STEP_FINISHED;
        $session->save();

        // broadcast อัปเดตสถานะ
        DB::afterCommit(function () use ($conversation) {
            $conversation->load([
                'contact.member',
                'account',
                'registerSessions' => fn ($q) => $q->where('status', 'in_progress'),
            ]);

            event(new LineOAChatConversationUpdated($conversation));
        });

        return response()->json([
            'message' => 'success',
        ]);
    }

    public function getBalance(
        Request $request,
        MemberRepository $memberRepository,
        GameUserRepository $gameUserRepository
    ): JsonResponse {
        $conversationId = (int) $request->input('conversation_id');

        if (! $conversationId) {
            return response()->json([
                'ok' => false,
                'message' => 'ไม่พบค่า conversation_id',
            ], 422);
        }

        /** @var LineConversation|null $conversation */
        $conversation = LineConversation::query()
            ->with('contact')
            ->find($conversationId);

        if (! $conversation) {
            return response()->json([
                'ok' => false,
                'message' => 'ไม่พบห้องสนทนา',
            ], 404);
        }

        // ดึง member จาก contact
        $memberId = $conversation->contact?->member_id;
        $memberUsername = $conversation->contact?->member_username;

        if (! $memberId) {
            return response()->json([
                'ok' => false,
                'message' => 'ห้องนี้ยังไม่ได้ผูกกับสมาชิกในระบบ',
            ], 422);
        }

        $member = $memberRepository->find($memberId);

        if (! $member) {
            return response()->json([
                'ok' => false,
                'message' => 'ไม่พบข้อมูลสมาชิก (อาจถูกลบออกจากระบบแล้ว)',
            ], 404);
        }

        $gameUser = $member->gameUser;

        $balance = 0.0;
        $rawResponse = null;

        try {
            $game = core()->getGame();

            // NOTE: ปรับ parameter ให้ตรงกับ signature จริงของ checkBalance
            // บางระบบใช้ game_code + user_name, บางที่ใช้ game_id + game_user
            $rawResponse = $gameUserRepository->checkBalance(
                $game->id,
                $member->game_user // ถ้าจริง ๆ เป็น user_name ก็แก้เป็น $member->user_name
            );

            // กันเคส provider ตอบอะไรแปลก ๆ กลับมา
            $success = is_array($rawResponse) ? (bool) ($rawResponse['success'] ?? false) : false;

            if ($success) {
                $score = $rawResponse['score'] ?? 0;
                $balance = (float) $score;
            } else {
                // ดึง message จาก provider ถ้ามี
                $providerMessage = is_array($rawResponse)
                    ? ($rawResponse['message'] ?? 'ไม่สามารถดึงยอดเงินจากผู้ให้บริการได้')
                    : 'ไม่สามารถดึงยอดเงินจากผู้ให้บริการได้';

                return response()->json([
                    'ok' => false,
                    'message' => $providerMessage,
                ], 502);
            }
        } catch (Throwable $e) {
            // log ไว้เผื่อ debug
            Log::channel('line_oa')->warning('[LineOA] getBalance error', [
                'conversation_id' => $conversationId,
                'member_id' => $memberId,
                'response' => $rawResponse,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'เกิดข้อผิดพลาดระหว่างดึงยอดเงิน',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'success',
            'data' => [
                'member_id' => $memberId,
                'member_username' => $memberUsername,
                'member_gameuser' => $member->game_user,
                'member_turnover' => $gameUser->amount_balance,
                'member_limit' => $gameUser->withdraw_limit_amount,
                'member_pro' => ($gameUser->pro_code > 0 || $gameUser->amount_balance > 0) ? true : false,
                'member_pro_name' => $gameUser->promotion?->name_th ?? '',
                'balance' => $balance,
                'balance_text' => number_format($balance, 2),
                'currency' => 'THB',
            ],
        ]);
    }

    public function getBalanceMulti(
        Request $request,
        MemberRepository $memberRepository,
        GameUserRepository $gameUserRepository
    ): JsonResponse {
        $conversationId = (int) $request->input('conversation_id');

        if (! $conversationId) {
            return response()->json([
                'ok' => false,
                'message' => 'ไม่พบค่า conversation_id',
            ], 422);
        }

        /** @var LineConversation|null $conversation */
        $conversation = LineConversation::query()
            ->with('contact')
            ->find($conversationId);

        if (! $conversation) {
            return response()->json([
                'ok' => false,
                'message' => 'ไม่พบห้องสนทนา',
            ], 404);
        }

        // ดึง member จาก contact
        $memberId = $conversation->contact?->member_id;
        $memberUsername = $conversation->contact?->member_username;

        if (! $memberId) {
            return response()->json([
                'ok' => false,
                'message' => 'ห้องนี้ยังไม่ได้ผูกกับสมาชิกในระบบ',
            ], 422);
        }

        $member = $memberRepository->find($memberId);
        //        $gameUser = $member->gameUser;

        if (! $member) {
            return response()->json([
                'ok' => false,
                'message' => 'ไม่พบข้อมูลสมาชิก (อาจถูกลบออกจากระบบแล้ว)',
            ], 404);
        }

        $balance = 0.0;
        $rawResponse = null;

        try {
            //            $game = core()->getGame();

            // NOTE: ปรับ parameter ให้ตรงกับ signature จริงของ checkBalance
            // บางระบบใช้ game_code + user_name, บางที่ใช้ game_id + game_user
            //            $rawResponse = $gameUserRepository->checkBalance(
            //                $game->id,
            //                $member->game_user // ถ้าจริง ๆ เป็น user_name ก็แก้เป็น $member->user_name
            //            );

            // กันเคส provider ตอบอะไรแปลก ๆ กลับมา
            $score = $member->balance ?? 0;
            $balance = (float) $score;

        } catch (Throwable $e) {
            // log ไว้เผื่อ debug
            Log::channel('line_oa')->warning('[LineOA] getBalance error', [
                'conversation_id' => $conversationId,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'เกิดข้อผิดพลาดระหว่างดึงยอดเงิน',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'success',
            'data' => [
                'member_id' => $memberId,
                'member_username' => $memberUsername,
                'member_gameuser' => '',
                'member_turnover' => 0,
                'member_limit' => 0,
                'member_pro' => false,
                'member_pro_name' => '',
                'balance' => $balance,
                'balance_text' => number_format($balance, 2),
                'currency' => 'THB',
            ],
        ]);
    }

    /**
     * แสดงรายการโน้ตของห้องสนทนา
     *
     * GET /line-oa/conversations/{conversation}/notes
     */
    public function listNotes(LineConversation $conversation): JsonResponse
    {
        // ตามหลักควรจะ check สิทธิ์ด้วย (แล้วแต่โบ๊ทใช้ Gate/Policy ไหม)
        // if (Gate::denies('view', $conversation)) {
        //     abort(403);
        // }

        $notes = LineConversationNote::query()
            ->where('line_conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->get([
                'id',
                'body',
                'employee_id',
                'employee_name',
                'created_at',
            ]);

        $data = $notes->map(function (LineConversationNote $note) {
            return [
                'id' => $note->id,
                'body' => $note->body,
                'employee_id' => $note->employee_id,
                'employee_name' => $note->employee_name,
                'created_at' => optional($note->created_at)->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * เพิ่มโน้ตใหม่ให้ห้องสนทนา
     *
     * POST /line-oa/conversations/{conversation}/notes
     *
     * body: { body: "ข้อความโน้ต" }
     */
    public function storeNote(Request $request, LineConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Auth::guard('admin')->user();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        $employeeId = $employee->code ?? null;
        $employeeName = $employee->user_name ?? ($employee->name ?? 'พนักงาน');

        if (! $employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรหัสพนักงาน (code)',
            ], 403);
        }

        $body = trim($data['body']);

        if ($body === '') {
            return response()->json([
                'success' => false,
                'message' => 'ข้อความโน้ตห้ามเว้นว่าง',
            ], 422);
        }

        /** @var LineConversationNote $note */
        $note = LineConversationNote::create([
            'line_conversation_id' => $conversation->id,
            'line_account_id' => $conversation->line_account_id,
            'line_contact_id' => $conversation->line_contact_id,
            'employee_id' => $employeeId,
            'employee_name' => $employeeName,
            'body' => $body,
        ]);

        $data = [
            'id' => $note->id,
            'body' => $note->body,
            'employee_id' => $note->employee_id,
            'employee_name' => $note->employee_name,
            'created_at' => optional($note->created_at)->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 201);
    }

    public function updateNote(
        Request $request,
        LineConversation $conversation,
        LineConversationNote $note
    ): JsonResponse {
        // ตรวจว่า note นี้อยู่ในห้องเดียวกันจริงไหม กันยิง cross-conversation
        if ((int) $note->line_conversation_id !== (int) $conversation->id) {
            return response()->json([
                'success' => false,
                'message' => 'โน้ตนี้ไม่ได้อยู่ในห้องสนทนานี้',
            ], 403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Auth::guard('admin')->user();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        $employeeId = $employee->code ?? null;
        $employeeName = $employee->user_name ?? ($employee->name ?? 'พนักงาน');

        if (! $employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรหัสพนักงาน (code)',
            ], 403);
        }

        $body = trim($data['body']);

        if ($body === '') {
            return response()->json([
                'success' => false,
                'message' => 'ข้อความโน้ตห้ามเว้นว่าง',
            ], 422);
        }

        // อัปเดตโน้ต
        $note->body = $body;
        $note->employee_id = $employeeId;
        $note->employee_name = $employeeName;
        $note->save();

        $resp = [
            'id' => $note->id,
            'body' => $note->body,
            'employee_id' => $note->employee_id,
            'employee_name' => $note->employee_name,
            'created_at' => optional($note->created_at)->toIso8601String(),
            // เผื่ออนาคต frontend อยากแสดงว่าแก้ไขเมื่อไหร่
            'updated_at' => optional($note->updated_at)->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'data' => $resp,
        ]);
    }

    public function destroyNote(
        LineConversation $conversation,
        LineConversationNote $note
    ): JsonResponse {
        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Auth::guard('admin')->user();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // กันยิงลบโน้ตข้ามห้อง
        if ((int) $note->line_conversation_id !== (int) $conversation->id) {
            return response()->json([
                'success' => false,
                'message' => 'โน้ตนี้ไม่ได้อยู่ในห้องสนทนานี้',
            ], 403);
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบโน้ตสำเร็จ',
        ]);
    }

    /**
     * ปักหมุดห้องสนทนา (ฝั่งซ้าย)
     * POST /admin/line-oa/conversations/{conversation}/pin
     */
    public function pinConversation(LineConversation $conversation): JsonResponse
    {
        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Auth::guard('admin')->user();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        $conversation->is_pinned = true;
        $conversation->save();

        $conversationFresh = $conversation->fresh([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]) ?? $conversation;

        // broadcast ให้ list ซ้ายของทุกคนอัปเดต
        event(new LineOAChatConversationUpdated($conversationFresh));

        return response()->json([
            'success' => true,
            'data' => $conversationFresh,
        ]);
    }

    /**
     * เลิกปักหมุดห้องสนทนา
     * POST /admin/line-oa/conversations/{conversation}/unpin
     */
    public function unpinConversation(LineConversation $conversation): JsonResponse
    {
        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Auth::guard('admin')->user();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        $conversation->is_pinned = false;
        $conversation->save();

        $conversationFresh = $conversation->fresh([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]) ?? $conversation;

        event(new LineOAChatConversationUpdated($conversationFresh));

        return response()->json([
            'success' => true,
            'data' => $conversationFresh,
        ]);
    }

    /**
     * ปักหมุดข้อความในห้อง
     * POST /admin/line-oa/messages/{message}/pin
     */
    public function pinMessage(LineMessage $message): JsonResponse
    {
        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Auth::guard('admin')->user();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // กันไม่ให้ไปปักห้องคนอื่น OA / กรณีอยากเช็คเพิ่ม สามารถตรวจผ่าน relation conversation/account ได้
        $message->is_pinned = true;
        $message->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'line_conversation_id' => $message->line_conversation_id,
                'direction' => $message->direction,
                'source' => $message->source,
                'type' => $message->type,
                'text' => $message->text,
                'sent_at' => optional($message->sent_at)->toIso8601String(),
                'sender_employee_id' => $message->sender_employee_id,
                'sender_bot_key' => $message->sender_bot_key,
                'meta' => $message->meta,
                'payload' => $message->payload,
                'is_pinned' => (bool) $message->is_pinned,
            ],
        ]);
    }

    /**
     * เลิกปักหมุดข้อความในห้อง
     * POST /admin/line-oa/messages/{message}/unpin
     */
    public function unpinMessage(LineMessage $message): JsonResponse
    {
        /** @var \Gametech\Admin\Models\Admin|null $employee */
        $employee = Auth::guard('admin')->user();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        $message->is_pinned = false;
        $message->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'line_conversation_id' => $message->line_conversation_id,
                'direction' => $message->direction,
                'source' => $message->source,
                'type' => $message->type,
                'text' => $message->text,
                'sent_at' => optional($message->sent_at)->toIso8601String(),
                'sender_employee_id' => $message->sender_employee_id,
                'sender_bot_key' => $message->sender_bot_key,
                'meta' => $message->meta,
                'payload' => $message->payload,
                'is_pinned' => (bool) $message->is_pinned,
            ],
        ]);
    }
}
