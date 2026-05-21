<?php

namespace Gametech\FrontendApi\Services;

use Gametech\Core\Core;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberCreditFreeLogRepository;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BillRepository;
use Gametech\Payment\Repositories\BonusRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use RuntimeException;

class CouponService
{
    public function __construct(
        private BonusRepository $bonusRepository,
        private BankPaymentRepository $bankPaymentRepository,
        private MemberCreditLogRepository $memberCreditLogRepository,
        private MemberCreditFreeLogRepository $memberCreditFreeLogRepository,
        private GameUserRepository $gameUserRepository,
        private BillRepository $billRepository,
        private Core $core
    ) {}

    /**
     * @param  object  $member
     * @return array<string,mixed>
     */
    public function redeemCode($member, string $couponCode, string $ipAddress, string $locale = 'th'): array
    {
        $code = trim($couponCode);
        if ($code === '') {
            throw new RuntimeException(Lang::get('app.coupon.empty', [], $locale));
        }

        $datenow = now()->toDateString();
        $datetime = now()->toDateTimeString();

        return DB::transaction(function () use ($member, $code, $datenow, $datetime, $ipAddress, $locale): array {
            $coupon = DB::table('coupons_list')
                ->whereDate('date_start', '<=', $datenow)
                ->whereDate('date_stop', '>=', $datenow)
                ->where('status', 'N')
                ->where('enable', 'Y')
                ->where('name', $code)
                ->lockForUpdate()
                ->first();

            if (! $coupon) {
                throw new RuntimeException(Lang::get('app.coupon.empty', [], $locale));
            }

            $main = DB::table('coupons')->where('code', $coupon->coupon_code)->first();
            if (! $main) {
                throw new RuntimeException(Lang::get('app.coupon.fail', [], $locale));
            }

            if ((string) ($main->enable ?? 'N') === 'N') {
                throw new RuntimeException(Lang::get('app.coupon.cannot', [], $locale));
            }

            $couponChk = DB::table('coupons_list')
                ->where('member_code', $member->code)
                ->where('coupon_code', $main->code)
                ->where('enable', 'Y')
                ->first();

            if ($couponChk) {
                throw new RuntimeException(Lang::get('app.coupon.cannot_rejoin', [], $locale));
            }

            if ((string) ($coupon->newuser ?? 'N') === 'Y' && (int) ($member->status_pro ?? 0) === 1) {
                throw new RuntimeException(Lang::get('app.coupon.condition', [], $locale));
            }

            if ((string) ($coupon->norefill ?? 'N') === 'Y') {
                $payment = $this->bankPaymentRepository
                    ->where('member_topup', $member->code)
                    ->where('status', 1)
                    ->where('enable', 'Y')
                    ->where('bankstatus', 1)
                    ->sum('value');

                if ((float) $payment > 0) {
                    throw new RuntimeException(Lang::get('app.coupon.condition', [], $locale));
                }
            } elseif ((float) ($coupon->money ?? 0) > 0) {
                $paymentQuery = $this->bankPaymentRepository
                    ->where('member_topup', $member->code)
                    ->where('status', 1)
                    ->where('enable', 'Y')
                    ->where('bankstatus', 1);

                if (! is_null($main->refill_start ?? null) && ! is_null($main->refill_stop ?? null)) {
                    $paymentQuery = $paymentQuery->whereBetween('date_approve', [$main->refill_start, $main->refill_stop]);
                }

                if ((float) $paymentQuery->sum('value') < (float) $coupon->money) {
                    throw new RuntimeException(Lang::get('app.coupon.condition', [], $locale));
                }
            }

            $expire = ((int) ($coupon->date_expire ?? 0) === 0)
                ? null
                : now()->addDays((int) $coupon->date_expire);

            $bonus = $this->bonusRepository->create([
                'refer_coupon' => $coupon->code,
                'name' => $main->name,
                'cashback' => $coupon->cashback,
                'member_code' => $member->code,
                'value' => $coupon->value,
                'turnpro' => $coupon->turnpro,
                'amount_limit' => $coupon->amount_limit,
                'date_expire' => $expire,
                'status' => 'N',
                'user_create' => 'SYSTEM',
                'user_update' => 'SYSTEM',
            ]);

            if ((string) $coupon->cashback === 'Y') {
                $this->memberCreditFreeLogRepository->create([
                    'enable' => 'Y',
                    'ip' => $ipAddress,
                    'credit_type' => 'D',
                    'amount' => $coupon->value,
                    'bonus' => 0,
                    'total' => 0,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => $coupon->value,
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
                    'refer_code' => $bonus->code,
                    'refer_table' => 'bonus',
                    'auto' => 'N',
                    'remark' => 'ได้รับเครดิตโบนัส (ฟรี) จากคูปอง '.$coupon->name.' จำนวน :'.$coupon->value,
                    'kind' => 'BONUS',
                    'user_create' => '',
                    'user_update' => '',
                ]);
            } else {
                $this->memberCreditLogRepository->create([
                    'enable' => 'Y',
                    'ip' => $ipAddress,
                    'credit_type' => 'D',
                    'amount' => $coupon->value,
                    'bonus' => 0,
                    'total' => 0,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => $coupon->value,
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
                    'refer_code' => $bonus->code,
                    'refer_table' => 'bonus',
                    'auto' => 'N',
                    'remark' => 'ได้รับเครดิตโบนัส จากคูปอง '.$coupon->name.' จำนวน :'.$coupon->value,
                    'kind' => 'BONUS',
                    'user_create' => '',
                    'user_update' => '',
                ]);
            }

            DB::table('coupons_list')
                ->where('code', $coupon->code)
                ->update([
                    'status' => 'Y',
                    'member_code' => $member->code,
                    'date_update' => $datetime,
                ]);

            return $this->mapPendingBonusItem((object) [
                'code' => $bonus->code,
                'name' => $main->name,
                'cashback' => $coupon->cashback,
                'value' => $coupon->value,
                'turnpro' => $coupon->turnpro,
                'amount_limit' => $coupon->amount_limit,
                'rate' => '',
                'date_expire' => $expire,
            ]);
        }, 3);
    }

    /**
     * @param  object  $member
     * @return array<int,array<string,mixed>>
     */
    public function listPendingBonuses($member): array
    {
        $datenow = now()->toDateString();
        $datas = $this->bonusRepository->findWhere([
            'member_code' => $member->code,
            'status' => 'N',
        ]);

        $bonuses = collect();

        if ($datas && $datas->count()) {
            foreach ($datas as $item) {
                if (! is_null($item->date_expire) && $datenow >= $item->date_expire) {
                    continue;
                }

                $bonuses->push($this->mapPendingBonusItem($item));
            }
        }

        return $bonuses->values()->all();
    }

    /**
     * @param  object  $member
     * @return array<string,mixed>
     */
    public function claimBonus($member, string $bonusCode, string $ipAddress, string $locale = 'th'): array
    {
        $config = $this->core->getConfigData();
        $gamelist = $this->core->getGame();
        $code = trim($bonusCode);

        if ($code === '' || ! $gamelist) {
            throw new RuntimeException(Lang::get('app.status.tryagain', [], $locale));
        }

        return DB::transaction(function () use ($config, $gamelist, $member, $code, $ipAddress, $locale): array {
            $bonusRow = DB::table('bonus')
                ->where('member_code', $member->code)
                ->where('code', $code)
                ->where('status', 'N')
                ->lockForUpdate()
                ->first();

            if (! $bonusRow) {
                throw new RuntimeException(Lang::get('app.coupon.expire', [], $locale));
            }

            if (! empty($bonusRow->date_expire)) {
                $nowDate = Carbon::now()->startOfDay();
                $expDate = Carbon::parse($bonusRow->date_expire)->endOfDay();
                if ($nowDate->greaterThan($expDate)) {
                    throw new RuntimeException(Lang::get('app.coupon.expire', [], $locale));
                }
            }

            $gameUser = $this->gameUserRepository->findOneWhere([
                'member_code' => $member->code,
                'game_code' => $gamelist->code,
                'enable' => 'Y',
            ]);

            if (! $gameUser) {
                throw new RuntimeException(Lang::get('app.coupon.nomember', [], $locale));
            }

            if ((string) ($config->seamless ?? 'N') === 'Y' || (string) ($config->multigame_open ?? 'N') === 'Y') {
                $gameCurrent = (float) ($member->balance ?? 0);
            } else {
                $chk = $this->gameUserRepository->checkBalance($gamelist->id, $gameUser->user_name);
                if (($chk['success'] ?? false) !== true) {
                    throw new RuntimeException(Lang::get('app.status.tryagain', [], $locale));
                }
                $gameCurrent = (float) floor($chk['score'] ?? 0);
            }

            $hasRule = ((float) $bonusRow->turnpro > 0) || ((float) $bonusRow->amount_limit > 0);
            if ($hasRule && $gameCurrent > (float) ($config->pro_reset ?? 0)) {
                throw new RuntimeException(Lang::get('app.coupon.cannot_get', [], $locale).($config->pro_reset ?? 0));
            }

            $hasExistingRule = ((float) ($gameUser->pro_code ?? 0) > 0)
                || ((float) ($gameUser->amount_balance ?? 0) > 0)
                || ((float) ($gameUser->withdraw_limit_amount ?? 0) > 0);

            if ($hasExistingRule && $gameCurrent > (float) ($config->pro_reset ?? 0)) {
                throw new RuntimeException(Lang::get('app.coupon.cannot_get', [], $locale).($config->pro_reset ?? 0));
            }

            $amount = (float) $bonusRow->value;
            $requiredTurnAmount = $amount * (float) $bonusRow->turnpro;
            $withdrawCapAmount = $amount * (float) $bonusRow->amount_limit;

            $response = [
                'before' => $gameCurrent,
                'after' => $gameCurrent + $amount,
                'ref_id' => '',
            ];

            if ((string) ($config->seamless ?? 'N') === 'N' && (string) ($config->multigame_open ?? 'N') === 'N') {
                $deposit = $this->gameUserRepository->UserDeposit(
                    $gamelist->code,
                    $gameUser->user_name,
                    $amount,
                    false
                );

                if (($deposit['success'] ?? false) !== true) {
                    throw new RuntimeException(Lang::get('app.status.tryagain', [], $locale));
                }

                $response['before'] = $deposit['before'] ?? $response['before'];
                $response['after'] = $deposit['after'] ?? ($response['before'] + $amount);
                $response['ref_id'] = $deposit['ref_id'] ?? '';
            }

            $promotion = DB::table('promotions')->where('id', 'pro_coupon')->first();
            $proCode = $promotion->code ?? 0;
            $proName = $bonusRow->name;
            if ((float) $bonusRow->turnpro === 0.0 && (float) $bonusRow->amount_limit === 0.0) {
                $proCode = 0;
            }

            $log = $this->memberCreditLogRepository->create([
                'enable' => 'Y',
                'ip' => $ipAddress,
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
                'gameuser_code' => $gameUser->code,
                'pro_code' => $proCode,
                'pro_name' => $proName,
                'bank_code' => 0,
                'refer_code' => $bonusRow->code,
                'refer_table' => 'bonus',
                'auto' => 'N',
                'remark' => 'รับโบนัส จากกิจกรรม '.$bonusRow->name.' จำนวน :'.$bonusRow->value,
                'kind' => 'G_BONUS',
                'amount_balance' => $requiredTurnAmount,
                'withdraw_limit' => 0,
                'withdraw_limit_amount' => $withdrawCapAmount,
                'user_create' => '',
                'user_update' => '',
            ]);

            $bill = $this->billRepository->create([
                'complete' => 'Y',
                'enable' => 'Y',
                'refer_code' => $log->code,
                'refer_table' => 'members_credit_log',
                'ref_id' => $response['ref_id'],
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'member_code' => $member->code,
                'game_code' => $gamelist->code,
                'gameuser_code' => $gameUser->code,
                'pro_code' => $proCode,
                'pro_name' => $proName,
                'remark' => 'รับโบนัส จากกิจกรรม '.$bonusRow->name,
                'method' => 'BONUS',
                'transfer_type' => 1,
                'amount' => $amount,
                'balance_before' => $response['before'],
                'balance_after' => $response['after'],
                'credit' => 0,
                'credit_bonus' => $amount,
                'credit_balance' => $amount,
                'amount_request' => $requiredTurnAmount,
                'amount_limit' => $withdrawCapAmount,
                'ip' => $ipAddress,
                'user_create' => $member->name ?? '',
                'user_update' => $member->name ?? '',
            ]);

            $createdAt = now();
            DB::table('wallet_transactions')->insert([
                'member_id' => (int) $member->code,
                'scope' => 'MEMBER',
                'game_user_id' => $gameUser->code ?? null,
                'direction' => 'CREDIT',
                'amount' => $amount,
                'balance_before' => $response['before'],
                'balance_after' => $response['after'],
                'ref_type' => 'COUPON_CLAIM',
                'ref_id' => null,
                'ref_code' => $bonusRow->code,
                'group_code' => null,
                'related_txn_id' => null,
                'status' => 'SUCCESS',
                'description' => 'รับโบนัส จากกิจกรรม '.$bonusRow->name,
                'meta' => json_encode([
                    'bonus_code' => $bonusRow->code,
                    'bonus_name' => $bonusRow->name,
                    'pro_code' => $proCode,
                ], JSON_UNESCAPED_UNICODE),
                'created_by_type' => 'member',
                'created_by_id' => (int) $member->code,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $member->balance = $response['after'];
            $member->saveQuietly();

            $gameUser->pro_code = $proCode;
            $gameUser->bill_code = $bill->code;
            $gameUser->balance = $response['after'];
            $gameUser->amount = 0;
            $gameUser->bonus = $bonusRow->value;
            $gameUser->turnpro = $bonusRow->turnpro;
            $gameUser->amount_balance = $requiredTurnAmount;
            $gameUser->withdraw_limit = 0;
            $gameUser->withdraw_limit_rate = $bonusRow->amount_limit;
            $gameUser->withdraw_limit_amount = $withdrawCapAmount;
            $gameUser->save();

            DB::table('bonus')
                ->where('member_code', $member->code)
                ->where('code', $bonusRow->code)
                ->where('status', 'N')
                ->update(['status' => 'Y']);

            return [
                'code' => (string) $bonusRow->code,
                'name' => (string) $bonusRow->name,
                'status' => 'claimed',
                'status_label' => 'รับโบนัสแล้ว',
                'type' => ((string) $bonusRow->cashback === 'Y') ? 'freecredit' : 'credit',
                'type_label' => ((string) $bonusRow->cashback === 'Y') ? 'เครดิตฟรี' : 'เครดิต',
                'amount' => $amount,
                'turnpro' => (float) $bonusRow->turnpro,
                'amount_limit' => (float) $bonusRow->amount_limit,
                'balance_after' => (float) $response['after'],
            ];
        }, 3);
    }

    /**
     * @param  object  $item
     * @return array<string,mixed>
     */
    private function mapPendingBonusItem($item): array
    {
        $isFreeCredit = (string) ($item->cashback ?? 'N') === 'Y';

        return [
            'code' => (string) ($item->code ?? ''),
            'name' => (string) ($item->name ?? ''),
            'status' => 'pending_claim',
            'status_label' => 'รอรับโบนัส',
            'type' => $isFreeCredit ? 'freecredit' : 'credit',
            'type_label' => $isFreeCredit ? 'เครดิตฟรี' : 'เครดิต',
            'value' => (float) ($item->value ?? 0),
            'turnpro' => (float) ($item->turnpro ?? 0),
            'amount_limit' => (float) ($item->amount_limit ?? 0),
            'rate' => $item->rate ?? '',
            'date_expire' => $this->normalizeDateTime($item->date_expire ?? null),
            'can_claim' => true,
        ];
    }

    /**
     * @param  mixed  $value
     */
    private function normalizeDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
    }
}
