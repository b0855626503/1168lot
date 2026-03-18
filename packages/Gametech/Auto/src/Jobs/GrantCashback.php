<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrantCashback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 2;

    public int $maxExceptions = 3;

    public int $retryAfter = 5;

    public $backoff = [5, 15];

    protected string $date;     // YYYY-MM-DD

    protected string $username; // game username

    protected float $deposit;   // จาก repo

    protected float $withdraw;  // จาก repo

    public function __construct(string $date, string $username, float $deposit, float $withdraw)
    {
        $this->date = $date;
        $this->username = $username;

        $this->deposit = $deposit;
        $this->withdraw = $withdraw;

        // IMPORTANT: ห้าม resolve object/service ที่ไม่ควร serialize ใน constructor
        // จะไป resolve ใน handle() แทน
    }

    public function handle(): void
    {
        $game_check = false;
        $date = $this->date;
        $username = $this->username;

        $deposit = (float) $this->deposit;
        $withdraw = (float) $this->withdraw;

        // โหลดโปรโมชันปัจจุบัน (ดึงสดเพื่อรองรับการปรับค่า)
        $promotion = DB::table('promotions')->where('id', 'pro_cashback')->first();
        if (! $promotion || $promotion->enable !== 'Y' || $promotion->active !== 'Y' || $promotion->use_auto !== 'Y') {
            return;
        }

        $game = core()->getGame();

        $gameUser = app('Gametech\Game\Repositories\GameUserRepository');

        /**
         * เช็กยอดปัจจุบันจากค่ายเกม (best-effort)
         * - ทำ retry 3 ครั้ง เผื่อ provider/network แกว่ง
         * - cache ระยะสั้น (15s) เฉพาะกรณีสำเร็จ เพื่อลดการยิงซ้ำจาก worker ชนกัน
         * - หากเช็กไม่ได้ ให้ fallback ไปใช้ยอด DB ตาม logic เดิม
         */
        $getbalance = null;
        $cacheKey = 'gt:game_balance:' . (string) ($game->id ?? 0) . ':' . $username;

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            $getbalance = $cached;
        } else {
            $maxAttempts = 3;
            $lastErr = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $resp = $gameUser->checkBalance($game->id, $username);

                    // ถ้าสำเร็จให้หยุด และ cache สั้น ๆ
                    if (($resp['success'] ?? false) === true) {
                        $getbalance = $resp;
                        Cache::put($cacheKey, $resp, 15);
                        break;
                    }

                    // ไม่สำเร็จแต่ไม่ throw: เก็บไว้เป็น last response เผื่อ log
                    $getbalance = $resp;
                } catch (\Throwable $e) {
                    $lastErr = $e;
                    Log::channel('cashback')->warning('GrantCashback:checkBalance_exception', [
                        'date' => $date,
                        'username' => $username,
                        'attempt' => $attempt,
                        'message' => $e->getMessage(),
                    ]);
                }

                // หน่วงนิดหนึ่งก่อน retry (กัน rate limit / network jitter)
                if ($attempt < $maxAttempts) {
                    usleep(300000); // 300ms
                }
            }

            // ถ้าทั้งหมดล้มเหลว ให้ log อีกครั้งแบบสรุป (แต่ไม่ทำให้ job fail)
            if (($getbalance['success'] ?? false) !== true) {
                Log::channel('cashback')->warning('GrantCashback:checkBalance_failed_fallback_db', [
                    'date' => $date,
                    'username' => $username,
                    'attempts' => $maxAttempts,
                    'last_exception' => $lastErr ? $lastErr->getMessage() : null,
                    'last_response_success' => $getbalance['success'] ?? null,
                ]);
            }
        }

        $user = $gameUser->findOneByField('user_name', $username);
        if (! $user) {
            return;
        }

        if (($getbalance['success'] ?? false) === false) {
            $balance = (float) $user->balance;
        } else {
            $balance = (float) ($getbalance['score'] ?? 0);
            $game_check = true;
        }

        /**
         * ยึด logic เดิมของคุณ (ห้ามเปลี่ยน):
         * winlose = deposit - withdraw - balance
         * ตัวอย่าง: 500 - 100 - 100 = 300  => เอา 300 ไปคำนวณ cashback
         */
        $winlose = ($deposit - $withdraw - $balance);

        // ถ้า <= 0 ถือว่า "ไม่ได้ cashback"
        if ($winlose <= 0) {
            return;
        }

        $pro_code = $promotion->code;
        $turnpro = (float) $promotion->turnpro;
        $withdraw_limit = (float) $promotion->withdraw_limit;
        $withdraw_limit_rate = (float) $promotion->withdraw_limit_rate;

        $bonusPct = (float) $promotion->bonus_percent;
        $bonusMin = (float) $promotion->bonus_min;
        $bonusMax = (float) $promotion->bonus_max;

        // คำนวณ cashback จากยอด winlose (ค่าบวก) ตามระบบคุณ
        $cashback = 0.0;

        if ($bonusPct > 0) {
            $cashback = ($winlose * $bonusPct) / 100.0;

            // ตามโค้ดเดิม: ถ้าต่ำกว่า min ให้ 0
            if ($bonusMin > 0 && $cashback < $bonusMin) {
                $cashback = 0.0;
            }

            if ($bonusMax > 0 && $cashback > $bonusMax) {
                $cashback = $bonusMax;
            }
        }

        // ถ้าคำนวณได้ 0 = ไม่ได้ cashback
        if ($cashback <= 0) {
            return;
        }

        /**
         * topupic:
         * N = มอบ (รอรับ) จะถูกปรับเป็น Y เมื่อรับแล้ว
         * X = ไม่ได้ cashback
         */
        $topup = 'N';

        // หา GameUser + Member
        $memberRepo = app('Gametech\Member\Repositories\MemberRepository');
        $eventRepo = app('Gametech\Game\Repositories\GameUserEventRepository');
        $cashbackRepo = app('Gametech\Member\Repositories\MemberCashbackRepository');
        $creditLogRepo = app('Gametech\Member\Repositories\MemberCreditLogRepository');

        $member = $memberRepo->findOneByField('code', $user->member_code);
        if (! $member) {
            return;
        }

        // idempotency: กันซ้ำด้วย unique(date_cashback, game_user)
        $exists = DB::table('members_cashback')
            ->whereDate('date_cashback', $date)
            ->where('game_user', $username)
            ->exists();

        if ($exists) {
            return;
        }

        $turn = 0;

        try {
            DB::transaction(function () use (
                $date, $username, $cashback, $topup, $turn, $winlose, $pro_code,
                $turnpro, $withdraw_limit, $withdraw_limit_rate, $deposit, $withdraw, $balance,
                $member, $user, $eventRepo, $cashbackRepo, $creditLogRepo, $bonusPct, $game_check
            ) {
                // เตรียม/หา GameUserEvent
                $game_user_event = $eventRepo->findOneWhere([
                    'method' => 'CASHBACK',
                    'member_code' => $member->code,
                    'game_code' => 1,
                    'enable' => 'Y',
                ]);

                if (! $game_user_event) {
                    $game_user_event = $eventRepo->create([
                        'game_code' => 1,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'method' => 'CASHBACK',
                        'user_name' => $member->user_name,
                        'amount' => 0,
                        'bonus' => 0,
                        'turnpro' => 0,
                        'amount_balance' => 0,
                        'withdraw_limit' => 0,
                        'withdraw_limit_rate' => 0,
                        'withdraw_limit_amount' => 0,
                    ]);
                }

                // Insert แถว cashback (จะชน unique ถ้าซ้ำ)
                $bill = $cashbackRepo->create([
                    'date_cashback' => $date,
                    'member_code' => $member->code,
                    'game_user' => $username,
                    'cashback' => $cashback,
                    'topupic' => $topup, // N = มอบรอรับ
                    'ip_admin' => 'SYSTEM',
                    'turnpro' => $turn,
                    'winlose' => $winlose,
                    'startdate' => $date,
                    'enddate' => $date,
                    'sum_deposit' => $deposit,
                    'sum_withdraw' => $withdraw,
                    'sum_balance' => $balance,
                    'emp_code' => 0,
                    'user_create' => 'SYSTEM',
                    'user_update' => 'SYSTEM',
                ]);

                // อัปเดต field แสดงใน member (ตามโค้ดเดิม)
                $member->cashback += $cashback;
                $member->save();

                // อัปเดต event
                $game_user_event->amount = $member->balance;
                $game_user_event->pro_code = $pro_code;
                $game_user_event->bill_code = $bill->code;
                $game_user_event->turnpro = $turnpro;
                $game_user_event->bonus += $cashback;
                $game_user_event->amount_balance += ($cashback * $turnpro);
                $game_user_event->withdraw_limit += $withdraw_limit;
                $game_user_event->withdraw_limit_rate = $withdraw_limit_rate;
                $game_user_event->withdraw_limit_amount += ($cashback * $withdraw_limit_rate);
                $game_user_event->save();

                // บันทึกเครดิตล็อก
                $creditLogRepo->create([
                    'ip' => 'SYSTEM',
                    'credit_type' => 'D',
                    'game_code' => 1,
                    'gameuser_code' => $user->code,
                    'amount' => $cashback,
                    'bonus' => 0,
                    'total' => $cashback,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => 0,
                    'credit_after' => 0,
                    'member_code' => $member->code,
                    'pro_code' => $pro_code,
                    'refer_code' => $bill->code,
                    'refer_table' => 'members_cashback',
                    'auto' => 'Y',
                    'remark' => 'ได้รับยอด Cashback จากการคำนวนรอบ '.$date
                        .' [ Winlose = '.$winlose
                        .' , Deposit Sum = '.$deposit
                        .' , Wirhdraw Sum = '.$withdraw
                        .' , Balance '.($game_check ? '(Game)' : 'DB').' = '.$balance
                        .' , Cashback Rate = '.$bonusPct
                        .' % , Bonus-Min/Max ใช้ตามโปรโมชัน , สรุปยอดที่นำมาคิด = '.$winlose
                        .' , Cashback = '.$cashback
                        .' (เข้ากระเป๋าโบนัส รอลูกค้ากดรับ) ]',
                    'kind' => 'CASHBACK',
                    'amount_balance' => $game_user_event->amount_balance,
                    'withdraw_limit' => $game_user_event->withdraw_limit,
                    'withdraw_limit_amount' => $game_user_event->withdraw_limit_amount,
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);
            });
        } catch (QueryException $e) {
            // ถ้าชน unique (worker ชนกัน) ให้ถือว่าทำไปแล้วและข้าม
            Log::channel('cashback')->warning('GrantCashback:duplicate_or_query_error', [
                'date' => $date,
                'username' => $username,
                'message' => $e->getMessage(),
            ]);
            return;
        }

        Log::channel('cashback')->info('GrantCashback:done', [
            'date' => $date,
            'username' => $username,
            'cashback' => $cashback,
        ]);
    }
}
