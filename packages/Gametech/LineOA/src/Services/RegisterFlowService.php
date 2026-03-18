<?php

namespace Gametech\LineOA\Services;

use Gametech\LineOA\Contracts\LineMemberRegistrar;
use Gametech\LineOA\Events\LineOAChatConversationUpdated;
use Gametech\LineOA\Models\LineContact;
use Gametech\LineOA\Models\LineConversation;
use Gametech\LineOA\Models\LineRegisterSession;
use Gametech\Member\Models\Member;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * จัดการ flow การสมัครสมาชิกผ่าน LINE แบบถาม–ตอบทีละ step
 *
 * Flow:
 * 1) phone  -> เก็บเบอร์
 * 2) bank   -> เก็บ bank_code
 *    - ถ้า True Wallet (18) ใช้เบอร์โทรเป็นเลขบัญชี + เช็คซ้ำ แล้วไป STEP_NAME
 *    - ถ้าแบงค์อื่น -> ไป STEP_ACCOUNT
 * 3) account -> เก็บเลขบัญชี + เช็คซ้ำ + (ถ้า bank รองรับ) ยิง API เช็คชื่อ
 *    - ถ้า API: status=true → มีชื่อ → สมัครเลย
 *    - ถ้า API: status=false && msg="ข้อมูลเลขบัญชีปลายทางไม่ถูกต้อง" → ถามเลขบัญชีใหม่
 *    - ถ้า API: status=false && msg="...ไม่รองรับ" → ไป STEP_NAME
 *    - ถ้า API error/อื่น ๆ → ไป STEP_NAME
 * 4) name   -> พยายาม parse "ชื่อ นามสกุล" ถ้ามีครบ → สมัครเลย, ถ้าไม่ → ไป STEP_SURNAME
 * 5) surname-> เก็บนามสกุล → สมัครเลย
 */
class RegisterFlowService
{
    public const STEP_PHONE = 'phone';

    public const STEP_NAME = 'name';

    public const STEP_SURNAME = 'surname';

    public const STEP_BANK = 'bank';

    public const STEP_ACCOUNT = 'account';

    public const STEP_FINISHED = 'finished';

    /** True Wallet bank_code ตามตาราง banks */
    private const BANK_CODE_TRUE_WALLET = '18';

    protected LineTemplateService $templates;

    protected LineMemberRegistrar $memberRegistrar;

    public function __construct(
        LineTemplateService $templates,
        LineMemberRegistrar $memberRegistrar
    ) {
        $this->templates = $templates;
        $this->memberRegistrar = $memberRegistrar;
    }

    /**
     * ข้อความจากลูกค้าหนึ่งข้อความ ผ่านเข้ามาที่นี่
     */
    public function handleTextMessage(
        LineContact $contact,
        LineConversation $conversation,
        string $text
    ): ?RegisterFlowResult {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        // เริ่ม flow เมื่อพิมพ์ "สมัคร"
        if ($this->isStartKeyword($text)) {
            return $this->handleStart($contact, $conversation);
        }

        // หา session สมัครที่ in_progress อยู่
        $session = $this->getInProgressSession($contact);

        if (! $session) {
            return null;
        }

        // ยกเลิก
        if ($this->isCancelKeyword($text)) {
            return $this->handleCancel($session,$conversation);
        }

        // เลือก handler ตาม step ปัจจุบัน
        switch ($session->current_step) {
            case self::STEP_PHONE:
                return $this->handlePhoneStep($session, $text);

            case self::STEP_BANK:
                return $this->handleBankStep($session, $text);

            case self::STEP_ACCOUNT:
                return $this->handleAccountStep($session, $text);

            case self::STEP_NAME:
                return $this->handleNameStep($session, $text);

            case self::STEP_SURNAME:
                return $this->handleSurnameStep($session, $text);

            default:
                return RegisterFlowResult::make()
                    ->handled(true)
                    ->finished(true)
                    ->session($session)
                    ->replyText(
                        $this->templates->render('register.already_completed')
                    );
        }
    }

    /**
     * เริ่ม flow ใหม่เมื่อพิมพ์ "สมัคร"
     */
    protected function handleStart(
        LineContact $contact,
        LineConversation $conversation
    ): RegisterFlowResult {
        // เคยสมัครสำเร็จแล้ว
        //        $existingCompleted = LineRegisterSession::query()
        //            ->where('line_contact_id', $contact->id)
        //            ->where('status', 'completed')
        //            ->first();
        //
        //        if ($existingCompleted) {
        //            return RegisterFlowResult::make()
        //                ->handled(true)
        //                ->finished(true)
        //                ->replyText(
        //                    $this->templates->render('register.already_completed')
        //                );
        //        }

        // session ค้างอยู่
        $session = $this->getInProgressSession($contact);

        if (! $session) {
            $session = LineRegisterSession::create([
                'line_contact_id' => $contact->id,
                'line_conversation_id' => $conversation->id,
                'status' => 'in_progress',
                'current_step' => self::STEP_PHONE,
                'data' => [],
            ]);
        } else {
            $session->current_step = self::STEP_PHONE;
            $session->data = [];
            $session->save();
        }

        // 🔹 ตรงนี้คือส่วนที่เพิ่มเข้าไป
        if (! $conversation->is_registering) {

            $conversation->is_registering = true;
            $conversation->save();

            //            DB::afterCommit(function () use ($conversation) {

            //            });
        }

        $conv = $conversation->load([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]);

        // ถ้าโบ๊ทมี event broadcast อัปเดตห้อง อยู่แล้ว ก็ยิงตรงนี้ได้ (ถ้ามี)
        event(new LineOAChatConversationUpdated($conv));

        $reply = $this->templates->render('register.ask_phone', [
            'contact_name' => $contact->display_name ?? '',
        ]);

        return RegisterFlowResult::make()
            ->handled(true)
            ->session($session)
            ->replyText($reply);
    }

    /**
     * STEP 1: เบอร์โทร → ไปถามธนาคาร
     */
    protected function handlePhoneStep(LineRegisterSession $session, string $text): RegisterFlowResult
    {
        $plain = $this->normalizePhone($text);

        if (! $plain) {
            $reply = $this->templates->render('register.error_phone_invalid', [
                'input' => $text,
            ]);

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        // ใช้ rule จริงแบบเว็บ: ห้ามซ้ำใน members.tel และ banks_account.acc_no
        if ($this->isPhoneAlreadyUsed($plain)) {
            $reply = $this->templates->render('register.error_phone_used', [
                'phone' => $plain,
            ]);

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        $data = $session->data ?? [];
        $data['phone'] = $plain;

        $session->data = $data;
        $session->current_step = self::STEP_BANK;
        $session->save();

        $reply = $this->templates->render('register.ask_bank');

        return RegisterFlowResult::make()
            ->handled(true)
            ->session($session)
            ->replyText($reply)
            ->quickReply($this->getBankQuickReplyOptions());
    }

    /**
     * STEP 2: ธนาคาร
     *
     * - ถ้า True Wallet (18) → ใช้เบอร์โทรเป็นเลขบัญชี + เช็คซ้ำ + ไป STEP_NAME
     * - ถ้าแบงค์อื่น → ไป STEP_ACCOUNT
     */
    protected function handleBankStep(LineRegisterSession $session, string $text): RegisterFlowResult
    {
        $bankCode = $this->normalizeBankInput($text);

        if (! $bankCode) {
            $reply = $this->templates->render('register.error_bank_invalid', [
                'input' => $text,
            ]);

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply)
                ->quickReply($this->getBankQuickReplyOptions());
        }

        $data = $session->data ?? [];
        $data['bank_code'] = $bankCode;
        $session->data = $data;

        // กรณีพิเศษ True Wallet (18) → ใช้เบอร์โทรเป็นเลขบัญชี
        if ((string) $bankCode === self::BANK_CODE_TRUE_WALLET) {
            $phone = Arr::get($data, 'phone');

            if (! $phone) {
                // state แปลกมาก → ให้ไปกรอกเลขบัญชีเอง
                $session->current_step = self::STEP_ACCOUNT;
                $session->save();

                $reply = $this->templates->render('register.ask_account', [
                    'bank_code' => $bankCode,
                ]);

                return RegisterFlowResult::make()
                    ->handled(true)
                    ->session($session)
                    ->replyText($reply);
            }

            $accountNo = $this->normalizeAccountNo($phone);

            if (! $accountNo) {
                // ถ้าเบอร์เพี้ยนจนเอาไปใช้เป็นเลขบัญชีไม่ได้ → ให้กรอกเอง
                $session->current_step = self::STEP_ACCOUNT;
                $session->save();

                $reply = $this->templates->render('register.ask_account', [
                    'bank_code' => $bankCode,
                ]);

                return RegisterFlowResult::make()
                    ->handled(true)
                    ->session($session)
                    ->replyText($reply);
            }

            // เช็คซ้ำเลขบัญชีสำหรับ TW เหมือนบัญชีปกติ
            if ($this->isBankAccountAlreadyUsed($bankCode, $accountNo)) {
                $reply = $this->templates->render('register.error_account_used', [
                    'account_no' => $accountNo,
                ]);

                // กลับไปให้เลือกธนาคารใหม่ (หรือให้พิมพ์ TW ใหม่)
                $session->current_step = self::STEP_BANK;
                $session->save();

                return RegisterFlowResult::make()
                    ->handled(true)
                    ->session($session)
                    ->replyText($reply)
                    ->quickReply($this->getBankQuickReplyOptions());
            }

            // ผ่านทุกอย่าง → เก็บเลขบัญชีแล้วไปถามชื่อ
            $data['account_no'] = $accountNo;
            $session->data = $data;
            $session->current_step = self::STEP_NAME;
            $session->save();

            $reply = $this->templates->render('register.ask_name');

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        // แบงค์อื่น → ไป step ถามเลขบัญชี
        $session->current_step = self::STEP_ACCOUNT;
        $session->save();

        $reply = $this->templates->render('register.ask_account', [
            'bank_code' => $bankCode,
        ]);

        return RegisterFlowResult::make()
            ->handled(true)
            ->session($session)
            ->replyText($reply);
    }

    /**
     * STEP 3: เลขบัญชี
     *
     * - เช็ค format / เช็คซ้ำ
     * - ถ้า bank รองรับ API → ยิงตรวจชื่อ
     *   - ถ้า status=true → มีชื่อ → สมัครเลย
     *   - ถ้า status=false && msg="ข้อมูลเลขบัญชีปลายทางไม่ถูกต้อง" → ถามเลขบัญชีใหม่
     *   - ถ้า status=false && msg="...ไม่รองรับ" → ไป STEP_NAME
     * - ถ้า bank ไม่รองรับ API / HTTP error → ไป STEP_NAME
     */
    protected function handleAccountStep(LineRegisterSession $session, string $text): RegisterFlowResult
    {
        $plain = $this->normalizeAccountNo($text);

        if (! $plain) {
            $reply = $this->templates->render('register.error_account_invalid', [
                'input' => $text,
            ]);

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        $data = $session->data ?? [];
        $bankCode = Arr::get($data, 'bank_code');

        if (! $bankCode) {
            // state แปลก → ย้อนกลับไปถามธนาคารใหม่
            $session->current_step = self::STEP_BANK;
            $session->save();

            $reply = $this->templates->render('register.ask_bank');

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply)
                ->quickReply($this->getBankQuickReplyOptions());
        }

        // ห้ามซ้ำ:
        // - members.acc_no
        // - banks_account.acc_no
        if ($this->isBankAccountAlreadyUsed($bankCode, $plain)) {
            $reply = $this->templates->render('register.error_account_used', [
                'account_no' => $plain,
            ]);

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        $data['account_no'] = $plain;
        $session->data = $data;
        $session->save();

        // True Wallet ไม่ต้องเรียก API (ตาม Banks() เดิมก็ไม่มี map code 18)
        if ((string) $bankCode === self::BANK_CODE_TRUE_WALLET) {
            $session->current_step = self::STEP_NAME;
            $session->save();

            $reply = $this->templates->render('register.ask_name');

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        // แบงค์อื่น → ลองเรียก API ตรวจชื่อ
        $apiResult = $this->lookupAccountNameViaApi($bankCode, $plain);

        if ($apiResult['success'] && $apiResult['firstname'] && $apiResult['lastname']) {
            // ได้ชื่อ-นามสกุลจาก API → สมัครเลย
            $data['name'] = $apiResult['firstname'];
            $data['surname'] = $apiResult['lastname'];

            $session->data = $data;
            $session->save();

            return $this->completeRegistrationFromSession($session);
        }

        // ถ้า API แจ้งว่าเลขบัญชีปลายทางไม่ถูกต้อง → ให้ถามเลขบัญชีใหม่
        if (($apiResult['error_type'] ?? null) === 'invalid_account') {
            // อยู่ที่ STEP_ACCOUNT ต่อ
            $session->current_step = self::STEP_ACCOUNT;
            $session->save();

            // ใช้ template เดิมของ error เลขบัญชีไม่ถูกต้อง
            $reply = $this->templates->render('register.error_account_invalid', [
                'input' => $plain,
            ]);

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        // กรณีอื่น ๆ (ไม่รองรับ / error / ไม่มีชื่อ) → ไปถามชื่อเอง
        $session->current_step = self::STEP_NAME;
        $session->save();

        $reply = $this->templates->render('register.ask_name');

        return RegisterFlowResult::make()
            ->handled(true)
            ->session($session)
            ->replyText($reply);
    }

    /**
     * STEP 4: ชื่อ
     *
     * - เช็คคำนำหน้า + พยายามแยกชื่อ/นามสกุลด้วย splitNameUniversal
     * - ถ้าเจอทั้งชื่อและนามสกุล → สมัครเลย
     * - ถ้าเจอชื่ออย่างเดียว → ไป STEP_SURNAME
     */
    protected function handleNameStep(LineRegisterSession $session, string $text): RegisterFlowResult
    {
        $clean = $this->cleanInvisibleAndSpaces($text);

        if ($clean === '') {
            $reply = $this->templates->render('register.error_name_invalid');

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        $parts = $this->splitNameUniversal($clean);
        $firstname = $parts['firstname'] ?? '';
        $lastname = $parts['lastname'] ?? '';

        $data = $session->data ?? [];

        // ถ้า parse ชื่อไม่ได้เลย → fallback เป็น logic เดิม (ใช้ข้อความเต็มเป็นชื่อ)
        if ($firstname === '') {
            if (mb_strlen($clean) < 2) {
                $reply = $this->templates->render('register.error_name_invalid');

                return RegisterFlowResult::make()
                    ->handled(true)
                    ->session($session)
                    ->replyText($reply);
            }

            $data['name'] = $clean;
            $session->data = $data;
            $session->current_step = self::STEP_SURNAME;
            $session->save();

            $reply = $this->templates->render('register.ask_surname');

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        // ถ้ามีทั้งชื่อและนามสกุลในประโยคเดียว → สมัครเลย
        if ($lastname !== '') {
            $data['name'] = $firstname;
            $data['surname'] = $lastname;

            $session->data = $data;
            $session->save();

            return $this->completeRegistrationFromSession($session);
        }

        // มีแต่ชื่อ → ไปถามนามสกุล
        if (mb_strlen($firstname) < 2) {
            $reply = $this->templates->render('register.error_name_invalid');

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        $data['name'] = $firstname;
        $session->data = $data;
        $session->current_step = self::STEP_SURNAME;
        $session->save();

        $reply = $this->templates->render('register.ask_surname');

        return RegisterFlowResult::make()
            ->handled(true)
            ->session($session)
            ->replyText($reply);
    }

    /**
     * STEP 5: นามสกุล → สมัครเลย
     */
    protected function handleSurnameStep(LineRegisterSession $session, string $text): RegisterFlowResult
    {
        $clean = $this->cleanInvisibleAndSpaces($text);

        if ($clean === '' || mb_strlen($clean) < 2) {
            $reply = $this->templates->render('register.error_surname_invalid');

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->replyText($reply);
        }

        $data = $session->data ?? [];
        $data['surname'] = $clean;

        $session->data = $data;
        $session->save();

        return $this->completeRegistrationFromSession($session);
    }

    protected function handleCancel(LineRegisterSession $session,LineConversation $conversation): RegisterFlowResult
    {
        $session->status = 'cancelled';
        $session->current_step = self::STEP_FINISHED;
        $session->error_message = null;
        $session->save();

        // 🔹 ตรงนี้คือส่วนที่เพิ่มเข้าไป
        if ($conversation->is_registering) {

            $conversation->is_registering = false;
            $conversation->save();

            //            DB::afterCommit(function () use ($conversation) {

            //            });
        }

        $conv = $conversation->load([
            'contact.member',
            'account',
            'registerSessions' => function ($q) {
                $q->where('status', 'in_progress');
            },
        ]);

        // ถ้าโบ๊ทมี event broadcast อัปเดตห้อง อยู่แล้ว ก็ยิงตรงนี้ได้ (ถ้ามี)
        event(new LineOAChatConversationUpdated($conv));

        $reply = $this->templates->render('register.cancelled');

        return RegisterFlowResult::make()
            ->handled(true)
            ->session($session)
            ->finished(true)
            ->replyText($reply);
    }

    /**
     * รวม logic สมัครจริงจาก session->data
     */
    protected function completeRegistrationFromSession(LineRegisterSession $session): RegisterFlowResult
    {
        $data = $session->data ?? [];

        try {
            $result = $this->memberRegistrar->registerFromLineData($data);
        } catch (\Throwable $e) {
            report($e);

            $reply = $this->templates->render('register.error_system', [
                'reason' => $e->getMessage(),
            ]);

            $session->status = 'failed';
            $session->error_message = $e->getMessage();
            $session->current_step = self::STEP_FINISHED;
            $session->save();

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->finished(true)
                ->replyText($reply);
        }

        if (! $result->success) {
            $session->status = 'failed';
            $session->error_message = $result->message;
            $session->current_step = self::STEP_FINISHED;
            $session->save();

            $reply = $this->templates->render('register.error_system', [
                'reason' => $result->message,
            ]);

            return RegisterFlowResult::make()
                ->handled(true)
                ->session($session)
                ->finished(true)
                ->replyText($reply);
        }

        // สมัครสำเร็จ
        $session->status = 'completed';
        $session->current_step = self::STEP_FINISHED;
        $session->member_id = $result->memberId;
        $session->data = $data;
        $session->save();

        // --- ผูก LineContact / Conversation กับ member ที่สมัครใหม่ ถ้ายังไม่เคยผูก ---
        try {
            $contact = LineContact::find($session->line_contact_id);
            $phone = Arr::get($data, 'phone');
            if ($contact && empty($contact->member_id) && $phone) {
                LineContact::where('line_user_id', $contact->line_user_id)
//                    ->whereNull('member_id') // กันทับ record ที่ผูกไว้แล้ว
                    ->update([
                        'member_id' => $result->memberId,
                        'member_mobile' => $phone,
                        'member_username' => $phone,
                    ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $reply = $this->templates->render('register.complete_success', [
            'username' => $result->username,
            'password' => $result->password,
            'login_url' => $result->loginUrl,
        ]);

        return RegisterFlowResult::make()
            ->handled(true)
            ->session($session)
            ->finished(true)
            ->memberId($result->memberId)
            ->replyText($reply);
    }

    /**
     * เรียก API ภายนอกเพื่อดูชื่อบัญชี (ดัดแปลงจาก checkBank())
     *
     * คืนค่า:
     *  [
     *    'success'   => bool,      // true = มีชื่อ, false = ไม่มี/มีปัญหา
     *    'firstname' => string|null,
     *    'lastname'  => string|null,
     *    'error_type'=> null|'invalid_account'|'unsupported_bank'
     *  ]
     */
    protected function lookupAccountNameViaApi(string $bankCode, string $accountNo): array
    {
        $result = [
            'success' => false,
            'firstname' => null,
            'lastname' => null,
            'error_type' => null,
        ];

        $apiBankCode = $this->mapBankCodeForExternalApi($bankCode);

        if (! $apiBankCode) {
            // ไม่รองรับ bank นี้
            $result['error_type'] = 'unsupported_bank';

            return $result;
        }

        try {
            $postData = [
                'toBankAccNumber' => $accountNo,
                'toBankAccNameCode' => $apiBankCode,
            ];

            $response = Http::withHeaders([
                'x-api-key' => 'af96aa1c-e1f5-4c22-ab96-7f5453704aa9',
            ])->asJson()->post('https://me2me.biz/getname.php', $postData);
        } catch (\Throwable $e) {
            // connect error / timeout → ปล่อยให้ไปถามชื่อเอง
            return $result;
        }

        if (! $response->successful()) {
            // status code != 200 → ปล่อยให้ไปถามชื่อเอง
            return $result;
        }

        $json = $response->json();

        $status = (bool) data_get($json, 'status');
        $msg = (string) (data_get($json, 'msg', '') ?? '');

        if (! $status) {
            // เคส status=false แยกตามเงื่อนไขที่ต้องการ
            if (Str::contains($msg, 'ข้อมูลเลขบัญชีปลายทางไม่ถูกต้อง')) {
                // ให้ถามเลขบัญชีใหม่
                $result['error_type'] = 'invalid_account';
            } elseif (Str::contains($msg, 'ไม่รองรับ')) {
                // เช่น "toBankAccNameCode : LHBT ไม่รองรับ" → ไป step ถัดไป
                $result['error_type'] = 'unsupported_bank';
            }

            return $result;
        }

        // ดึงชื่อ-นามสกุลจาก API และ normalize
        $rawFullname = (string) data_get($json, 'data.accountName', '');
        $cleanFullname = $this->cleanInvisibleAndSpaces($rawFullname);

        if ($cleanFullname === '') {
            return $result;
        }

        $fullname = $this->splitNameUniversal($cleanFullname);

        $firstname = $fullname['firstname'] ?? '';
        $lastname = $fullname['lastname'] ?? '';

        if ($firstname === '' || $lastname === '') {
            return $result;
        }

        $result['success'] = true;
        $result['firstname'] = $firstname;
        $result['lastname'] = $lastname;

        return $result;
    }

    /**
     * map bank_code → code ที่ API ภายนอกต้องการ (จาก Banks())
     */
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

    /**
     * หา session ที่สถานะ in_progress ของ contact นี้
     */
    protected function getInProgressSession(LineContact $contact): ?LineRegisterSession
    {
        return LineRegisterSession::query()
            ->where('line_contact_id', $contact->id)
            ->where('status', 'in_progress')
            ->orderByDesc('id')
            ->first();
    }

    protected function isStartKeyword(string $text): bool
    {
        $text = trim(mb_strtolower($text));

        $keywords = [
            'สมัคร',
            'สมัครสมาชิก',
            'reg',
            'register',
        ];

        return in_array($text, $keywords, true);
    }

    protected function isCancelKeyword(string $text): bool
    {
        $text = trim(mb_strtolower($text));

        $keywords = [
            'ยกเลิก',
            'ยกเลิกสมัคร',
            'cancel',
            'stop',
        ];

        return in_array($text, $keywords, true);
    }

    public function normalizePhone(string $text): ?string
    {
        $digits = preg_replace('/\D+/', '', $text);

        if (mb_strlen($digits) !== 10) {
            return null;
        }

        if (! preg_match('/^0[0-9]{9}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * ตัวเลือกธนาคารที่จะแสดงเป็น Quick Reply ใน LINE
     */
    protected function getBankQuickReplyOptions(): array
    {
        return [
            // TOP banks + True Wallet + TTB
            [
                'label' => 'กสิกรไทย',
                'text' => 'กสิกรไทย',
            ],
            [
                'label' => 'ไทยพาณิชย์',
                'text' => 'ไทยพาณิชย์',
            ],
            [
                'label' => 'กรุงไทย',
                'text' => 'กรุงไทย',
            ],
            [
                'label' => 'กรุงเทพ',
                'text' => 'กรุงเทพ',
            ],
            [
                'label' => 'กรุงศรี',
                'text' => 'กรุงศรี',
            ],
            [
                'label' => 'ออมสิน',
                'text' => 'ออมสิน',
            ],
            [
                'label' => 'ทรูวอเลท',
                'text' => 'ทรูวอเลท',
            ],
            [
                'label' => 'TTB',
                'text' => 'TTB',
            ],
            [
                'label' => 'ธกส',
                'text' => 'ธกส',
            ],
            [
                'label' => 'UOB',
                'text' => 'UOB',
            ],
            [
                'label' => 'เกียรตินาคิน',
                'text' => 'เกียรตินาคิน',
            ],
            [
                'label' => 'ซีไอเอ็มบี',
                'text' => 'CIMB',
            ],
            // ถ้าอยากได้ครบ 13 ปุ่ม จะเติมอีกตัวเช่น ทิสโก้ ก็ได้
            // [
            //     'label' => 'ทิสโก้',
            //     'text'  => 'ทิสโก้',
            // ],
        ];
    }

    /**
     * แปลง input ธนาคารจากข้อความ → bank_code (ตัวเลขจากตาราง banks)
     *
     * ถ้าผู้ใช้ส่งเลขล้วน (เช่น "18") ให้ถือว่าเป็น bank_code โดยตรง
     */
    protected function normalizeBankInput(string $text): ?string
    {
        // ลบช่องว่างทั้งหมด + แปลงเป็นตัวพิมพ์เล็ก
        $t = Str::lower(preg_replace('/\s+/', '', $text));

        if ($t === '') {
            return null;
        }

        $map = [
            // กรุงเทพ (1)
            'กรุงเทพ' => '1',
            'bangkokbank' => '1',
            'bbl' => '1',

            // กสิกรไทย (2)
            'กสิกรไทย' => '2',
            'กสิกร' => '2',
            'kbank' => '2',
            'kasikorn' => '2',

            // กรุงไทย (3)
            'กรุงไทย' => '3',
            'ktb' => '3',

            // ไทยพาณิชย์ (4)
            'ไทยพาณิชย์' => '4',
            'scb' => '4',

            // อาคารสงเคราะห์ (5)
            'อาคารสงเคราะห์' => '5',
            'ghbank' => '5',

            // เกียรตินาคิน (6)
            'เกียรตินาคิน' => '6',
            'kkp' => '6',

            // ซีไอเอ็มบี (7)
            'ซีไอเอ็มบี' => '7',
            'cimb' => '7',

            // อิสลาม (8)
            'อิสลาม' => '8',
            'ibank' => '8',

            // ทิสโก้ (9)
            'ทิสโก้' => '9',
            'tisco' => '9',

            // กรุงศรีอยุธยา (11)
            'กรุงศรี' => '11',
            'กรุงศรีอยุธยา' => '11',
            'bay' => '11',

            // ยูโอบี (12)
            'ยูโอบี' => '12',
            'uob' => '12',

            // แลนด์ แอนด์ เฮ้าส์ (13)
            'แลนด์แอนด์เฮ้าส์' => '13',
            'lhbank' => '13',

            // ออมสิน (14)
            'ออมสิน' => '14',
            'gsb' => '14',

            // ธกส. (17)
            'ธกส' => '17',
            'baac' => '17',
            'การเกษตร' => '17',
            'เกษตร' => '17',

            // True Wallet (18)
            'ทรู' => '18',
            'ทรูวอเลท' => '18',
            'truewallet' => '18',
            'true' => '18',
            'tw' => '18',

            // ทหารไทยธนชาต / TTB (19)
            'ttb' => '19',
            'tmb' => '19',
            'ทหารไทย' => '19',
            'ทหารไทยธนชาต' => '19',
        ];

        // ตรงเป๊ะก่อน
        if (isset($map[$t])) {
            return $map[$t];
        }

        // เผื่อพิมพ์คำอื่นยาว ๆ ที่มีคำเหล่านี้อยู่
        foreach ($map as $key => $code) {
            if ($key !== '' && Str::contains($t, $key)) {
                return $code;
            }
        }

        // ถ้าเป็นตัวเลขล้วน (เช่น 18, 19) ให้ถือว่าเป็น bank_code โดยตรง
        if (ctype_digit($t)) {
            return $t;
        }

        return null;
    }

    public function normalizeAccountNo(string $text): ?string
    {
        $digits = preg_replace('/\D+/', '', $text);

        if (mb_strlen($digits) < 6 || mb_strlen($digits) > 16) {
            return null;
        }

        return $digits;
    }

    public function isPhoneAlreadyUsed(string $phone): bool
    {
        // 1) members.tel หรือ members.user_name ใช้เบอร์นี้แล้ว
        $existsInMember = Member::where(function ($q) use ($phone) {
            $q->where('tel', $phone)
                ->orWhere('user_name', $phone);
        })->exists();

        if ($existsInMember) {
            return true;
        }

        // 2) banks_account.acc_no
        $existsInBankAccount = DB::table('banks_account')
            ->where('acc_no', $phone)
            ->exists();

        return $existsInBankAccount;
    }

    public function isUsernameAlreadyUsed(string $username): bool
    {
        // 1) members.tel หรือ members.user_name ใช้เบอร์นี้แล้ว
        // normalize ให้เป็นตัวเล็กทั้งหมด
        $username = strtolower(trim($username));

        // ป้องกัน input ผิด เช่นว่าง หรือมีอักขระแปลก
        if ($username === '' || !preg_match('/^[a-z0-9]+$/', $username)) {
            return true; // ถือว่าใช้ไม่ได้
        }

        // ชื่อคอลัมน์ในระบบโบ๊ทบางแบรนด์ใช้ user_name บางที่ใช้ username
        // และบาง flow เก็บ login_id เป็น username ด้วย
        return Member::where(function ($q) use ($username) {
            $q->where('user_name', $username);
        })->exists();
    }

    public function isBankAccountAlreadyUsed(?string $bankCode, string $accountNo): bool
    {
        // ตอนนี้ bankCode เป็นเลข bank_code ในระบบ
        // เช็คซ้ำจากเลขบัญชีเป็นหลัก
        $dupMember = Member::where('acc_no', $accountNo)
            ->where('bank_code', $bankCode)
            ->exists();

        if ($dupMember) {
            return true;
        }

        $existsInBankAccount = DB::table('banks_account')
            ->where('acc_no', $accountNo)
            ->exists();

        return $existsInBankAccount;
    }

    /**
     * สมัครสมาชิกแทนลูกค้า (ใช้จากฝั่งหลังบ้าน / ChatController)
     *
     * คาดหวัง payload อย่างน้อย:
     *  - phone
     *  - bank_code
     *  - account_no
     *  - name
     *  - surname
     *
     * ออปชัน:
     *  - line_contact_id
     *  - line_conversation_id
     *  - line_account_id
     *  - employee_id
     *  - created_from
     */
    public function registerFromStaff(array $payload): array
    {
        // โหมดสมัคร: phone หรือ username (default = phone เพื่อไม่พังของเดิม)
        $mode = $payload['register_mode'] ?? 'phone';

        $phone    = $payload['phone'] ?? null;
        $username = isset($payload['username'])
            ? strtolower(trim((string) $payload['username']))
            : null;

        $bankCode = $payload['bank_code'] ?? null;
        $accountNo = $payload['account_no'] ?? null;
        $name = $payload['name'] ?? null;
        $surname = $payload['surname'] ?? null;

        // ตรวจความครบถ้วนตามโหมด
        if ($mode === 'phone') {
            if (! $phone || ! $bankCode || ! $accountNo || ! $name || ! $surname) {
                return [
                    'success' => false,
                    'message' => 'ข้อมูลไม่ครบถ้วน',
                ];
            }
        } elseif ($mode === 'username') {
            if (! $username || ! $bankCode || ! $accountNo || ! $name || ! $surname) {
                return [
                    'success' => false,
                    'message' => 'ข้อมูลไม่ครบถ้วน',
                ];
            }
        } else {
            // กันเคสส่ง mode แปลกมา
            return [
                'success' => false,
                'message' => 'โหมดการสมัครไม่ถูกต้อง',
            ];
        }

        // เตรียม data ส่งเข้า registerFromLineData ให้สอดคล้องกับ flow เดิม
        $data = [
            'bank_code'    => $bankCode,
            'account_no'   => $accountNo,
            'name'         => $name,
            'surname'      => $surname,
            'created_from' => $payload['created_from'] ?? 'line_staff',
            'register_mode'=> $mode,
        ];

        // phone ใส่เมื่อมี (โหมด phone หรือ username+phone)
        if ($phone) {
            $data['phone'] = $phone;
        }

        // username ใส่เมื่อมี (ใช้กับโหมด username)
        if ($username) {
            $data['username'] = $username;
        }

        // แนบ context เพิ่มเติมถ้ามี
        foreach (['line_contact_id', 'line_conversation_id', 'line_account_id', 'employee_id'] as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null) {
                $data[$key] = $payload[$key];
            }
        }

        try {
            $result = $this->memberRegistrar->registerFromLineData($data);
        } catch (\Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่',
            ];
        }

        if (! $result->success) {
            return [
                'success' => false,
                'message' => $result->message ?? 'สมัครสมาชิกไม่สำเร็จ',
            ];
        }

        // ผูก LineContact กับ member ถ้า line_contact_id ส่งมา
        try {
            $contact = null;
            if (! empty($payload['line_contact_id'])) {
                $contact = LineContact::find($payload['line_contact_id']);
            }

            if ($contact && empty($contact->member_id)) {

                // ใช้ username จากผลลัพธ์ registrar เป็นหลัก (ถูกต้องที่สุด)
                $resolvedUsername = $result->username
                    ?? $username
                    ?? $phone;

                LineContact::where('line_user_id', $contact->line_user_id)
                    ->update([
                        'member_id'       => $result->memberId,
                        'member_mobile'   => $phone,             // อาจเป็น null ในโหมด username
                        'member_username' => $resolvedUsername,  // รองรับทั้ง phone / username
                    ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        // ดึงข้อมูล member กลับไปให้ฝั่งแชตใช้ (ถ้าต้องใช้แสดงผล)
        $member = null;
        try {
            $member = Member::find($result->memberId);
        } catch (\Throwable $e) {
            $member = null;
        }

        return [
            'success'    => true,
            'message'    => $result->message ?? 'สมัครสมาชิกสำเร็จ',
            'member'     => $member,
            'member_id'  => $result->memberId,
            'username'   => $result->username ?? null,
            'password'   => $result->password ?? null,
            'login_url'  => $result->loginUrl ?? null,
        ];
    }


    /**
     * ล้างอักขระมองไม่เห็น และ normalize ช่องว่าง
     */
    public function cleanInvisibleAndSpaces(string $s): string
    {
        // ลบอักขระรูปแบบ (General Category: Cf) ที่เจอบ่อยแบบเจาะจง
        $s = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{200E}\x{200F}\x{2060}\x{00A0}\x{202F}\x{FEFF}]/u', '', $s);

        // แปลง \r\n, \t ฯลฯ เป็นช่องว่าง แล้วบีบให้เหลือช่องว่างเดียว
        $s = preg_replace('/\s+/u', ' ', $s);

        // ตัดช่องว่างหัวท้าย
        return trim($s);
    }

    /**
     * แยก fullname → firstname/lastname และตัดคำนำหน้าออก
     */
    public function splitNameUniversal(string $fullName): array
    {
        // ล้าง ZWSP/NBSP/BOM ฯลฯ และ normalize ช่องว่าง
        $fullName = $this->cleanInvisibleAndSpaces($fullName);

        // คำนำหน้าที่พบบ่อย (เพิ่ม/แก้ได้ตามดาต้า)
        $prefixes = [
            // ไทย
            'นาย', 'นางสาว', 'นาง', 'น.ส.', 'น.', 'ดร.', 'ศ.', 'ผศ.', 'รศ.', 'ด.ญ.', 'ด.ช.', 'เด็กชาย.', 'เด็กหญิง.', 'เด็กชาย', 'เด็กหญิง', 'สาว', 'พระ',
            // อังกฤษ
            'Mr.', 'Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.', 'Sir', 'Madam', 'MISTER', 'MISS', 'MS', 'MR', 'MRS', 'KHUN',
        ];

        // ตัดคำนำหน้าออก (ไม่สนตัวพิมพ์ใหญ่เล็ก, รองรับ multibyte)
        foreach ($prefixes as $prefix) {
            if (mb_stripos($fullName, $prefix) === 0) {
                $fullName = trim(mb_substr($fullName, mb_strlen($prefix)));
                break;
            }
        }

        // กันกรณีคั่นด้วยหลายช่องว่าง/อักขระเว้นวรรคหลากชนิด
        $parts = preg_split('/\s+/u', $fullName);

        $firstname = $parts[0] ?? '';
        $lastname = count($parts) > 1 ? $parts[count($parts) - 1] : '';

        // ล้างซ้ำอีกรอบให้ชัวร์
        $firstname = $this->cleanInvisibleAndSpaces($firstname);
        $lastname = $this->cleanInvisibleAndSpaces($lastname);

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
        ];
    }
}

/**
 * DTO สำหรับผลลัพธ์ของ RegisterFlowService
 */
class RegisterFlowResult
{
    public bool $handled = false;

    public bool $finished = false;

    public ?int $memberId = null;

    public ?string $replyText = null;

    public ?LineRegisterSession $session = null;

    /** ตัวเลือก quick reply (เช่น เลือกธนาคาร) */
    public ?array $quickReply = null;

    public static function make(): self
    {
        return new self;
    }

    public function handled(bool $handled): self
    {
        $this->handled = $handled;

        return $this;
    }

    public function finished(bool $finished): self
    {
        $this->finished = $finished;

        return $this;
    }

    public function memberId(?int $memberId): self
    {
        $this->memberId = $memberId;

        return $this;
    }

    public function replyText(?string $replyText): self
    {
        $this->replyText = $replyText;

        return $this;
    }

    public function session(?LineRegisterSession $session): self
    {
        $this->session = $session;

        return $this;
    }

    /** เซ็ตตัวเลือก quick reply (เช่น ใช้ตอนถามธนาคาร) */
    public function quickReply(?array $options): self
    {
        $this->quickReply = $options;

        return $this;
    }
}
