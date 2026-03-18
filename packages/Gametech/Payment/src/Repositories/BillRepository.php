<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberLogRepository;
use Gametech\Member\Repositories\MemberPromotionLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class BillRepository extends Repository
{
    use ActivityLogger, ActivityLoggerUser;

    private $gameUserRepository;

    private $memberRepository;

    private $paymentLogRepository;

    private $memberLogRepository;

    private $memberCreditLogRepository;

    private $memberPromotionLogRepository;

    private $paymentWaitingRepository;

    private $bankPaymentRepository;

    /**
     * BillRepository constructor.
     */
    public function __construct(
        GameUserRepository $gameUserRepo,
        MemberRepository $memberRepo,
        PaymentLogRepository $paymentLogRepo,
        MemberLogRepository $memberLogRepo,
        MemberCreditLogRepository $memberCreditLogRepo,
        MemberPromotionLogRepository $memberPromotionLogRepo,
        PaymentWaitingRepository $paymentWaitingRepo,
        BankPaymentRepository $bankPaymentRepo,
        App $app
    ) {

        $this->gameUserRepository = $gameUserRepo;

        $this->memberRepository = $memberRepo;

        $this->memberLogRepository = $memberLogRepo;

        $this->paymentLogRepository = $paymentLogRepo;

        $this->memberCreditLogRepository = $memberCreditLogRepo;

        $this->memberPromotionLogRepository = $memberPromotionLogRepo;

        $this->paymentWaitingRepository = $paymentWaitingRepo;

        $this->bankPaymentRepository = $bankPaymentRepo;

        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model(): string
    {
        return \Gametech\Payment\Models\Bill::class;

    }

    public function transferWallet(array $data): array
    {
        $return['success'] = false;

        $ip = request()->ip();
        $limit = 0;
        $member_code = $data['member_code'];
        $game_code = $data['game_code'];
        $game_name = $data['game_name'];
        $user_code = $data['user_code'];
        $user_name = $data['user_name'];
        $pro_code = $data['pro_code'];
        $amount = $data['amount'];
        $bonus = $data['bonus'];
        $total = $amount;

        $game_balance = $data['game_balance'];

        $gameuser = $this->gameUserRepository->getOneUserNew($user_code, $game_code);
        if ($gameuser['success'] === false) {
            ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' พบปัญหา อัพเดทยอดเงินในเกมไม่ได้');
            $return['msg'] = 'อัพเดทยอดเงินในเกมไม่ได้';

            return $return;
        }

        $user = $gameuser['data'];

        ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'เตรียมทำรายการโยกออก จำนวนเงินที่แจ้ง '.$total.'  / ยอดเครดิตในเกม ที่มีอยู่คือ '.$user->balance);

        if (! $user) {
            $return['msg'] = 'ไม่พบข้อมูล ID เกมนี้';

            return $return;
        }

        if ($user->balance != $game_balance) {
            ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' พบปัญหายอดเงินในการทำรายการไม่ถูกต้อง');
            $return['msg'] = 'ยอดเงินที่ทำรายการ ไม่ถูกต้อง โปรดทำรายการใหม่ อีกครั้งในภายหลัง';

            return $return;
        }

        if ($amount < $user->amount_balance) {
            ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' ไม่สามารถทำรายการได้เนื่องจากติดยอดเทิน');
            $return['msg'] = 'ไม่สามารถทำรายการได้ เนื่องจากยังไม่ผ่านเงื่อนไข โปรโมชั่น';

            return $return;
        }

        if ($amount > $user->balance) {
            ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' ไม่สามารถทำรายการได้เนื่องจาก ยอดเงินไม่ถูกต้อง');
            $return['msg'] = 'ไม่สามารถทำรายการได้ เนื่องจาก ยอดเงินไม่ถูกต้อง';

            return $return;
        }

        if ($user->withdraw_limit_amount > 0) {
            if ($amount > $user->withdraw_limit_amount) {
                ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' เกมมีการจำกัดยอดเงินที่ได้รับจริง ยอดอั้นถอนที่ระบุ '.$user->withdraw_limit_amount);
                $amount = $user->withdraw_limit_amount;
                $limit = $user->withdraw_limit_amount;
            }
        }

        if ($user->withdraw_limit > 0) {
            ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' เกมมีการจำกัดยอดเงินที่ได้รับจริง ยอดที่บังคับได้รับตอนถอน '.$user->withdraw_limit);
            $amount = $user->withdraw_limit;
            $limit = $user->withdraw_limit;

            if (floor($total) != floor($user->balance)) {
                ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' ยอดแจ้งถอน ไม่เท่ากับยอดเงินในเกม');
                $return['msg'] = 'ไม่สามารถทำรายการได้ เนื่องจาก ต้องโยกออกทั้งหมดตามเงื่อนไขโปรโมชั่น โปรดใส่จำนวนเต็มในการโยก สามารถเหลือเศษได้';

                return $return;
            }
        }

        $withdraw_limit = $limit;

        ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' เริ่มต้นทำรายการโยกเงิน');

        $bill = $this->create([
            'enable' => 'N',
            'ref_id' => '',
            'credit_before' => 0,
            'credit_after' => 0,
            'member_code' => $member_code,
            'game_code' => $game_code,
            'pro_code' => $pro_code,
            'transfer_type' => 2,
            'amount_request' => $total,
            'amount_limit' => $withdraw_limit,
            'amount' => $amount,
            'balance_before' => 0,
            'balance_after' => 0,
            'credit' => $amount,
            'credit_bonus' => $bonus,
            'credit_balance' => $total,
            'ip' => $ip,
            'user_create' => '',
            'user_update' => '',
        ]);

        $log = $this->memberCreditLogRepository->create([
            'enable' => 'N',
            'ip' => $ip,
            'credit_type' => 'D',
            'amount' => $amount,
            'bonus' => $bonus,
            'total' => $total,
            'balance_before' => 0,
            'balance_after' => 0,
            'credit' => $total,
            'credit_bonus' => 0,
            'credit_total' => $total,
            'credit_before' => 0,
            'credit_after' => 0,
            'member_code' => $member_code,
            'user_name' => $user->user_name,
            'game_code' => $game_code,
            'gameuser_code' => $user_code,
            'pro_code' => $pro_code,
            'bank_code' => 0,
            'refer_code' => $bill->code,
            'refer_table' => 'bills',
            'auto' => 'N',
            'remark' => 'โยกเงินออกจากเกมเข้า Wallet อ้างอิงบิล ID :'.$bill->code.($withdraw_limit > 0 ? ' ถูกจำกัดยอดถอนที่ '.$withdraw_limit : ''),
            'kind' => 'TRANSFER',
            'user_create' => '',
            'user_update' => '',
        ]);

        $response = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $total, false);
        if ($response['success'] === true) {
            ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' ระบบทำการถอนเงินออกจากเกมแล้ว');
        } else {
            $return['msg'] = 'ไม่สามารถ ทำรายการโยกเงินออกจากเกมได้ ';
            ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' ไม่สามารถถอนเงินออกจากเกมได้ '.$response['msg']);

            return $return;
        }

        DB::beginTransaction();

        try {

            $member = $this->memberRepository->find($member_code);

            $balance_before = $member->balance;
            $balance_after = ($balance_before + $amount);

            $newbill = $this->update([
                'enable' => 'Y',
                'ref_id' => $response['ref_id'],
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'user_create' => $member['name'],
                'user_update' => $member['name'],
            ], $bill->code);

            $this->memberCreditLogRepository->update([
                'enable' => 'Y',
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'user_create' => $member['name'],
                'user_update' => $member['name'],
            ], $log->code);

            $this->gameUserRepository->update([
                'balance' => $response['after'],
                'pro_code' => 0,
                'bill_code' => $bill->code,
                'turnpro' => 0,
                'amount' => 0,
                'bonus' => 0,
                'amount_balance' => 0,
                'withdraw_limit' => 0,
            ], $user_code);

            $member->balance += $amount;
            $member->save();

            DB::commit();

        } catch (Throwable $e) {
            ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', '(FAIL) จำนวนเงิน '.$total.' พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', '(FAIL) จำนวนเงิน '.$total.' ดำเนินการ Rollback การทำรายการแล้ว');

            $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $total);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', '(FAIL) จำนวนเงิน '.$total.' ระบบทำการคืนยอดเงินเข้าเกมแล้ว');
            } else {
                ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', '(FAIL) จำนวนเงิน '.$total.' ระบบไม่สามารถคืนยอดเงินเข้าเกม');
            }

            $return['msg'] = 'ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง';
            report($e);

            return $return;
        }

        ActivityLoggerUser::activity('โยกเงินจากเกม '.$game_name.' เข้ากระเป๋า Wallet', 'จำนวนเงิน '.$total.' ทำรายการโยกเงินสำเร็จ');
        $return['success'] = true;
        $return['data'] = $newbill;

        return $return;

    }

    public function transferGame(array $data): array
    {
        $config = core()->getConfigData();

        $return['success'] = false;

        $ip = request()->ip();

        $member_code = $data['member_code'];
        $game_code = $data['game_code'];
        $game_name = $data['game_name'];
        $user_code = $data['user_code'];
        $user_name = $data['user_name'];
        $pro_code = $data['pro_code'];
        $pro_id = $data['pro_id'];
        $pro_name = $data['pro_name'];
        $turnpro = $data['turnpro'];
        $withdraw_limit = $data['withdraw_limit'];
        $amount = $data['amount'];
        $bonus = $data['bonus'];
        $total = $data['total'];

        $member = $this->memberRepository->find($member_code);
        if (! $member) {
            $return['msg'] = 'ไม่พบข้อมูลสมาชิก';

            return $return;
        }

        $balance_before = $member->balance;
        $balance_after = ($balance_before - $amount);

        $money_text = 'จำนวนเงิน '.$amount.' โบนัส '.$bonus.' จากโปร '.$pro_name.' รวมเป็น '.$total;

        if ((($member->balance - $amount) < 0)) {
            ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.' พบปัญหายอดเงินในการทำรายการไม่ถูกต้อง');
            $return['msg'] = 'ยอด Wallet คงเหลือไม่ถูกต้อง';

            return $return;
        }

        $gameuser = $this->gameUserRepository->getOneUserNew($user_code, $game_code);
        if ($gameuser['success'] === false) {
            ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.' พบปัญหา อัพเดทยอดเงินในเกมไม่ได้ หรือ ลูกค้ามียอด Outstanding มากกว่า 0');

            $return['msg'] = $gameuser['msg'];

            return $return;
        }

        $user = $gameuser['data'];

        ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.' เริ่มต้นทำรายการโยกเงินเข้าเกม ยอดเกมเครดิตก่อนโยกคือ '.$user->balance);

        $bill = $this->create([
            'enable' => 'N',
            'ref_id' => '',
            'credit_before' => 0,
            'credit_after' => 0,
            'member_code' => $member_code,
            'game_code' => $game_code,
            'pro_code' => $pro_code,
            'transfer_type' => 1,
            'amount' => $amount,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
            'credit' => $amount,
            'credit_bonus' => $bonus,
            'credit_balance' => $total,
            'ip' => $ip,
            'user_create' => $member['name'],
            'user_update' => $member['name'],
        ]);

        $log = $this->memberCreditLogRepository->create([
            'enable' => 'N',
            'ip' => $ip,
            'credit_type' => 'W',
            'amount' => $amount,
            'bonus' => 0,
            'total' => $amount,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
            'credit' => $amount,
            'credit_bonus' => $bonus,
            'credit_total' => $total,
            'credit_before' => 0,
            'credit_after' => 0,
            'member_code' => $member_code,
            'user_name' => $member->user_name,
            'game_code' => $game_code,
            'gameuser_code' => $user_code,
            'pro_code' => $pro_code,
            'bank_code' => 0,
            'refer_code' => $bill->code,
            'refer_table' => 'bills',
            'auto' => 'N',
            'remark' => 'ระบบหัก Wallet ก่อนทำการโยกเงินเข้าเกมแล้ว',
            'kind' => 'TRANSFER',
            'user_create' => $member['name'],
            'user_update' => $member['name'],
        ]);

        ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.' ระบบทำการ หัก Wallet ก่อนโยกเข้าเกม');

        $member->balance -= $amount;
        $member->save();

        $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $total, false);

        if ($response['success'] === true) {
            ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.' ระบบทำการฝากเงินเข้าเกมแล้ว สำเร็จ');
        } elseif ($response['success'] === false) {

            if ($config['auto_wallet'] == 'Y') {

                $this->memberCreditLogRepository->update([
                    'balance_after' => $balance_before,
                    'remark' => 'ระบบคืน Wallet แล้วเนื่องจาก ไม่สามารถโยกเข้าเกมได้',
                ], $log->code);

                $member->balance += $amount;
                $member->save();

                ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.' ไม่สามารถฝากเงินเข้าเกมได้ คืน Wallet แล้ว');

            } else {

                $this->memberCreditLogRepository->update([
                    'remark' => 'ไม่สามารถโยกเข้าเกมได้ ระบบไม่ได้คืน Wallet ให้',
                ], $log->code);

                ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.' ไม่สามารถฝากเงินเข้าเกมได้ ระบบคืนออโต้ปิดใช้งานอยู่');

            }

            $return['msg'] = 'ไม่สามารถ ทำรายการโยกเงินเข้าเกมได้';

            return $return;
        } else {
            ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.' ไม่มีการตอบสนอง ระบบไม่ได้คืน Wallet โปรดตรวจสอบ');
            $return['msg'] = 'ไม่สามารถ ทำรายการโยกเงินเข้าเกมได้';

            return $return;
        }

        DB::beginTransaction();

        try {

            //            $bill->enable = 'Y';
            //            $bill->credit_after = $response['after'];
            //            $bill->balance_after = $balance_after;
            //            $bill->save();

            $bill->enable = 'Y';
            $bill->credit_before = $response['before'];
            $bill->credit_after = $response['after'];
            $bill->ref_id = $response['ref_id'];
            $bill->save();

            //            $this->update([
            //                'enable' => 'Y',
            //                'ref_id' => $response['ref_id'],
            //                'credit_before' => $response['before'],
            //                'credit_after' => $response['after']
            //            ], $bill->code);

            $this->memberCreditLogRepository->update([
                'enable' => 'Y',
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'remark' => 'โยกเงินจาก Wallet เข้าเกม อ้างอิงบิล ID :'.$bill->code,
            ], $log->code);

            $this->gameUserRepository->update([
                'balance' => $response['after'],
                'pro_code' => $pro_code,
                'bill_code' => $bill->code,
                'turnpro' => $turnpro,
                'amount' => $amount,
                'bonus' => $bonus,
                'amount_balance' => ($total * $turnpro),
                'withdraw_limit' => $withdraw_limit,
            ], $user_code);

            if ($pro_code > 0) {

                $this->memberPromotionLogRepository->create([
                    'date_start' => now()->toDateString(),
                    'bill_code' => $bill->code,
                    'member_code' => $member_code,
                    'game_code' => $game_code,
                    'game_name' => $game_name,
                    'gameuser_code' => $user_code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'turnpro' => $turnpro,
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'amount_balance' => ($total * $turnpro),
                    'withdraw_limit' => $withdraw_limit,
                    'complete' => 'N',
                    'enable' => 'Y',
                    'user_create' => $member['name'],
                    'user_update' => $member['name'],
                ]);

            }

            $this->bankPaymentRepository->where('member_topup', $member_code)->where('pro_check', 'N')->update([
                'pro_check' => 'Y',
                'user_update' => $member['name'],
            ]);

            if ($pro_id === 'pro_newuser') {
                $member->status_pro = 1;
            }
            if ($pro_code > 0) {
                $member->pro_status = 'Y';
                $member->promotion = 'Y';
            }

            $member->save();

            DB::commit();

        } catch (Throwable $e) {
            ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.'(FAIL) พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.'(FAIL) ดำเนินการ Rollback การทำรายการแล้ว');

            $response = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $total);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.'(FAIL) ระบบทำการถอนเงินออกจากเกมแล้ว');
                $member->balance += $amount;
                $member->save();
                ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.'(FAIL) ระบบทำการคืนยอด Wallet แล้ว');
            } else {
                ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.'(FAIL) ระบบไม่สามารถถอนเงินออกจากเกมได้ จึงไม่คืน Wallet');
            }

            $return['msg'] = 'ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง';
            report($e);

            return $return;
        }

        ActivityLoggerUser::activity('โยกเงิน Wallet เข้าเกม '.$game_name, $money_text.' ทำรายการโยกเงินสำเร็จ');
        $return['success'] = true;
        $return['data'] = $bill;

        return $return;

    }

    public function requestTransferGame(array $data): array
    {
        $return['success'] = false;

        $ip = request()->ip();

        $member_code = $data['member_code'];
        $game_code = $data['game_code'];
        $game_name = $data['game_name'];
        $user_code = $data['user_code'];
        $user_name = $data['user_name'];
        $pro_code = $data['pro_code'];
        $pro_name = $data['pro_name'];
        $turnpro = $data['turnpro'];
        $amount = $data['amount'];
        $bonus = $data['bonus'];
        $total = $data['total'];

        $member = $this->memberRepository->find($member_code);
        if (! $member) {
            $return['msg'] = 'ไม่พบข้อมูลสมาชิก';

            return $return;
        }

        $balance_before = $member->balance;
        $balance_after = ($balance_before - $amount);

        if ((($member->balance - $amount) < 0)) {
            ActivityLoggerUser::activity('Request โยกเงิน Wallet เข้าเกม '.$game_name, 'จำนวนเงิน '.$total.' พบปัญหายอดเงินในการทำรายการไม่ถูกต้อง');
            $return['msg'] = 'ยอด Wallet คงเหลือไม่ถูกต้อง';

            return $return;
        }

        ActivityLoggerUser::activity('Request โยกเงิน Wallet เข้าเกม '.$game_name, 'จำนวนเงิน '.$total.' เริ่มต้นทำรายการแจ้งทีมงานเพื่อโยกเงิน');

        DB::beginTransaction();
        try {

            $bill = $this->paymentWaitingRepository->create([
                'member_code' => $member_code,
                'game_code' => $game_code,
                'pro_code' => $pro_code,
                'transfer_type' => 1,
                'amount' => $amount,
                'bonus' => $bonus,
                'total' => $total,
                'ip' => $ip,
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);

            //            $this->memberLogRepository->create([
            //                'member_code' => $member_code,
            //                'mode' => 'TRANSFER_IN_REQUEST',
            //                'menu' => 'transfergame',
            //                'record' => $member_code,
            //                'remark' => 'แจ้งโยกเงินออกจาก Wallet เข้าเกม',
            //                'item_before' => '',
            //                'item' => serialize($data),
            //                'ip' => $ip,
            //                'user_create' => $member['name']
            //            ]);

            $this->memberCreditLogRepository->create([
                'ip' => $ip,
                'credit_type' => 'W',
                'amount' => $amount,
                'bonus' => 0,
                'total' => $amount,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => 0,
                'credit_after' => 0,
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'game_code' => $game_code,
                'gameuser_code' => $user_code,
                'bank_code' => 0,
                'refer_code' => $bill->code,
                'refer_table' => 'payments_waiting',
                'auto' => 'N',
                'remark' => 'แจ้งโยกเงินจาก Wallet เข้าเกม  อ้างอิงบิล ID :'.$bill->code,
                'kind' => 'TRANSFER',
                'user_create' => $member['name'],
                'user_update' => $member['name'],
            ]);

            $member->bankPayments()->where('member_topup', $member_code)->where('pro_check', 'N')->update([
                'pro_check' => 'Y',
                'user_update' => $member['name'],
            ]);

            $member->balance -= $amount;
            $member->save();

            DB::commit();
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Request โยกเงิน Wallet เข้าเกม '.$game_name, 'จำนวนเงิน '.$total.' พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLoggerUser::activity('Request โยกเงิน Wallet เข้าเกม '.$game_name, 'จำนวนเงิน '.$total.' ดำเนินการ Rollback การทำรายการแล้ว');

            $return['msg'] = 'ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง';
            report($e);

            return $return;
        }

        ActivityLoggerUser::activity('Request โยกเงิน Wallet เข้าเกม '.$game_name, 'จำนวนเงิน '.$total.' ทำรายการแจ้งทีมงานเพื่อโยกเงินสำเร็จ');

        $return['success'] = true;
        $return['data'] = $bill;

        return $return;

    }

    public function confirmWallet(array $data): array
    {
        $return['success'] = false;

        $ip = request()->ip();

        $member_code = $data['member_code'];
        $game_code = $data['game_code'];
        $game_name = $data['game_name'];
        $user_code = $data['user_code'];
        $user_name = $data['user_name'];
        $pro_code = $data['pro_code'];
        $pro_name = $data['pro_name'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $turnpro = $data['turnpro'];
        $withdraw_limit = $data['withdraw_limit'];
        $amount = $data['amount'];
        $bonus = $data['bonus'];
        $total = $data['total'];
        $payment_code = $data['payment_code'];
        $balance_before = $data['member_balance'];
        $balance_after = ($balance_before - $amount);

        $member = $this->memberRepository->find($member_code);

        if (! $member) {
            $return['msg'] = 'ไม่พบข้อมูลสมาชิก';

            return $return;
        }

        ActivityLogger::activitie('Confirm โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'เริ่มต้นทำรายการยืนยันการโยกเงิน');

        $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $total, false);
        if ($response['success'] === true) {
            ActivityLogger::activitie('Confirm โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'ระบบทำการฝากเงินเข้าเกมแล้ว');

        } else {
            ActivityLogger::activitie('Confirm โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'ไม่สามารถฝากเงินเข้าเกมได้');
            $return['msg'] = 'ไม่สามารถ ทำรายการโยกเงินเข้าเกมได้';

            return $return;
        }

        DB::beginTransaction();

        try {

            $bill = $this->create([
                'enable' => 'Y',
                'ref_id' => $response['ref_id'],
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'member_code' => $member_code,
                'game_code' => $game_code,
                'pro_code' => $pro_code,
                'transfer_type' => 1,
                'amount' => $amount,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'credit' => $amount,
                'credit_bonus' => $bonus,
                'credit_balance' => $total,
                'emp_code' => $emp_code,
                'ip' => $ip,
                'user_create' => $emp_name,
                'user_update' => $emp_name,
            ]);

            //            $this->paymentLogRepository->create([
            //                'msg' => 'ทีมงานอนุมัติ การโยกเงินออกจาก Wallet เข้าเกม เรียบร้อย',
            //                'status' => 'COMPLETE',
            //                'showmsg' => 'Y',
            //                'confirm' => 'Y',
            //                'enable' => 'Y',
            //                'bill_code' => $bill->code,
            //                'member_code' => $member_code,
            //                'game_code' => $game_code,
            //                'token' => '',
            //                'transfer_type' => 1,
            //                'amount' => $amount,
            //                'ip' => $ip,
            //                'user_create' => $emp_name,
            //                'user_update' => $emp_name
            //            ]);

            //            $this->memberLogRepository->create([
            //                'member_code' => $member_code,
            //                'mode' => 'TRANSFER_IN',
            //                'menu' => 'payments_waiting',
            //                'record' => $member_code,
            //                'remark' => 'ทีมงานอนุมัติ การโยกเงินออกจาก Wallet เข้าเกม',
            //                'item_before' => '',
            //                'item' => serialize($data),
            //                'ip' => $ip,
            //                'user_create' => $member['name']
            //            ]);

            $this->memberCreditLogRepository->create([
                'ip' => $ip,
                'credit_type' => 'W',
                'amount' => $amount,
                'bonus' => 0,
                'total' => $amount,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'credit' => $amount,
                'credit_bonus' => $bonus,
                'credit_total' => $total,
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'game_code' => $game_code,
                'gameuser_code' => $user_code,
                'pro_code' => $pro_code,
                'bank_code' => 0,
                'refer_code' => $bill->code,
                'refer_table' => 'bills',
                'emp_code' => $emp_code,
                'auto' => 'N',
                'remark' => 'ทีมงานอนุมัติ การโยกเงินจาก Wallet เข้าเกม อ้างอิงบิล ID :'.$bill->code,
                'kind' => 'CONFIRM',
                'user_create' => $emp_name,
                'user_update' => $emp_name,
            ]);

            $this->paymentWaitingRepository->update([
                'credit' => $amount,
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'credit_balance' => $total,
                'ip_admin' => $ip,
                'confirm' => 'Y',
                'date_approve' => now()->toDateTimeString(),
                'emp_code' => $emp_code,
                'user_update' => $emp_name,
            ], $payment_code);

            if ($pro_code == 1) {
                $member->status_pro = 1;
            }
            if ($pro_code > 0) {
                $member->pro_status = 'Y';
                $member->promotion = 'Y';

                $this->memberPromotionLogRepository->create([
                    'date_start' => now()->toDateString(),
                    'bill_code' => $bill->code,
                    'member_code' => $member_code,
                    'game_code' => $game_code,
                    'game_name' => $game_name,
                    'gameuser_code' => $user_code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'turnpro' => $turnpro,
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'amount_balance' => ($total * $turnpro),
                    'withdraw_limit' => $withdraw_limit,
                    'complete' => 'N',
                    'enable' => 'Y',
                    'emp_code' => $emp_code,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);
            }

            $this->gameUserRepository->update([
                'balance' => $response['after'],
                'pro_code' => $pro_code,
                'bill_code' => $bill->code,
                'turnpro' => $turnpro,
                'amount' => $amount,
                'bonus' => $bonus,
                'amount_balance' => ($total * $turnpro),
                'withdraw_limit' => $withdraw_limit,
            ], $user_code);

            $member->save();
            DB::commit();

        } catch (Throwable $e) {
            ActivityLogger::activitie('Confirm โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLogger::activitie('Reject โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'ดำเนินการ Rollback การทำรายการแล้ว');

            $response = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $total);
            if ($response['success'] === true) {
                ActivityLogger::activitie('Confirm โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'ระบบทำการถอนเงินออกจากเกมแล้ว');

            } else {
                ActivityLogger::activitie('Confirm โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'ระบบไม่สามารถถอนเงินออกจากเกมได้');
            }

            $return['msg'] = 'ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง';
            report($e);

            return $return;
        }

        ActivityLogger::activitie('Confirm โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'ทำรายการยืนยันการโยกเงินสำเร็จ');

        $return['success'] = true;
        $return['data'] = $bill;

        return $return;

    }

    public function rejectWallet(array $data): array
    {
        $return['success'] = false;

        $ip = request()->ip();

        $member_code = $data['member_code'];
        $game_code = $data['game_code'];
        $game_name = $data['game_name'];
        $user_code = $data['user_code'];
        $user_name = $data['user_name'];
        $pro_code = $data['pro_code'];
        $pro_name = $data['pro_name'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $turnpro = $data['turnpro'];
        $amount = $data['amount'];
        $bonus = $data['bonus'];
        $total = $data['total'];
        $remark = $data['remark'];
        $payment_code = $data['payment_code'];
        $balance_before = $data['member_balance'];
        $balance_after = ($balance_before + $amount);

        $member = $this->memberRepository->find($member_code);
        if (! $member) {
            $return['msg'] = 'ไม่พบข้อมูลสมาชิก';

            return $return;
        }

        ActivityLogger::activitie('Reject โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'เริ่มต้นทำรายการคืนยอดการแจ้งโยกเงิน');

        DB::beginTransaction();

        try {

            $bill = $this->memberCreditLogRepository->create([
                'ip' => $ip,
                'credit_type' => 'D',
                'amount' => $amount,
                'bonus' => 0,
                'total' => $amount,
                'balance_before' => $member->balance,
                'balance_after' => ($member->balance + $amount),
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => 0,
                'credit_after' => 0,
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'game_code' => $game_code,
                'gameuser_code' => $user_code,
                'pro_code' => 0,
                'bank_code' => 0,
                'refer_code' => $payment_code,
                'refer_table' => 'payments_waiting',
                'emp_code' => $emp_code,
                'auto' => 'N',
                'remark' => 'ทีมงานคืนยอด การโยกเงินจาก Wallet เข้าเกม อ้างอิงบิล ID :'.$payment_code,
                'kind' => 'ROLLBACK',
                'user_create' => $emp_name,
                'user_update' => $emp_name,
            ]);

            $this->paymentWaitingRepository->update([
                'ip_admin' => $ip,
                'confirm' => 'N',
                'remark' => $remark,
                'emp_code' => $emp_code,
                'user_update' => $emp_name,
            ], $payment_code);

            $member->balance += $amount;
            $member->save();

            DB::commit();

        } catch (Throwable $e) {
            ActivityLogger::activitie('Reject โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLogger::activitie('Reject โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'ดำเนินการ Rollback การทำรายการแล้ว');

            $return['msg'] = 'ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง';
            report($e);

            return $return;
        }

        ActivityLogger::activitie('Reject โยกเงิน Wallet เข้าเกม User : '.$member->user_name, 'ทำรายการคืนยอดการแจ้งโยกเงินสำเร็จ');

        $return['success'] = true;
        $return['data'] = $bill;

        return $return;

    }

    public function getPro(array $data): array
    {
        $return = ['success' => false];

        $ip = request()->ip();

        $reason            = $data['reason'] ?? '';
        $member_code       = (int) ($data['member_code'] ?? 0);
        $bank_payment_code = (int) ($data['bank_payment_code'] ?? 0);

        $pro_code          = (int) ($data['pro_code'] ?? 0);
        $pro_id            = $data['pro_id'] ?? null;
        $pro_name          = (string) ($data['pro_name'] ?? '');

        $turnpro           = (float) ($data['turnpro'] ?? 0);
        $withdraw_limit    = (float) ($data['withdraw_limit'] ?? 0);
        $withdraw_limit_rate = (float) ($data['withdraw_limit_rate'] ?? 0);

        $bonus             = (float) ($data['bonus'] ?? 0);
        $amount            = (float) ($data['amount'] ?? 0);
        $total             = (float) ($data['total'] ?? 0);

        $member = $this->memberRepository->find($member_code);
        if (! $member) {
            $return['msg'] = 'ไม่พบข้อมูลสมาชิก';
            return $return;
        }

        // นโยบาย: ถ้าจะ "ใส่โปรย้อนหลัง" แต่ไม่อยากบังคับยอดคงเหลือ ให้พิจารณาปิดเงื่อนไขนี้
        if ($member->balance < $amount) {
            $return['msg'] = 'ยอดเครดิตปัจจุบัน ไม่ถูกต้อง';
            return $return;
        }

        $game = core()->getGame();
        $game_user = $this->gameUserRepository->findOneWhere([
            'member_code' => $member->code,
            'game_code'   => $game->code,
            'enable'      => 'Y',
        ]);

        if (! $game_user) {
            $return['msg'] = 'ไม่พบข้อมูล game user ของสมาชิก';
            return $return;
        }

        $game_code = $game->code;
        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $game_name = $game->name;



        $money_text = 'User '.$member->user_name.' Game ID : '.$user_name.' ผูกโปรหลังเติมเงิน ได้รับ โบนัส '.$bonus.' จากโปร '.$pro_name;

        // 1) ยิงฝากโบนัสเข้าเกม "ก่อน" แล้วค่อยทำ DB
        //    แต่ถ้า DB fail ต้องถอนคืน (compensate) เพื่อไม่ให้ยอดเกมลอย
        $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $bonus, false);
        if (! ($response['success'] ?? false)) {
            ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม '.$game_name, $money_text.' ไม่สามารถฝากเงินเข้าเกมได้');
            $return['msg'] = 'ไม่สามารถฝากเงินเข้าเกมได้';
            return $return;
        }

        $balance_before = (float) $response['before'];
        $balance_after  = $response['after'];

        ActivityLoggerUser::activity(
            'Single ฝากเงินเข้าเกม '.$game_name,
            $money_text.' (ผูกโปร) ระบบทำการฝากเงินเข้าเกมแล้ว ยอดก่อน '.$response['before'].' ยอดหลัง '.$response['after']
        );

        DB::beginTransaction();

        try {
            // 2) สร้างบิล
            $bill = $this->create([
                'complete'       => 'Y',
                'enable'         => 'Y',
                'refer_code'     => $bank_payment_code,
                'refer_table'    => 'bank_payment',
                'ref_id'         => $response['ref_id'],
                'credit_before'  => $response['before'],
                'credit_after'   => $response['after'],
                'member_code'    => $member->code,
                'game_code'      => $game_code,
                'gameuser_code'  => $user_code,
                'pro_code'       => $pro_code,
                'pro_name'       => $pro_name,
                'method'         => 'TOPUP',
                'transfer_type'  => 1,
                'amount'         => $bonus,
                'balance_before' => $response['before'],
                'balance_after'  => $response['after'],
                'credit'         => $bonus,
                'credit_bonus'   => $bonus,
                'credit_balance' => $bonus,
                'amount_request' => 0,
                'amount_limit'   => 0,
                'remark'         => $reason,
                'ip'             => $ip,
                'user_create'    => $member->name,
                'user_update'    => $member->name,
            ]);

            // 3) อัปเดต game_user
            $game_user->balance = $balance_after;
            $game_user->pro_code = $pro_code;
            $game_user->bill_code = $bill->code;
            $game_user->turnpro = $turnpro;
            $game_user->amount += $amount;
            $game_user->bonus += $bonus;

            // NOTE: สูตร amount_balance / withdraw_limit_amount เป็น logic เดิมของคุณ
            $game_user->amount_balance += (($balance_before - $amount) + ($total * $turnpro));
            $game_user->withdraw_limit = $withdraw_limit;
            $game_user->withdraw_limit_rate = $withdraw_limit_rate;
            $game_user->withdraw_limit_amount += (($amount + $bonus) * $withdraw_limit_rate);

            $game_user->save();

            // 4) เซ็ต amount_request/amount_limit แล้ว "ต้อง save" ไม่งั้นหาย
            $bill->amount_request = $game_user->amount_balance;
            $bill->amount_limit   = $game_user->withdraw_limit_amount;
            $bill->save();

            // 5) log เครดิตโบนัส
            $this->memberCreditLogRepository->create([
                'enable'              => 'Y',
                'ip'                  => $ip,
                'credit_type'         => 'D',
                'amount'              => 0,
                'bonus'               => $bonus,
                'total'               => $bonus,
                'balance_before'      => $balance_before,
                'balance_after'       => $balance_after,
                'credit'              => 0,
                'credit_bonus'        => $bonus,
                'credit_total'        => $bonus,
                'credit_before'       => $balance_before,
                'credit_after'        => $balance_after,
                'member_code'         => $member_code,
                'user_name'           => $member->user_name,
                'game_code'           => $game_code,
                'gameuser_code'       => $user_code,
                'pro_code'            => $pro_code,
                'bank_code'           => 0,
                'refer_code'          => $bill->code,
                'refer_table'         => 'bills',
                'auto'                => 'N',
                'remark'              => $reason.' อ้างอิงเลขที่บิล : '.$bill->code.' / มียอดฝาก : '.$amount,
                'kind'                => 'PROMOTION',
                'amount_balance'      => $game_user->amount_balance,
                'withdraw_limit'      => $game_user->withdraw_limit,
                'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                'user_create'         => $member->name,
                'user_update'         => $member->name,
            ]);

            // 6) promotion log
            $this->memberPromotionLogRepository->create([
                'date_start'          => now()->toDateString(),
                'bill_code'           => $bill->code,
                'member_code'         => $member_code,
                'game_code'           => $game_code,
                'game_name'           => $game_name,
                'gameuser_code'       => $user_code,
                'pro_code'            => $pro_code,
                'pro_name'            => $pro_name,
                'turnpro'             => $turnpro,
                'balance'             => ($balance_before - $amount),
                'amount'              => $amount,
                'bonus'               => $bonus,
                'amount_balance'      => ($total * $turnpro),
                'total_amount_balance'=> (($balance_before - $amount) + ($total * $turnpro)),
                'withdraw_limit'      => $withdraw_limit,
                'withdraw_limit_rate' => $withdraw_limit_rate,
                'complete'            => 'N',
                'enable'              => 'Y',
                'user_create'         => $member->name,
                'user_update'         => $member->name,
            ]);

            // 7) update bank_payment => ผูกโปร
            $checkPayment = $this->bankPaymentRepository->findOrFail($bank_payment_code);
            if ($checkPayment) {
                $this->bankPaymentRepository->update([
                    'pro_id'       => $pro_code,
                    'pro_amount'   => $bonus,
                    'msg'          => $pro_name,
                    'user_update'  => $member->name,
                ], $checkPayment->code);
            }

            // 8) flag member
            if ($pro_id == 'pro_newuser') {
                $member->status_pro = 1;
            }
            if ($pro_code > 0) {
                $member->pro_status = 'Y';
                $member->promotion  = 'Y';
            }

            $member->balance += $bonus;
            $member->save();

            DB::commit();

            $return['success'] = true;
            $return['data'] = $bill;
            return $return;

        } catch (Throwable $e) {
            DB::rollBack();

            // 9) compensate: ถอนโบนัสคืนจากเกม (สำคัญมาก กันยอดเกมลอย)
            try {
                $this->gameUserRepository->UserWithdraw($game_code, $user_name, $bonus, false);
            } catch (Throwable $ignored) {
                // ปล่อยไว้ แต่ควรมี log เพิ่มถ้าคุณมี channel เฉพาะ
            }

            $return['msg'] = 'ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง';
            report($e);

            return $return;
        }
    }

}
