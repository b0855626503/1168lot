<?php

namespace Gametech\LineOA\Http\Controllers\Admin;

use Gametech\LineOA\Models\LineContact;
use Gametech\LineOA\Models\LineConversation;
use Gametech\LineOA\Models\LineMessage;
use Gametech\LineOA\Services\ChatService;
use Gametech\LineOA\Services\LineMessagingClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatController_last extends Controller
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
        $perPage = (int) $request->get('per_page', 20);
        $scope = $request->get('scope', 'all'); // all | mine

        $query = LineConversation::query()
            ->with(['contact.member', 'account'])
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

        } else {
            // “ยังไม่ปิดเคส”
            $query->where(function ($qBuilder) {
                $qBuilder->whereNull('status')
                    ->orWhereIn('status', ['open', 'assigned']);
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

        $paginator = $query->paginate($perPage);

        $data = [
            'data' => $paginator->getCollection()->map(function (LineConversation $conv) {
                return [
                    'id' => $conv->id,
                    'status' => $conv->status,
                    'last_message' => $conv->last_message_preview,
                    'last_message_at' => optional($conv->last_message_at)->toIso8601String(),
                    'unread_count' => $conv->unread_count,

                    // *** ที่ต้องส่งเพิ่ม ***
                    'assigned_employee_id' => $conv->assigned_employee_id,
                    'assigned_employee_name' => $conv->assigned_employee_name,
                    'assigned_at' => optional($conv->assigned_at)->toIso8601String(),

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

        $conversation->load(['contact.member', 'account']);

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

        // clear unread
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

                // *** ส่งเพิ่ม ***
                'assigned_employee_id' => $conversation->assigned_employee_id,
                'assigned_employee_name' => $conversation->assigned_employee_name,
                'assigned_at' => optional($conversation->assigned_at)->toIso8601String(),

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
     * ส่ง TEXT จาก admin
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

        // 👇 เพิ่มตรงนี้: กันส่งข้อความในห้องที่ปิดแล้ว
        if ($conversation->status === 'closed') {
            return response()->json([
                'message' => 'เคสนี้ถูกปิดแล้ว ไม่สามารถส่งข้อความได้',
            ], 409);
        }

        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        $message = $this->chat->createOutboundMessageFromAgent(
            $conversation,
            $text,
            $employeeId,
            [
                'employee_name' => $employee->user_name ?? null,
            ]
        );

        $conversation->loadMissing(['account', 'contact']);
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
                    'error' => $result['error'] ?? null,
                    'status' => $result['status'] ?? null,
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
                'sent_at' => optional($message->sent_at)->toIso8601String(),
                'sender_employee_id' => $message->sender_employee_id,
                'sender_bot_key' => $message->sender_bot_key,
                'meta' => $message->meta,
                'payload' => $message->payload,
            ],
        ]);
    }

    /**
     * ส่ง IMAGE จาก admin
     */
    public function replyImage(Request $request, LineConversation $conversation): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        if ($conversation->status === 'closed') {
            return response()->json([
                'message' => 'เคสนี้ถูกปิดแล้ว ไม่สามารถส่งรูปภาพได้',
            ], 409);
        }

        $file = $request->file('image');

        $employee = Auth::guard('admin')->user();
        $employeeId = $employee?->code ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน (admin)',
            ], 403);
        }

        $message = $this->chat->createOutboundImageFromAgent(
            $conversation,
            $file,
            $employeeId,
            [
                'employee_name' => $employee->user_name ?? null,
            ]
        );

        $payloadMsg = $message->payload['message'] ?? [];
        $originalUrl = $payloadMsg['contentUrl'] ?? null;
        $previewUrl = $payloadMsg['previewUrl'] ?? $originalUrl;

        if ($originalUrl) {
            $originalUrl = url($originalUrl);
        }
        if ($previewUrl) {
            $previewUrl = url($previewUrl);
        }

        $conversation->loadMissing(['account', 'contact']);
        $account = $conversation->account;
        $contact = $conversation->contact;

        if ($account && $contact && $contact->line_user_id && $originalUrl) {
            $result = $this->lineMessaging->sendImageMessage(
                $account,
                $contact->line_user_id,
                $originalUrl,
                $previewUrl
            );

            if (! $result['success']) {
                Log::channel('line_oa')->warning('[LineChat] ส่งรูปไป LINE ไม่สำเร็จ', [
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'image_url' => $originalUrl,
                    'error' => $result['error'] ?? null,
                    'status' => $result['status'] ?? null,
                ]);
            }
        } else {
            Log::channel('line_oa')->warning('[LineChat] ไม่สามารถส่งรูปไป LINE ได้ (ไม่พบ account/contact/line_user_id หรือ url ว่าง)', [
                'conversation_id' => $conversation->id,
                'image_url' => $originalUrl,
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
                'sent_at' => optional($message->sent_at)->toIso8601String(),
                'sender_employee_id' => $message->sender_employee_id,
                'sender_bot_key' => $message->sender_bot_key,
                'meta' => $message->meta,
                'payload' => $message->payload,
            ],
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

    public function attachMember(Request $request, LineContact $contact): JsonResponse
    {
        $memberId = trim((string) $request->input('member_id', ''));

        if ($memberId === '') {
            return response()->json([
                'message' => 'member_id ห้ามว่าง',
            ], 422);
        }

        // ดึงข้อมูล member มาใส่เพิ่ม (optional)
        $memberName = null;
        $memberUsername = null;
        $memberMobile = null;
        $memberBankName = null;
        $memberAccNo = null;

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

        // อัปเดต contact
        $contact->member_id = $memberId;

        if ($memberUsername !== null) {
            $contact->member_username = $memberUsername;
        }
        if ($memberMobile !== null) {
            $contact->member_mobile = $memberMobile;
        }
//        if ($memberName !== null) {
//            $contact->member_name = $memberName;
//        }
//        if ($memberBankName !== null) {
//            $contact->member_bank_name = $memberBankName;
//        }
//        if ($memberAccNo !== null) {
//            $contact->member_acc_no = $memberAccNo;
//        }


        $contact->save();

        // โหลดความสัมพันธ์ที่ front ใช้
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

    /**
     * fallback เวอร์ชันเก่าที่โบ๊ทเคยใช้
     */
    public function replyImage_(Request $request, $conversationId)
    {
        /** @var LineConversation $conversation */
        $conversation = LineConversation::with(['account', 'contact'])
            ->findOrFail($conversationId);

        $employee = auth('admin')->user();
        $employeeId = $employee->code ?? $employee->id ?? null;

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
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

        return response()->json([
            'data' => $message->fresh(),
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
                'message' => 'ห้องนี้ถูกปิดเคสแล้ว',
            ], 409);
        }

        // ถ้ามีคนรับเรื่องไว้แล้ว และไม่ใช่เราเอง
        if ($conversation->assigned_employee_id &&
            (int) $conversation->assigned_employee_id !== (int) $employeeId) {

            return response()->json([
                'message' => 'ห้องนี้ถูกพนักงานคนอื่นรับเรื่องแล้ว',
            ], 409);
        }

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

        return response()->json([
            'message' => 'success',
            'data' => $conversation->fresh(),
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

            // ถ้าอยากทำ timeout (เช่นเกิน 10 นาทีแล้วถือว่าหมดอายุ) ค่อยมาเช็ค locked_at เพิ่มได้ที่นี่
            return response()->json([
                'message' => 'ห้องนี้กำลังใช้งานโดย '.($conversation->locked_by_employee_name ?: 'พนักงานคนอื่น'),
            ], 409);
        }

        $conversation->locked_by_employee_id = (int) $employeeId;
        $conversation->locked_by_employee_name = $employeeName;
        $conversation->locked_at = now();
        $conversation->save();

        return response()->json([
            'message' => 'success',
            'data' => $conversation->fresh(),
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

        return response()->json([
            'message' => 'success',
            'data' => $conversation->fresh(),
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
            return response()->json([
                'message' => 'success',
                'data' => $conversation->fresh(),
            ]);
        }

        // ถ้าห้องนี้มี assigned แล้ว แต่ไม่ใช่เรา → อนุญาตปิดได้ (ขึ้นกับ policy)
        // ถ้าไม่ต้องการให้ปิด ให้เพิ่ม guard ตรงนี้

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

        return response()->json([
            'message' => 'success',
            'data' => $conversation->fresh(),
        ]);
    }
}
