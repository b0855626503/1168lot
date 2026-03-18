<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Core\Models\CouponListProxy;
use Gametech\Core\Repositories\CouponListRepository;
use Gametech\Core\Repositories\CouponRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberCreditFreeLogRepository;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BonusRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;


class CouponController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $repository;

    protected $memberRepository;
    protected $bonusRepository;

    protected $bankPaymentRepository;
    protected $couponListRepository;

    protected $memberCreditLogRepository;
    protected $memberCreditFreeLogRepository;

    protected $gameUserRepository;


    public function __construct
    (
        CouponRepository              $repository,
        CouponListRepository          $couponListRepository,
        BonusRepository               $bonusRepository,
        BankPaymentRepository         $bankPaymentRepository,
        MemberRepository              $memberRepository,
        MemberCreditLogRepository     $memberCreditLogRepository,
        GameUserRepository             $gameUserRepository,
        MemberCreditFreeLogRepository $memberCreditFreeLogRepository
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->repository = $repository;

        $this->couponListRepository = $couponListRepository;

        $this->bonusRepository = $bonusRepository;

        $this->bankPaymentRepository = $bankPaymentRepository;

        $this->memberRepository = $memberRepository;

        $this->memberCreditLogRepository = $memberCreditLogRepository;

        $this->memberCreditFreeLogRepository = $memberCreditFreeLogRepository;

        $this->gameUserRepository = $gameUserRepository;

    }

    public function redeem(Request $request)
    {

        $config = core()->getConfigData();
        $status = false;
        $datenow = now()->toDateString();
        $datetime = now()->toDateTimeString();
        $code = $request->input('coupon');

        $member = $this->user();


        $coupon = $this->couponListRepository->scopeQuery(function ($query) use ($code, $datenow) {
            return $query->whereDate('date_start', '<=', $datenow)->whereDate('date_stop', '>=', $datenow)->where('status', 'N')->where('enable', 'Y')->where('name', $code);
        })->first();


        if (isset($coupon)) {
            $main = $this->repository->findOneByField('code', $coupon['coupon_code']);
            if (isset($main)) {
                if ($main['enable'] == 'N') {
                    return $this->sendError(Lang::get('app.coupon.cannot'), 200);
                }

                $coupon_chk = CouponListProxy::where('member_code', $member->code)->where('coupon_code', $main->code)->where('enable', 'Y')->first();

                if (isset($coupon_chk)) {
                    return $this->sendError(Lang::get('app.coupon.cannot_rejoin'), 200);
                }

                if ($coupon['newuser'] == 'Y') {
                    if ($member->status_pro == 1) {
                        return $this->sendError(Lang::get('app.coupon.condition'), 200);
                    }
                }

                if ($coupon['norefill'] == 'Y') {
                    $payment = $this->bankPaymentRepository->where('member_topup', $member->code)->where('status', 1)->where('enable', 'Y')->where('bankstatus', 1)->sum('value');
                    if ($payment > 0) {
                        return $this->sendError(Lang::get('app.coupon.condition'), 200);
                    }
                } else {
                    if ($coupon['money'] > 0) {
                        if (is_null($main['refill_start'])) {
                            $payment = $this->bankPaymentRepository->where('member_topup', $member->code)->where('status', 1)->where('enable', 'Y')->where('bankstatus', 1)->sum('value');

                        } else {
                            $payment = $this->bankPaymentRepository->where('member_topup', $member->code)->where('status', 1)->where('enable', 'Y')->where('bankstatus', 1)->whereBetween('date_approve', array($main['refill_start'], $main['refill_stop']))->sum('value');

                        }

//                    $payment = $this->bankPaymentRepository->findWhere(['status' => 1, 'enable' => 'Y', 'bankstatus' => 1]);

                        if ($payment >= $coupon['money']) {
                            $status = true;
                        } else {
                            return $this->sendError(Lang::get('app.coupon.condition'), 200);
                        }
                    }
                }

                if ($coupon['date_expire'] == 0) {
                    $expire = null;
                } else {
                    $expire = now()->addDays($coupon['date_expire']);
                }


                $bill = $this->bonusRepository->create([
                    'refer_coupon' => $coupon['code'],
                    'name' => $main['name'],
                    'cashback' => $coupon['cashback'],
                    'member_code' => $member->code,
                    'value' => $coupon['value'],
                    'turnpro' => $coupon['turnpro'],
                    'amount_limit' => $coupon['amount_limit'],
                    'date_expire' => $expire,
                    'status' => 'N',
                    'user_create' => 'SYSTEM',
                    'user_update' => 'SYSTEM',
                ]);

                if ($coupon->cashback == 'Y') {

                    $this->memberCreditFreeLogRepository->create([
                        'enable' => 'Y',
                        'ip' => request()->ip(),
                        'credit_type' => 'D',
                        'amount' => $coupon['value'],
                        'bonus' => 0,
                        'total' => 0,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => $coupon['value'],
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'game_code' => 0,
                        'gameuser_code' => 0,
                        'pro_code' => 0,
                        'bank_code' => 0,
                        'refer_code' => $bill->code,
                        'refer_table' => 'bonus',
                        'auto' => 'N',
                        'remark' => "ได้รับเครดิตโบนัส (ฟรี) จากคูปอง " . $coupon['name'] . " จำนวน  :" . $coupon['value'],
                        'kind' => 'BONUS',
                        'user_create' => '',
                        'user_update' => ''
                    ]);

                } else {

                    $this->memberCreditLogRepository->create([
                        'enable' => 'Y',
                        'ip' => request()->ip(),
                        'credit_type' => 'D',
                        'amount' => $coupon['value'],
                        'bonus' => 0,
                        'total' => 0,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => $coupon['value'],
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'game_code' => 0,
                        'gameuser_code' => 0,
                        'pro_code' => 0,
                        'bank_code' => 0,
                        'refer_code' => $bill->code,
                        'refer_table' => 'bonus',
                        'auto' => 'N',
                        'remark' => "ได้รับเครดิตโบนัส จากคูปอง " . $coupon['name'] . " จำนวน  :" . $coupon['value'],
                        'kind' => 'BONUS',
                        'user_create' => '',
                        'user_update' => ''
                    ]);

                }


                $coupon->status = 'Y';
                $coupon->member_code = $member->code;
                $coupon->date_update = $datetime;
                $coupon->save();

                return $this->sendSuccess(Lang::get('app.coupon.credit_amount') . $coupon['value']);


            } else {
                return $this->sendError(Lang::get('app.coupon.fail'), 200);
            }


        } else {
            return $this->sendError(Lang::get('app.coupon.empty'), 200);
        }

    }

    public function bonusList()
    {
        $datenow = now()->toDateString();
        $id = $this->id();
        $datas = $this->bonusRepository->findWhere([
            'member_code' => $id,
            'status' => 'N',
        ]);

        $bonuses = collect();

        if ($datas && $datas->count()) {
            foreach ($datas as $item) {
                // ข้ามถ้าหมดอายุ
                if (!is_null($item->date_expire) && $datenow >= $item->date_expire) {
                    continue;
                }

                $bonuses->push([
                    'code' => $item->code,
                    'type' => $item->cashback === 'Y' ? 'freecredit' : 'credit',
                    'value' => $item->value,
                    'turnpro' => $item->turnpro,
                    'limit' => $item->amount_limit,
                    'rate' => $item->rate ?? '',
                    'date_expire' => $item->date_expire,
                ]);
            }
        }

        // ถ้าไม่มีข้อมูล
        if ($bonuses->isEmpty()) {
            return $this->sendResponseNew([
                'data' => [],
                'message' => Lang::get('app.coupon.notfound'),
            ], 'complete');
        }

        return $this->sendResponseNew([
            'data' => $bonuses,
            'message' => 'complete',
        ], 'complete');
    }

    public function getBonus_(Request $request)
    {
        $config = core()->getConfigData();
        $code = $request->input('id');
        $member = $this->user();

        $gamelist = core()->getGame();
        $bonus = $this->bonusRepository->findOneWhere(['member_code' => $member->code, 'code' => $code, 'status' => 'N']);
        if (isset($bonus)) {

            $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $gamelist->code, 'enable' => 'Y']);
            if (!$game_user) {
                return $this->sendError(__('app.coupon.nomember'), 200);

            }

            if ($config->seamless == 'Y') {
                if ($bonus->turnpro > 0 || $bonus->amount_limit > 0) {
                    if ($member->balance > $config->pro_reset) {
                        return $this->sendError(__('app.coupon.cannot_get') . $config->pro_reset, 200);
                    }
                }
                $gameCurrent = $member->balance;
            } else {

                if ($config->multigame_open == 'Y') {
                    if ($bonus->turnpro > 0 || $bonus->amount_limit > 0) {
                        if ($member->balance > $config->pro_reset) {
                            return $this->sendError(__('app.coupon.cannot_get') . $config->pro_reset, 200);
                        }
                    }
                    $gameCurrent = $member->balance;
                }

                if ($config->multigame_open == 'N') {

                    $chk = $this->gameUserRepository->checkBalance($gamelist->id, $game_user->user_name);
                    if ($chk['success'] !== true) {
                        return $this->sendError(__('app.status.tryagain') . $config->pro_reset, 200);
                    }
                    $gameCurrent = (int)floor($chk['score']);
                    if ($bonus->turnpro > 0 || $bonus->amount_limit > 0) {
                        if ($gameCurrent > $config->pro_reset) {
                            return $this->sendError(__('app.coupon.cannot_get') . $config->pro_reset, 200);
                        }
                    }
                }
            }


            $amount = $bonus->value;
            $credit_before = $gameCurrent;
            $credit_after = $credit_before + $amount;

            $total = $credit_after;
            $amount_total = ($amount * $bonus->turnpro);
            $withdraw_limit_amount = ($amount * $bonus->amount_limit);

            if ($config->seamless == 'N' && $config->multigame_open == 'N') {
                $response = $this->gameUserRepository->UserDeposit($gamelist->code, $game_user->user_name, $amount, false);
                if ($response['success'] !== true) {
                    return $this->sendError(__('app.status.tryagain'), 200);
                }
            }else{
                $response['before'] = $gameCurrent;
                $response['after'] = ($gameCurrent + $amount);
                $response['ref_id'] = '';
            }

            $promotion = DB::table('promotions')->where('id', 'pro_coupon')->first();
            if(!$promotion){
                $pro_code = 0;
                $pro_name = '';
            }else{
                $pro_code = $promotion->code;
                $pro_name = $promotion->name_th;
            }
            if ($bonus->turnpro == 0 && $bonus->amount_limit == 0) {
                $pro_code = 0;
                $pro_name = '';
            }


            $log = $this->memberCreditLogRepository->create([
                'enable' => 'Y',
                'ip' => request()->ip(),
                'credit_type' => 'D',
                'amount' => $amount,
                'bonus' => 0,
                'total' => 0,
                'balance_before' => $response['before'],
                'balance_after' => $response['after'],
                'credit' => $amount,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => 0,
                'credit_after' => 0,
                'member_code' => $member->code,
                'user_name' => $member->user_name,
                'game_code' => $gamelist->code,
                'gameuser_code' => $game_user->code,
                'pro_code' => $pro_code,
                'pro_name' => $pro_name,
                'bank_code' => 0,
                'refer_code' => $bonus->code,
                'refer_table' => 'bonus',
                'auto' => 'N',
                'remark' => "รับโบนัส จากกิจกรรม " . $bonus['name'] . " จำนวน  :" . $bonus['value'],
                'kind' => 'G_BONUS',
                'amount_balance' => $amount_total,
                'withdraw_limit' => 0,
                'withdraw_limit_amount' => $withdraw_limit_amount,
                'user_create' => '',
                'user_update' => ''
            ]);

            $bill = app('Gametech\Payment\Repositories\BillRepository')->create([
                'complete' => 'Y',
                'enable' => 'Y',
                'refer_code' => $log->code,
                'refer_table' => 'members_credit_log',
                'ref_id' => $response['ref_id'],
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'member_code' => $member->code,
                'game_code' => $gamelist->code,
                'gameuser_code' => $game_user->code,
                'pro_code' => $pro_code,
                'pro_name' => $pro_name,
                'remark' => 'รับโบนัส จากกิจกรรม '.$bonus->name,
                'method' => 'BONUS',
                'transfer_type' => 1,
                'amount' => $amount,
                'balance_before' => $response['before'],
                'balance_after' => $response['after'],
                'credit' => 0,
                'credit_bonus' => $amount,
                'credit_balance' => $amount,
                'amount_request' => $amount_total,
                'amount_limit' => $withdraw_limit_amount,
                'ip' => request()->ip(),
                'user_create' => $member['name'],
                'user_update' => $member['name'],
            ]);

            $member->balance += $bonus->value;
            $member->saveQuietly();

            $bonus->status = 'Y';
            $bonus->save();

//            if ($config->seamless == 'Y') {
            $game_user->pro_code = $pro_code;
            $game_user->bill_code = $bill->code;
            $game_user->balance = $response['after'];
            $game_user->amount = 0;
            $game_user->bonus = $bonus->value;
            $game_user->turnpro = $bonus->turnpro;
            $game_user->amount_balance = $amount_total;
            $game_user->withdraw_limit = 0;
            $game_user->withdraw_limit_rate = $bonus->amount_limit;
            $game_user->withdraw_limit_amount = $withdraw_limit_amount;
            $game_user->save();

            return $this->sendSuccess(__('app.coupon.credit_amount') . $amount);

        } else {
            return $this->sendError(__('app.coupon.expire'), 200);
        }

    }

    public function getBonus(Request $request)
    {
        $config  = core()->getConfigData();
        $code    = (string) $request->input('id'); // id = bonus code (ตาม interface เดิม)
        $member  = $this->user();
        $gamelist = core()->getGame();

        // validate พื้นฐาน
        if ($code === '' || !$member || !$gamelist) {
            return $this->sendError(__('app.status.tryagain'), 200);
        }

        try {
            $resultMessage = null;

            DB::transaction(function () use (
                $config, $code, $member, $gamelist, &$resultMessage
            ) {
                // 1) lock แถวโบนัสปัจจุบัน กันเคลมซ้ำ
                $bonusRow = DB::table('bonus')
                    ->where('member_code', $member->code)
                    ->where('code', $code)
                    ->where('status', 'N')
                    ->lockForUpdate()
                    ->first();

                if (!$bonusRow) {
                    // ไม่มีสิทธิ์/ถูกใช้แล้ว/ไม่พบ
                    throw new \RuntimeException(__('app.coupon.expire'));
                }

                // เช็ควันหมดอายุ (ถ้ามี)
                if (!empty($bonusRow->date_expire)) {
                    $nowDate = \Carbon\Carbon::now()->startOfDay();
                    $expDate = \Carbon\Carbon::parse($bonusRow->date_expire)->endOfDay();
                    if ($nowDate->greaterThan($expDate)) {
                        throw new \RuntimeException(__('app.coupon.expire'));
                    }
                }

                // 2) หา game_user ที่ active
                $game_user = $this->gameUserRepository->findOneWhere([
                    'member_code' => $member->code,
                    'game_code'   => $gamelist->code,
                    'enable'      => 'Y',
                ]);

                if (!$game_user) {
                    throw new \RuntimeException(__('app.coupon.nomember'));
                }

                // 3) คำนวนยอดปัจจุบันของเกม ตามโหมดการทำงาน
                //    แล้วค่อยตรวจ rule pro_reset ทีเดียว
                $gameCurrent = 0;

                if ($config->seamless === 'Y') {
                    $gameCurrent = (float) $member->balance;

                } else {
                    if ($config->multigame_open === 'Y') {
                        $gameCurrent = (float) $member->balance;

                    } else { // multigame_open === 'N' => เช็คยอดในเกม
                        $chk = $this->gameUserRepository->checkBalance($gamelist->id, $game_user->user_name);
                        if (($chk['success'] ?? false) !== true) {
                            throw new \RuntimeException(__('app.status.tryagain'));
                        }
                        $gameCurrent = (float) floor($chk['score'] ?? 0);
                    }
                }

                // ต้อง reset ยอดก่อน ถ้ามี turn/limit
                $hasRule = ((float)$bonusRow->turnpro > 0) || ((float)$bonusRow->amount_limit > 0);
                if ($hasRule) {
                    if ($gameCurrent > (float) $config->pro_reset) {
                        throw new \RuntimeException(__('app.coupon.cannot_get') . $config->pro_reset);
                    }
                }
                $hasRule2 = ((float)$game_user->pro_code > 0) || ((float)$game_user->amount_balance > 0) || ((float)$game_user->withdraw_limit_amount > 0);
                if ($hasRule2) {
                    if ($gameCurrent > (float) $config->pro_reset) {
                        throw new \RuntimeException(__('app.coupon.cannot_get') . $config->pro_reset);
                    }
                }

                // 4) ทำการเครดิต (deposit) ถ้าจำเป็น
                $amount         = (float) $bonusRow->value;
                $credit_before  = $gameCurrent;
                $credit_after   = $credit_before + $amount;

                $required_turn_amount  = $amount * (float) $bonusRow->turnpro;      // ยอดเทิร์นที่ต้องทำ
                $withdraw_cap_amount   = $amount * (float) $bonusRow->amount_limit; // อั้นถอนรวม

                $response = [
                    'before' => $credit_before,
                    'after'  => $credit_after,
                    'ref_id' => '',
                ];

                if ($config->seamless === 'N' && $config->multigame_open === 'N') {
                    // ยิงเข้าเกมจริง
                    $res = $this->gameUserRepository->UserDeposit(
                        $gamelist->code,
                        $game_user->user_name,
                        $amount,
                        false
                    );

                    if (($res['success'] ?? false) !== true) {
                        // ให้ rollback transaction ทั้งหมด
                        throw new \RuntimeException(__('app.status.tryagain'));
                    }

                    // ถ้า API เกมคืน before/after มาก็ใช้ต่อได้เลย
                    $response['before'] = $res['before'] ?? $credit_before;
                    $response['after']  = $res['after']  ?? ($response['before'] + $amount);
                    $response['ref_id'] = $res['ref_id'] ?? '';
                }

                // 5) หาโปร (ข้อ 5: คง logic เดิมของคุณไว้)
                $promotion = DB::table('promotions')->where('id', 'pro_coupon')->first();
                if (!$promotion) {
                    $pro_code = 0;
                    $pro_name = $bonusRow->name;
                } else {
                    $pro_code = $promotion->code;
                    $pro_name = $bonusRow->name;
                }
                if ((float)$bonusRow->turnpro == 0 && (float)$bonusRow->amount_limit == 0) {
                    $pro_code = 0;
                    $pro_name = $bonusRow->name;
                }

                // 6) เขียน logs/bill
                $log = $this->memberCreditLogRepository->create([
                    'enable'                 => 'Y',
                    'ip'                     => request()->ip(),
                    'credit_type'            => 'D',
                    'amount'                 => $amount,
                    'bonus'                  => 0,
                    'total'                  => 0,
                    'balance_before'         => $response['before'],
                    'balance_after'          => $response['after'],
                    'credit'                 => $amount,
                    'credit_bonus'           => 0,
                    'credit_total'           => 0,
                    'credit_before'          => 0,
                    'credit_after'           => 0,
                    'member_code'            => $member->code,
                    'user_name'              => $member->user_name,
                    'game_code'              => $gamelist->code,
                    'gameuser_code'          => $game_user->code,
                    'pro_code'               => $pro_code,
                    'pro_name'               => $pro_name,
                    'bank_code'              => 0,
                    'refer_code'             => $bonusRow->code,
                    'refer_table'            => 'bonus',
                    'auto'                   => 'N',
                    'remark'                 => "รับโบนัส จากกิจกรรม {$bonusRow->name} จำนวน :{$bonusRow->value}",
                    'kind'                   => 'G_BONUS',
                    'amount_balance'         => $required_turn_amount,
                    'withdraw_limit'         => 0,
                    'withdraw_limit_amount'  => $withdraw_cap_amount,
                    'user_create'            => '',
                    'user_update'            => '',
                ]);

                $bill = app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete'               => 'Y',
                    'enable'                 => 'Y',
                    'refer_code'             => $log->code,
                    'refer_table'            => 'members_credit_log',
                    'ref_id'                 => $response['ref_id'],
                    'credit_before'          => $response['before'],
                    'credit_after'           => $response['after'],
                    'member_code'            => $member->code,
                    'game_code'              => $gamelist->code,
                    'gameuser_code'          => $game_user->code,
                    'pro_code'               => $pro_code,
                    'pro_name'               => $pro_name,
                    'remark'                 => 'รับโบนัส จากกิจกรรม ' . $bonusRow->name,
                    'method'                 => 'BONUS',
                    'transfer_type'          => 1,
                    'amount'                 => $amount,
                    'balance_before'         => $response['before'],
                    'balance_after'          => $response['after'],
                    'credit'                 => 0,
                    'credit_bonus'           => $amount,
                    'credit_balance'         => $amount,
                    'amount_request'         => $required_turn_amount,
                    'amount_limit'           => $withdraw_cap_amount,
                    'ip'                     => request()->ip(),
                    'user_create'            => $member->name ?? $member['name'] ?? '',
                    'user_update'            => $member->name ?? $member['name'] ?? '',
                ]);

                // 7) อัปเดตยอด member/game_user ภายในทรานแซกชัน
                //    (ถ้า seamless/multigame_open ใช้ balance member เป็นตัวแทน)
                $member->balance = $response['after']; // กัน race: set ตาม after ที่เรายืนยัน
                $member->saveQuietly();

                // game_user fields
                $game_user->pro_code              = $pro_code;
                $game_user->bill_code            = $bill->code;
                $game_user->balance              = $response['after'];
                $game_user->amount               = 0;
                $game_user->bonus                = $bonusRow->value;
                $game_user->turnpro              = $bonusRow->turnpro;
                $game_user->amount_balance       = $required_turn_amount;
                $game_user->withdraw_limit       = 0;
                $game_user->withdraw_limit_rate  = $bonusRow->amount_limit;
                $game_user->withdraw_limit_amount= $withdraw_cap_amount;
                $game_user->save();

                // 8) flip สถานะโบนัสเป็นใช้แล้ว
                DB::table('bonus')
                    ->where('member_code', $member->code)
                    ->where('code', $bonusRow->code)
                    ->where('status', 'N')
                    ->update(['status' => 'Y']);

                $resultMessage = __('app.coupon.credit_amount') . $bonusRow->value;
            }, 3);

            // สำเร็จ
            return $this->sendSuccess($resultMessage ?? __('app.status.ok'));

        } catch (\RuntimeException $e) {
            // ข้อผิดพลาดจากธุรกิจ (เช่น สิทธิ์หมด/ยอดเกิน/ฝากไม่สำเร็จ)
            return $this->sendError($e->getMessage(), 200);

        } catch (\Throwable $e) {
            // อื่น ๆ ที่ไม่คาดคิด
            // คุณอาจจะ log เพิ่มที่นี่ เช่น Log::error('bonus.claim', [...])
            return $this->sendError(__('app.status.tryagain'), 200);
        }
    }



}
