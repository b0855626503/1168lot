<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashbackCalculate extends Command
{
    protected $signature = 'cashback:start
                            {--mode=range : Calculation mode: range or daily}
                            {--date= : Anchor date for range mode or business date for daily mode (Y-m-d)}
                            {--target=wallet : Credit target: wallet or cashback}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Calculating Cashback for members';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $mode = $this->normalizeMode((string) $this->option('mode'));
        $target = $this->normalizeTarget((string) $this->option('target'));
        $anchorDate = $this->resolveAnchorDate($mode, $this->option('date'));

        if ($mode === null) {
            $this->error('โหมดไม่ถูกต้อง กรุณาใช้ --mode=range หรือ --mode=daily');

            return self::FAILURE;
        }

        if ($target === null) {
            $this->error('ปลายทางไม่ถูกต้อง กรุณาใช้ --target=wallet หรือ --target=cashback');

            return self::FAILURE;
        }

        if ($anchorDate === null) {
            $this->error('รูปแบบวันที่ไม่ถูกต้อง กรุณาใช้รูปแบบ Y-m-d');

            return self::FAILURE;
        }

        [$startDate, $endDate, $cashbackDate, $roundLabel] = $this->resolvePeriod($mode, $anchorDate);

        $this->info($roundLabel.' | ปลายทางเครดิต: '.$target);
        $promotion = DB::table('promotions')->where('id', 'pro_cashback')->first();

        if (! $promotion) {
            $this->error('ไม่พบ promotion pro_cashback');

            return self::FAILURE;
        }

        if ($promotion->enable != 'Y' || $promotion->active != 'Y' || $promotion->use_auto != 'Y') {
            $this->warn('promotion cashback ยังไม่พร้อมสำหรับ auto calculate');

            return self::SUCCESS;
        }

        $latestBi = DB::table('bills')
            ->select('bills.member_code', DB::raw('SUM(bills.credit_bonus)  as bonus_amount'))
            ->where('bills.enable', 'Y')
            ->where('bills.transfer_type', 1)
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('bills.date_create', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
            })
            ->groupBy('bills.member_code');

        $latestWD = DB::table('withdraws', 'withdraws')
            ->select('withdraws.member_code', DB::raw('SUM(withdraws.amount)  as withdraw_amount'))
            ->where('withdraws.enable', 'Y')
            ->where('withdraws.status', 1)
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('withdraws.date_approve', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
            })
            ->groupBy('withdraws.member_code');

        $latestBP = DB::table('bank_payment')
            ->select(
                DB::raw('MAX(bank_payment.code) as code'),
                DB::raw('SUM(bank_payment.value) as deposit_amount'),
                DB::raw("'".$cashbackDate."' as date_cashback"),
                'bank_payment.member_topup'
            )
            ->where('bank_payment.value', '>', 0)
            ->where('bank_payment.bankstatus', 1)
            ->where('bank_payment.pro_id', 0)
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.status', 1)
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('bank_payment.date_approve', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
            })
            ->groupBy('bank_payment.member_topup');

        $lists = DB::table('members')
            ->select(
                'members.upline_code',
                'members.code as member_code',
                'members.user_name as user_name',
                'members.name as member_name',
                'members.balance as balance',
                DB::raw('IFNULL(withdraw_amount,0) as withdraw_amount'),
                'bank_payment.deposit_amount',
                DB::raw('IFNULL(bonus_amount,0) as bonus_amount'),
                DB::raw("'".$cashbackDate."' as date_cashback"),
                DB::raw("'".$startDate->toDateTimeString()."' as date_start"),
                DB::raw("'".$endDate->toDateTimeString()."' as date_stop"),
                'bank_payment.code'
            )
            ->orderByDesc('members.code')
            ->joinSub($latestBP, 'bank_payment', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'members.code');
            })
            ->leftJoinSub($latestBi, 'bills', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'bills.member_code');
                //                $join->on(DB::raw('Date(bank_payment.date_approve)'), '=', 'bills.date_approve');

            })
            ->leftJoinSub($latestWD, 'withdraws', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'withdraws.member_code');
            });

        $selectedCount = (clone $lists)->count();
        $dispatchedCount = 0;

        $lists->chunk(50, function ($itemlist) use ($startDate, $endDate, $cashbackDate, $promotion, $target, &$dispatchedCount) {

            foreach ($itemlist as $items) {
                Log::channel('cashback')->info(json_encode([
                    'member_code' => $items->member_code,
                    'mode' => $this->option('mode'),
                    'target' => $target,
                    'cashback_date' => $cashbackDate,
                ]));

                \App\Jobs\CashbackCalculate::dispatch(
                    $startDate->toDateTimeString(),
                    $endDate->toDateTimeString(),
                    $cashbackDate,
                    $items,
                    $promotion,
                    $target
                )->onQueue('cashback');

                $dispatchedCount++;
            }

        });

        $this->info('selected='.$selectedCount.' dispatched='.$dispatchedCount);

        return self::SUCCESS;
    }

    private function normalizeMode(string $mode): ?string
    {
        $normalized = strtolower(trim($mode));

        return in_array($normalized, ['range', 'daily'], true) ? $normalized : null;
    }

    private function normalizeTarget(string $target): ?string
    {
        $normalized = strtolower(trim($target));

        return in_array($normalized, ['wallet', 'cashback'], true) ? $normalized : null;
    }

    private function resolveAnchorDate(string $mode, mixed $input): ?Carbon
    {
        if (filled($input)) {
            try {
                return Carbon::createFromFormat('Y-m-d', (string) $input)->startOfDay();
            } catch (\Throwable $exception) {
                return null;
            }
        }

        if ($mode === 'daily') {
            return now()->subDay()->startOfDay();
        }

        return now()->startOfDay();
    }

    private function resolvePeriod(string $mode, Carbon $anchorDate): array
    {
        if ($mode === 'daily') {
            $startDate = $anchorDate->copy()->startOfDay();
            $endDate = $anchorDate->copy()->endOfDay();
            $cashbackDate = $anchorDate->toDateString();
            $roundLabel = 'รอบวันเดียว ('.$anchorDate->format('d/m/Y').')';

            return [$startDate, $endDate, $cashbackDate, $roundLabel];
        }

        $lastFriday = $anchorDate->copy()->subWeek()->endOfWeek(Carbon::FRIDAY);
        $lastSaturday = $lastFriday->copy()->subDays(6);
        $startDate = $lastSaturday->startOfDay();
        $endDate = $lastFriday->endOfDay();
        $cashbackDate = $startDate->toDateString();
        $roundLabel = 'รอบสัปดาห์ ('.$startDate->format('d/m').' - '.$endDate->format('d/m').')';

        return [$startDate, $endDate, $cashbackDate, $roundLabel];
    }
}
