<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScanCashback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public int $maxExceptions = 3;

    public int $retryAfter = 5;

    protected string $date; // YYYY-MM-DD

    public function __construct(string $date)
    {
        $this->date = $date;
    }

    public function handle(): void
    {
        $date = $this->date;

        $param = [
            'startDate' => $date,
            'endDate' => $date,
        ];

        Log::channel('cashback')->info('ScanCashback:start', [
            'date' => $date,
            'param' => $param,
        ]);

        $latestWD = DB::table('withdraws')
            ->select(
                'withdraws.member_code',
                DB::raw('SUM(withdraws.amount) as withdraw_amount'),
                DB::raw("DATE_FORMAT(withdraws.date_approve,'%Y-%m-%d') as date_approve")
            )
            ->where('withdraws.enable', 'Y')
            ->where('withdraws.status', 1)
            ->whereDate('withdraws.date_approve', $date)
            ->groupBy('withdraws.member_code', DB::raw('Date(withdraws.date_approve)'));

        $latestBP = DB::table('bank_payment')
            ->select(
                DB::raw('MAX(bank_payment.code) as code'),
                DB::raw('MAX(bank_payment.date_approve) as date_approve'),
                DB::raw('SUM(bank_payment.value) as deposit_amount'),
                DB::raw("DATE_FORMAT(bank_payment.date_approve,'%Y-%m-%d') as date_cashback"),
                'bank_payment.member_topup'
            )
            ->where('bank_payment.value', '>', 0)
            ->where('bank_payment.bankstatus', 1)
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.pro_id', 0)
            ->where('bank_payment.status', 1)
            ->whereDate('bank_payment.date_approve', $date)
            ->groupBy('bank_payment.member_topup', DB::raw('Date(bank_payment.date_approve)'));

        $result = DB::table('members')
            ->select(
                'members.upline_code',
                'members.code as member_code',
                'members.user_name',
                'members.name as member_name',
                'members.balance',
                DB::raw('IFNULL(withdraw_amount,0) as withdraw_amount'),
                'bank_payment.deposit_amount',
                'bank_payment.date_cashback',
                'bank_payment.date_approve',
                'bank_payment.code as bank_payment_code',
                'members.game_user'
            )
            ->orderByDesc('bank_payment.code')
            ->joinSub($latestBP, 'bank_payment', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'members.code');
            })
            ->leftJoinSub($latestWD, 'withdraws', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'withdraws.member_code');
                $join->on(
                    DB::raw('Date(bank_payment.date_approve)'),
                    '=',
                    'withdraws.date_approve'
                );
            })
            ->get();

        Log::channel('cashback')->info('ScanCashback:list_summary', [
            'date' => $date,
            'count' => $result->count(),
        ]);

        if ($result->isEmpty()) {
            Log::channel('cashback')->warning('ScanCashback:empty_result', [
                'date' => $date,
            ]);

            return;
        }

        foreach ($result as $item) {
            $username = $item->game_user ?? null;

            $deposit = (float) ($item->deposit_amount ?? 0);
            $withdraw = (float) ($item->withdraw_amount ?? 0);

            // log ต่อ record (ก่อน dispatch)
            Log::channel('cashback')->info('ScanCashback:record', [
                'date' => $date,
                'member_code' => $item->member_code,
                'username' => $item->user_name,
                'game_user' => $username,
                'deposit' => $deposit,
                'withdraw' => $withdraw,
                'balance_snapshot' => (float) $item->balance,
                'bank_payment_code' => $item->bank_payment_code,
            ]);

            if (! $username) {
                Log::channel('cashback')->warning('ScanCashback:skip_no_game_user', [
                    'date' => $date,
                    'member_code' => $item->member_code,
                    'username' => $item->user_name,
                ]);

                continue;
            }

            GrantCashback::dispatch($date, $username, $deposit, $withdraw)->onQueue('default');
        }

        Log::channel('cashback')->info('ScanCashback:done', [
            'date' => $date,
        ]);
    }
}
