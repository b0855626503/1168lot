<?php

namespace Gametech\LineOA\Http\Controllers\Admin;

use Gametech\LineOA\Models\LineConversation;
use Gametech\LineOA\Models\LineMessage;
use Gametech\LineOA\Services\ChatService;
use Gametech\LineOA\Services\LineMessagingClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatControllerBk extends Controller
{
    protected ChatService $chat;

    protected LineMessagingClient $lineMessaging;

    public function __construct(ChatService $chat, LineMessagingClient $lineMessaging)
    {
        $this->chat = $chat;
        $this->lineMessaging = $lineMessaging;

        // ปกติ route group ฝั่ง admin จะมี middleware('admin') อยู่แล้ว
        // ถ้าจะกันเพิ่มระดับ controller ก็ทำได้
        // $this->middleware('admin');
    }

    /**
     * แสดงหน้าแชต (Blade + Vue UI)
     *
     * Route:
     *   GET /admin/line-oa/chat  (name: admin.line-oa.chat)
     */
    public function page()
    {
        // Blade ต้องมีไฟล์: resources/views/admin/line_oa/chat.blade.php
        return view('admin::module.line-oa.index');
    }

    /**
     * ดึง list ห้องแชต (แสดงเป็น sidebar ซ้าย)
     *
     * Route:
     *   GET /admin/line-oa/conversations
     * Query:
     *   - status: open|closed (default: open)
     *   - q: keyword (display_name / member_username / member_mobile)
     *   - account_id: filter ตาม OA
     *   - per_page: default 20
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status', 'open');
        $accountId = $request->get('account_id');
        $q = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 20);

        $query = LineConversation::query()
            ->with(['contact', 'account'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($status) {
            $query->where('status', $status);
        }

        if ($accountId) {
            $query->where('line_account_id', $accountId);
        }

        if ($q !== '') {
            $query->whereHas('contact', function ($qQuery) use ($q) {
                $qQuery->where('display_name', 'like', '%'.$q.'%')
                    ->orWhere('member_username', 'like', '%'.$q.'%')
                    ->orWhere('member_mobile', 'like', '%'.$q.'%');
            });
        }

        $paginator = $query->paginate($perPage);

        $data = [
            'data' => $paginator->getCollection()->map(function (LineConversation $conv) {
                return [
                    'id' => $conv->id,
                    'status' => $conv->status,
                    'last_message' => $conv->last_message_preview,
                    'last_message_at' => optional($conv->last_message_at)->toIso8601String(),
                    'unread_count' => $conv->unread_count,
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
     *
     * Route:
     *   GET /admin/line-oa/conversations/{conversation}
     *
     * Query:
     *   - limit: จำนวนข้อความ (default: 50)
     *   - before_id: ถ้าอยากโหลดย้อน (pagination แบบ chat)
     */
    public function show(Request $request, LineConversation $conversation): JsonResponse
    {
        $limit = (int) $request->get('limit', 50);
        $beforeId = $request->get('before_id');

        $conversation->load(['contact', 'account']);

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

        // mark อ่านแล้ว: reset unread_count
        if ($conversation->unread_count > 0) {
            $conversation->unread_count = 0;
            $conversation->save();
        }

        $data = [
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'last_message_at' => optional($conversation->last_message_at)->toDateTimeString(),
                'unread_count' => $conversation->unread_count,
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
                ];
            }),
        ];

        return response()->json($data);
    }

    /**
     * ส่งข้อความตอบกลับจากฝั่ง admin ไปหาลูกค้า
     *
     * Route:
     *   POST /admin/line-oa/conversations/{conversation}/reply
     *
     * Body:
     *   - text: string (required)
     */
    public function reply(Request $request, LineConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string'],
        ]);

        $text = trim($data['text']);

        if ($text === '') {
            return response()->json([
                'message' => 'ข้อความห้ามว่าง',
            ], 422);
        }

        // หา employee จาก guard admin (ปรับให้ตรงระบบจริง ถ้า guard ไม่ชื่อ admin)
        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // 1) บันทึกข้อความ outbound ลง DB ผ่าน ChatService
        $message = $this->chat->createOutboundMessageFromAgent(
            $conversation,
            $text,
            $employeeId,
            [
                'employee_name' => $employee->user_name ?? null,
            ]
        );

        // 2) ยิงข้อความไปที่ LINE จริง ๆ
        $account = $conversation->account;
        $contact = $conversation->contact;

        if ($account && $contact && $contact->line_user_id) {
            $result = $this->lineMessaging->pushText(
                $account,
                $contact->line_user_id,
                $text
            );

            if (! $result['success']) {
                Log::channel('line_oa')->warning('[LineChat] ส่งข้อความไป LINE ไม่สำเร็จ', [
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'error' => $result['error'],
                    'status' => $result['status'],
                ]);
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
                'text' => $message->text,
                'sent_at' => optional($message->sent_at)->toDateTimeString(),
                'sender_employee_id' => $message->sender_employee_id,
                'meta' => $message->meta,
            ],
        ]);
    }

    public function replyImage(Request $request, LineConversation $conversation): JsonResponse
    {


        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $file = $request->file('image');

        // หา employee จาก guard admin (ปรับให้ตรงระบบจริง ถ้า guard ไม่ชื่อ admin)
        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        // 1) บันทึกข้อความ outbound ลง DB ผ่าน ChatService
        $message = $this->chat->createOutboundImageFromAgent(
            $conversation,
            $file,
            $employeeId,
            [
                'employee_name' => $employee->user_name ?? null,
            ]
        );

        // 2) ยิงข้อความไปที่ LINE จริง ๆ
        $account = $conversation->account;
        $contact = $conversation->contact;

        if ($account && $contact && $contact->line_user_id) {
            $result = $this->lineMessaging->sendImageMessage(
                $account,
                $contact->line_user_id,
                $url,
                $url
            );

            if (! $result['success']) {
                Log::channel('line_oa')->warning('[LineChat] ส่งข้อความไป LINE ไม่สำเร็จ', [
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'error' => $result['error'],
                    'status' => $result['status'],
                ]);
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
                'text' => $message->text,
                'sent_at' => optional($message->sent_at)->toDateTimeString(),
                'sender_employee_id' => $message->sender_employee_id,
                'meta' => $message->meta,
            ],
        ]);
    }

    public function replyImage_(Request $request, $conversationId)
    {
        /** @var LineConversation $conversation */
        $conversation = LineConversation::with(['account', 'contact'])
            ->findOrFail($conversationId);

        $employee = auth('admin')->user();
        $employeeId = $employee->code ?? $employee->id ?? null;

        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $file = $request->file('image');

        /** @var ChatService $chat */
        $chat = app(ChatService::class);

        $message = $chat->createOutboundImageFromAgent(
            $conversation,
            $file,
            $employeeId,
            [
                'employee_name' => $employee->user_name ?? null,
            ]
        );

        // ถ้าต้องการ broadcast realtime ตอนพนักงานส่งรูปด้วย
        // $chat->broadcastAgentMessage($conversation, $message);  // ถ้าทำ helper แยก

        return response()->json([
            'data' => $message->fresh(), // ให้ payload/สัมพันธ์กลับมาครบ
        ]);
    }

}
