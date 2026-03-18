<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrantIC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 2;
    public int $maxExceptions = 3;
    public int $retryAfter = 5;
    public $backoff = [5, 15];

    protected string $date;     // YYYY-MM-DD
    protected string $username; // game username
    protected float $winlose;   // จาก repo
    protected float $turn;      // จาก repo

    public function __construct(string $date, string $username, float $winlose, float $turn)
    {
        $this->date     = $date;
        $this->username = $username;
        $this->winlose  = $winlose;
        $this->turn     = $turn;
    }

    public function handle(): void
    {
        $date     = $this->date;
        $username = $this->username;
        $winlose  = (float) $this->winlose; // แพ้เป็นค่าติดลบ
        $turn     = (float) $this->turn;

        // โหลดโปรโมชันปัจจุบัน (ดึงสดเพื่อรองรับการปรับค่า)
        $promotion = DB::table('promotions')->where('id', 'pro_ic')->first();
        if (!$promotion || $promotion->enable !== 'Y' || $promotion->active !== 'Y' || $promotion->use_auto !== 'Y') {
            // ปิดโปรโมชัน/ไม่พร้อมทำงาน → ข้าม
            return;
        }

        $pro_code            = $promotion->code;
        $turnpro             = (float) $promotion->turnpro;
        $withdraw_limit      = (float) $promotion->withdraw_limit;
        $withdraw_limit_rate = (float) $promotion->withdraw_limit_rate;

        $bonusPct  = (float) $promotion->bonus_percent;
        $bonusMin  = (float) $promotion->bonus_min;
        $bonusMax  = (float) $promotion->bonus_max;

        // topup flag (X=ชนะ/ไม่ติดลบ, N=แพ้/ติดลบ ตามเดิมของคุณ)
        $topup = $winlose > 0 ? 'X' : 'N';

        // คำนวณ cashback
        $cashback = 0.0;
        if ($winlose < 0) {
            $loss = abs($winlose);
            $cashback = ($loss * $bonusPct) / 100.0;
            if ($bonusMin > 0 && $cashback < $bonusMin) {
                $cashback = 0.0; // ตามโค้ดเดิม: ถ้าต่ำกว่า min ให้ 0
            }
            if ($bonusMax > 0 && $cashback > $bonusMax) {
                $cashback = $bonusMax;
            }
        }

        // หา GameUser + Member
        $gameUserRepo  = app('Gametech\Game\Repositories\GameUserRepository');
        $memberRepo    = app('Gametech\Member\Repositories\MemberRepository');
        $eventRepo     = app('Gametech\Game\Repositories\GameUserEventRepository');
        $cashbackRepo  = app('Gametech\Member\Repositories\MemberIcRepository');
        $creditLogRepo = app('Gametech\Member\Repositories\MemberCreditLogRepository');

        $user = $gameUserRepo->findOneByField('user_name', $username);
        if (!$user) {
            // ไม่มีเกมยูสนี้ → ข้าม
            return;
        }

        $member = $memberRepo->findOneByField('code', $user->member_code);
        if (!$member && $member->upline_code > 0) {
            // ไม่มีสมาชิก → ข้าม
            return;
        }

        $upline = $memberRepo->findOneByField('code', $member->upline_code);
        if (!$upline) {
            return;
        }

        $upline_user = $gameUserRepo->findOneByField('member_code', $upline->code);
        if (!$upline_user) {
            // ไม่มีเกมยูสนี้ → ข้าม
            return;
        }

        // idempotency: กันซ้ำด้วย unique(date_cashback, game_user)
        $exists = DB::table('members_ic')
            ->whereDate('date_cashback', $date)
            ->where('member_code', $upline->code)
            ->where('downline', $member->code)
            ->exists();

        if ($exists) {
            // ทำไปแล้ว
            return;
        }

        DB::transaction(function () use (
            $date, $username, $cashback, $topup, $turn, $winlose, $pro_code,
            $turnpro, $withdraw_limit, $withdraw_limit_rate,
            $member, $user, $eventRepo, $cashbackRepo, $creditLogRepo,$bonusPct,$upline,$upline_user
        ) {
            // เตรียม/หา GameUserEvent
            $game_user_event = $eventRepo->findOneWhere([
                'method'      => 'IC',
                'member_code' => $upline->code,
                'game_code'   => 1,
                'enable'      => 'Y',
            ]);

            if (!$game_user_event) {
                $game_user_event = $eventRepo->create([
                    'game_code'            => 1,
                    'member_code'          => $upline->code,
                    'pro_code'             => $pro_code,
                    'method'               => 'IC',
                    'user_name'            => $upline->user_name,
                    'amount'               => 0,
                    'bonus'                => 0,
                    'turnpro'              => 0,
                    'amount_balance'       => 0,
                    'withdraw_limit'       => 0,
                    'withdraw_limit_rate'  => 0,
                    'withdraw_limit_amount'=> 0,
                ]);
            }

            // Insert แถว cashback (จะชน unique ถ้าซ้ำ)
            $bill = $cashbackRepo->create([
                'date_cashback' => $date,
                'member_code'   => $upline->code,
                'downline'       => $member->code,
                'game_user'     => $upline->game_user,
                'cashback'      => $cashback,
                'topupic'       => $topup,
                'ip_admin'      => 'SYSTEM',
                'turnpro'       => $turn,
                'winlose'       => $winlose,
                'startdate'     => $date,
                'enddate'       => $date,
                'emp_code'      => 0,
                'user_create'   => 'SYSTEM',
                'user_update'   => 'SYSTEM',
            ]);

            // อัปเดต field แสดงใน member (ตามโค้ดเดิม)
            $upline->ic = $cashback;
            $upline->save();

            // อัปเดต event
            $game_user_event->amount                 = $upline->balance;
            $game_user_event->pro_code               = $pro_code;
            $game_user_event->bill_code              = $bill->code;
            $game_user_event->turnpro                = $turnpro;
            $game_user_event->bonus                 += $cashback;
            $game_user_event->amount_balance        += ($cashback * $turnpro);
            $game_user_event->withdraw_limit        += $withdraw_limit;
            $game_user_event->withdraw_limit_rate    = $withdraw_limit_rate;
            $game_user_event->withdraw_limit_amount += ($cashback * $withdraw_limit_rate);
            $game_user_event->save();

            // บันทึกเครดิตล็อก
            $creditLogRepo->create([
                'ip'                   => 'SYSTEM',
                'credit_type'          => 'D',
                'game_code'            => 1,
                'gameuser_code'        => $upline_user->code,
                'amount'               => $cashback,
                'bonus'                => 0,
                'total'                => $cashback,
                'balance_before'       => 0,
                'balance_after'        => 0,
                'credit'               => 0,
                'credit_bonus'         => 0,
                'credit_total'         => 0,
                'credit_before'        => 0,
                'credit_after'         => 0,
                'member_code'          => $upline->code,
                'pro_code'             => $pro_code,
                'refer_code'           => $bill->code,
                'refer_table'          => 'members_ic',
                'auto'                 => 'Y',
                'remark'               => 'ได้รับยอด IC จากการคำนวนรอบจากลูกทีม ไอดี '.$member->user_name.'[ '.$username.' ]' . $date
                    . ' [ Winlose = ' . $winlose
                    . ' , Cashback Rate = ' . $bonusPct
                        . ' % , Bonus-Min/Max ใช้ตามโปรโมชัน , สรุปได้ยอดเสีย = ' . $cashback
                        . ' (เข้ากระเป๋าโบนัส รอลูกค้ากดรับ) ]',
                'kind'                 => 'IC',
                'amount_balance'       => $game_user_event->amount_balance,
                'withdraw_limit'       => $game_user_event->withdraw_limit,
                'withdraw_limit_amount'=> $game_user_event->withdraw_limit_amount,
                'user_create'          => 'System Auto',
                'user_update'          => 'System Auto',
            ]);
        });

        Log::channel('cashback')->info('GrantIC:done', [
            'date'     => $date,
            'username' => $username,
            'cashback' => $cashback,
        ]);
    }
}
