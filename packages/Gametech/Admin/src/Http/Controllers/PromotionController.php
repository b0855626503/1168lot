<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\PromotionDataTable;
use Gametech\Member\Models\MemberSelectPro;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BillRepository;
use Gametech\Promotion\Repositories\PromotionAmountRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Gametech\Promotion\Repositories\PromotionTimeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class PromotionController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $promotionTimeRepository;

    protected $promotionAmountRepository;

    protected $memberRepository;

    protected $bankPaymentRepository;

    protected $billRepository;

    public function __construct(
        PromotionRepository $repository,
        PromotionTimeRepository $promotionTimeRepo,
        PromotionAmountRepository $promotionAmountRepo,
        MemberRepository $memberRepository,
        BankPaymentRepository $bankPaymentRepo,
        BillRepository $billRepo,
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->promotionTimeRepository = $promotionTimeRepo;

        $this->promotionAmountRepository = $promotionAmountRepo;

        $this->memberRepository = $memberRepository;

        $this->bankPaymentRepository = $bankPaymentRepo;

        $this->billRepository = $billRepo;
    }

    public function index(PromotionDataTable $promotionDataTable)
    {
        return $promotionDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->repository->find($id);
        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $table = '';
        switch ($data->length_type) {
            case 'TIME':
            case 'TIMEPC':
                $table = 'promotions_time';
                break;

            case 'AMOUNT':
            case 'AMOUNTPC':
            case 'BETWEEN':
            case 'BETWEENPC':

                $table = 'promotions_amount';
                break;
            default:
                break;
        }

        $data->table = $table;

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

    }

    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'], true);

        $data['user_create'] = $user;
        $data['user_update'] = $user;

        $this->repository->createnew($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function createsub(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $data = $request->input('data');
        $table = $request->input('table');

        $data['pro_code'] = $id;
        $data['user_create'] = $user;
        $data['user_update'] = $user;

        if ($table == 'promotions_amount') {

            $this->promotionAmountRepository->create($data);

        } elseif ($table == 'promotions_time') {

            $this->promotionTimeRepository->create($data);

        }

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'], true);

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->updatenew($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function edit(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function destroysub(Request $request)
    {
        $id = $request->input('id');
        $table = $request->input('method');

        if ($table == 'promotions_amount') {

            $chk = $this->promotionAmountRepository->find($id);

            if (! $chk) {
                return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
            }

            $this->promotionAmountRepository->delete($id);

        } elseif ($table == 'promotions_time') {

            $chk = $this->promotionTimeRepository->find($id);

            if (! $chk) {
                return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
            }
            $this->promotionTimeRepository->delete($id);

        }

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function loadPro(Request $request)
    {
        $id = $request->input('id');
        $method = $request->input('method');
        $table = '';

        $responses = [];

        switch ($method) {
            case 'TIME':
            case 'TIMEPC':
                $responses = $this->promotionTimeRepository->findByField('pro_code', $id);
                $table = 'promotions_time';
                break;

            case 'AMOUNT':
            case 'AMOUNTPC':
            case 'BETWEEN':
            case 'BETWEENPC':
                $responses = $this->promotionAmountRepository->findByField('pro_code', $id);
                $table = 'promotions_amount';
                break;

        }
        if ($method === 'TIME' || $method === 'TIMEPC') {
            $no = 0;
            $responses = collect($responses)->map(function ($items) use ($no, $table) {
                $no++;
                $item = (object) $items;

                return [
                    'no' => $item->code,
                    'time_start' => $item->time_start,
                    'time_stop' => $item->time_stop,
                    'deposit_amount' => $item->deposit_amount,
                    'deposit_stop' => $item->deposit_stop,
                    'amount' => $item->amount,
                    'action' => '<button type="button" class="btn btn-warning btn-xs icon-only" onclick="delSub('.$item->code.','."'".$table."'".')"><i class="fa fa-times"></i></button>',

                ];

            });
        } else {
            $no = 0;
            $responses = collect($responses)->map(function ($items) use ($no, $table) {
                $no++;
                $item = (object) $items;

                return [
                    'no' => $item->code,
                    'deposit_amount' => $item->deposit_amount,
                    'deposit_stop' => $item->deposit_stop,
                    'amount' => $item->amount,
                    'action' => '<button type="button" class="btn btn-warning btn-xs icon-only" onclick="delSub('.$item->code.','."'".$table."'".')"><i class="fa fa-times"></i></button>',

                ];

            });
        }

        $result['list'] = $responses;

        return $this->sendResponseNew($result, 'complete');
    }

    public function loadPromotion(Request $request)
    {

        $data = $this->repository->where('active', 'Y')->where('enable', 'Y')->whereNotIn('id', ['pro_cashback', 'pro_spin', 'pro_faststart', 'pro_ic', 'pro_coupon'])->get();
        //        dd($data);

        //        dd($cacheP, $cached_provider);

        //        $data = collect($provider)->pluck('lobbyName', 'lobbyId');
        $dropdown = collect($data)
            ->pluck('name_th', 'code')
            ->map(fn ($name, $id) => ['value' => $id, 'text' => $name])
            ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->toArray();
        //        if (!$data) {
        //            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        //        }

        return $this->sendResponse($dropdown, 'ดำเนินการเสร็จสิ้น');

    }

    public function selectPromotion(Request $request)
    {
        $config = $this->getCoreConfig();

        $id = $request->input('id');
        $promotion_id = $request->input('promotion');

        $user = $this->memberRepository->find($id);
        if (! $user) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 404);
        }

        if (! $promotion_id) {
            return $this->sendError('ไม่พบรหัสโปรโมชัน', 422);
        }

        // คุณใช้ code เป็นตัวเลือก (ถูกต้องตามระบบเดิม)
        $promotion = $this->repository->findOneWhere(['code' => $promotion_id]);
        if (! $promotion) {
            return $this->sendError('ไม่พบโปรโมชันนี้', 404);
        }

        // ตรวจ balance เกิน pro_reset ไหม (เฉพาะ seamless)
        if (($config->seamless ?? 'N') === 'Y') {
            if (($user->balance ?? 0) >= ($config->pro_reset ?? 0)) {
                return $this->sendError(Lang::get('app.promotion.over_balance') . ($config->pro_reset ?? 0), 200);
            }
        }

        // เงื่อนไขเฉพาะโปร (ยึด logic เดิม)
        $pass = false;
        switch ($promotion->id) {
            case 'pro_newuser':
                if (($user->status_pro ?? 0) != 1) {
                    $pass = true;
                }
                break;

            case 'pro_firstday':
                $count = $this->repository->checkProFirstDay($user->code);
                if (! $count) {
                    $pass = true;
                }
                break;

            case 'pro_allbonus':
                $pass = true;
                break;

            case 'pro_oneonly_day':
                $count = $this->repository->checkProOneOnlyDay($user->code, $promotion->id);
                if (! $count) {
                    $pass = true;
                }
                break;

            case 'pro_oneonly_time':
                $count = $this->repository->checkProOneOnlyTime($user->code, $promotion->id);
                if (! $count) {
                    $pass = true;
                }
                break;

            default:
                // ถ้ามีโปรประเภทอื่นที่ไม่เข้ากรณีข้างบน ให้ถือว่า "ไม่ผ่าน" ตามเดิม
                $pass = false;
                break;
        }

        if (! $pass) {
            \Gametech\Member\Models\MemberSelectPro::where('member_code', $user->code)->delete();
            return $this->sendError(Lang::get('app.promotion.cannot'), 200);
        }

        // ผ่านเงื่อนไข: บันทึก select pro
        \Gametech\Member\Models\MemberSelectPro::updateOrCreate(
            ['member_code' => $user->code],
            [
                'pro_code' => $promotion->code,
                'pro_name' => $promotion->name_th,
                'pro_id'   => $promotion->id,
            ]
        );

        $mode = $request->input('mode');

        // ===== retro mode: ทำ preview จากยอดฝากของ bank_payment =====
        if ($mode === 'retro') {

            $bank_payment_code = $request->input('bank_payment_code');
            if (! $bank_payment_code) {
                return $this->sendError('ไม่พบ bank_payment_code', 422);
            }

            $bank_payment = $this->bankPaymentRepository->find($bank_payment_code);
            if (! $bank_payment) {
                return $this->sendError('ไม่พบรายการฝาก (bank_payment) ที่ระบุ', 404);
            }

            $amount = (float) ($bank_payment->value ?? $bank_payment->amount ?? 0);
            if ($amount <= 0) {
                return $this->sendError('ยอดฝากไม่ถูกต้อง', 422);
            }

            $promotion_check = $this->repository->checkSelectPro(
                $promotion->code,
                $user->code,
                $amount,
                now()->toDateTimeString()
            );

            // กัน array key หาย
            $bonus = (float) ($promotion_check['bonus'] ?? 0);
            $turnpro = (float) ($promotion_check['turnpro'] ?? 0);
            $total = (float) ($promotion_check['total'] ?? 0);
            $withdrawRate = (float) ($promotion_check['withdraw_limit_rate'] ?? 0);

            // ส่งให้ frontend อ่านได้แบบ “ชัดเจน”: มี preview เป็น object
            return $this->sendResponse([
                'promotion' => $promotion->code,
                'preview' => [
                    'bonus_amount' => $bonus,
                    'turnpro' => $turnpro,
                    'amount_balance' => $total * $turnpro,
                    'withdraw_limit_rate' => $withdrawRate,
                    'withdraw_limit_amount' => $total * $withdrawRate,
                ],
            ], Lang::get('app.promotion.pass'));
        }

        // ===== normal mode =====
        return $this->sendResponse([
            'promotion' => $promotion->code,
        ], Lang::get('app.promotion.pass'));
    }


    public function deselectPromotion(Request $request)
    {
        $pass = false;
        $id = $request->input('id');
        $user = $this->memberRepository->find($id);

        //        $this->logMemberEvent($user, 'กดยกเลิกโปรที่รับ แล้ว');
        MemberSelectPro::where('member_code', $user->code)->delete();

        return $this->sendSuccess(Lang::get('app.promotion.deselect'));

    }

    public function applyRetro(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_payment_code' => ['required', 'integer'],
            'member_id' => ['required', 'integer'],
            'promotion_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $datenow = now()->toDateTimeString();

        $bankPaymentCode = (int) $validated['bank_payment_code'];
        $memberId = (int) $validated['member_id'];
        $promotionId = (int) $validated['promotion_id'];
        $reason = trim((string) $validated['reason']);

        try {
            return DB::transaction(function () use (
                $datenow,
                $bankPaymentCode,
                $memberId,
                $promotionId,
                $reason
            ) {
                // 1) โหลด bank_payment + lock กันยิงซ้ำ
                //    ถ้า repo ของคุณไม่มี query builder ให้ดึง model จาก repo แล้วค่อย query
                $repo = $this->bankPaymentRepository;

                $model = method_exists($repo, 'makeModel') ? $repo->makeModel() : $repo->model();
                $bankPayment = $model->newQuery()
                    ->where('code', $bankPaymentCode)
                    ->lockForUpdate()
                    ->first();

                if (! $bankPayment) {
                    return $this->sendError('ไม่พบรายการฝาก (bank_payment) ที่ระบุ', 200);
                }

                // 2) กันเอารายการของคนอื่นมาใส่โปร
                $bpMember = (int) ($bankPayment->member_topup ?? 0);
                if ($bpMember !== $memberId) {
                    return $this->sendError('รายการฝากนี้ไม่ตรงกับสมาชิกที่ระบุ', 200);
                }

                // 3) (เลือกใช้ตามระบบคุณ) เช็คสถานะรายการฝากว่าพร้อมใส่โปรย้อนหลังหรือไม่
                //    ถ้าระบบคุณให้ใส่ได้เฉพาะ status=1 (สำเร็จ) ก็เปิดไว้
                if ((string) ($bankPayment->enable ?? 'Y') !== 'Y') {
                    return $this->sendError('รายการฝากนี้ถูกปิดใช้งาน (enable != Y)', 200);
                }
                if ((int) ($bankPayment->status ?? 0) !== 1) {
                    return $this->sendError('รายการฝากนี้ยังไม่อยู่สถานะสำเร็จ (status != 1)', 200);
                }

                // 4) กันผูกซ้ำ
                $existingProId = (int) ($bankPayment->pro_id ?? 0);
                if ($existingProId > 0) {
                    return $this->sendError('รายการฝากนี้ผูกโปรไว้แล้ว เพื่อกันจ่ายซ้ำ ระบบไม่อนุญาตให้ใส่โปรย้อนหลัง', 200);
                }

                // 5) ดึง amount
                $amount = $bankPayment->value ?? $bankPayment->amount ?? 0;
                $amount = (float) $amount;

                if ($amount <= 0) {
                    return $this->sendError('ยอดฝากไม่ถูกต้อง (amount <= 0)', 200);
                }

                // 6) ตรวจโปร/คำนวณสิทธิ์ ผ่าน logic เดิมของคุณ
                $promotion = $this->repository->checkSelectPro(
                    $promotionId,
                    $memberId,
                    $amount,
                    $datenow
                );

                // โครงสร้างเดิม: ต้องมี bonus > 0
                if (! isset($promotion['bonus']) || (float) $promotion['bonus'] <= 0) {
                    return $this->sendError(Lang::get('app.promotion.cannot'), 200);
                }

                // 7) เตรียม payload ให้ getPro()
                //    สำคัญ: getPro() ของคุณใช้ key เหล่านี้อยู่แล้ว
                $promotion['bank_payment_code'] = $bankPaymentCode;
                $promotion['amount'] = $amount;
                $promotion['member_code'] = $memberId;
                $promotion['reason'] = $reason;

                // 8) เรียกของเดิม: สร้าง bill + โยกโบนัส + update bank_payment.pro_id/pro_amount/msg ฯลฯ
                $response = $this->billRepository->getPro($promotion);

                if (($response['success'] ?? false) !== true) {
                    return $this->sendError($response['msg'] ?? 'ไม่สามารถใส่โปรย้อนหลังได้', 200);
                }

                $bill = $response['data'];

                MemberSelectPro::where('member_code', $memberId)->delete();

                // 9) success message ให้ถูกต้อง (ของเดิมคุณใช้ cannot ตอนสำเร็จ)
                return $this->sendSuccess(
                    'ใส่โปรย้อนหลังสำเร็จ โบนัสที่ได้รับ: '.(float) ($bill->credit_bonus ?? 0)
                );
            }, 3); // retry 3 ครั้ง เผื่อ deadlock เล็ก ๆ
        } catch (\Throwable $e) {
            report($e);

            return $this->sendError('เกิดข้อผิดพลาดในการใส่โปรย้อนหลัง โปรดลองใหม่อีกครั้ง', 200);
        }
    }
}
