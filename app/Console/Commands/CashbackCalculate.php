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
                            {--target=wallet : Credit target: wallet or cashback}
                            {--promo-policy= : Promotion handling: exclude_member or exclude_deposit}';

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
        $promoPolicy = $this->resolvePromoPolicy((string) $this->option('promo-policy'));
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

        $this->info($roundLabel.' | ปลายทางเครดิต: '.$target.' | promo policy: '.$promoPolicy);
        $promotion = DB::table('promotions')->where('id', 'pro_cashback')->first();

        if (! $promotion) {
            $this->error('ไม่พบ promotion pro_cashback');

            return self::FAILURE;
        }

        if ($promotion->enable != 'Y' || $promotion->active != 'Y' || $promotion->use_auto != 'Y') {
            $this->warn('promotion cashback ยังไม่พร้อมสำหรับ auto calculate');

            return self::SUCCESS;
        }

        $promoTopupBills = DB::table('bills')
            ->select('bills.member_code', DB::raw('COUNT(*) as promo_topup_count'))
            ->where('bills.enable', 'Y')
            ->where('bills.pro_code', '>', 0)
            ->where('bills.transfer_type', 1)
            ->where('bills.method', 'TOPUP')
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
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.status', 1)
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('bank_payment.date_approve', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
            })
            ->groupBy('bank_payment.member_topup');

        $promoTopupDeposits = DB::table('bank_payment')
            ->select(
                DB::raw('SUM(bank_payment.value) as promo_deposit_amount'),
                'bank_payment.member_topup'
            )
            ->where('bank_payment.value', '>', 0)
            ->where('bank_payment.bankstatus', 1)
            ->where('bank_payment.pro_id', '>', 0)
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.status', 1)
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('bank_payment.date_approve', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
            })
            ->groupBy('bank_payment.member_topup');

        $depositAmountExpression = $promoPolicy === 'exclude_deposit'
            ? 'CASE WHEN IFNULL(bank_payment.deposit_amount,0) - IFNULL(promo_bank_payment.promo_deposit_amount,0) > 0 THEN IFNULL(bank_payment.deposit_amount,0) - IFNULL(promo_bank_payment.promo_deposit_amount,0) ELSE 0 END'
            : 'IFNULL(bank_payment.deposit_amount,0)';

        $lists = DB::table('members')
            ->select(
                'members.upline_code',
                'members.code as member_code',
                'members.user_name as user_name',
                'members.name as member_name',
                'members.balance as balance',
                DB::raw('IFNULL(withdraw_amount,0) as withdraw_amount'),
                DB::raw($depositAmountExpression.' as deposit_amount'),
                DB::raw('IFNULL(promo_topup_bills.promo_topup_count,0) as promo_topup_count'),
                DB::raw('IFNULL(promo_bank_payment.promo_deposit_amount,0) as promo_deposit_amount'),
                DB::raw("'".$cashbackDate."' as date_cashback"),
                DB::raw("'".$startDate->toDateTimeString()."' as date_start"),
                DB::raw("'".$endDate->toDateTimeString()."' as date_stop"),
                'bank_payment.code'
            )
            ->orderByDesc('members.code')
            ->joinSub($latestBP, 'bank_payment', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'members.code');
            })
            ->leftJoinSub($promoTopupBills, 'promo_topup_bills', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'promo_topup_bills.member_code');
            })
            ->leftJoinSub($promoTopupDeposits, 'promo_bank_payment', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'promo_bank_payment.member_topup');
            })
            ->leftJoinSub($latestWD, 'withdraws', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'withdraws.member_code');
            });

        if ($promoPolicy === 'exclude_member') {
            $this->logExcludedMembersByPromotion(
                clone $lists,
                $cashbackDate,
                $startDate->toDateTimeString(),
                $endDate->toDateTimeString()
            );

            $lists->whereRaw('IFNULL(promo_topup_bills.promo_topup_count, 0) = 0');
        }

        $selectedCount = (clone $lists)->count();
        $dispatchedCount = 0;

        $lists->chunk(50, function ($itemlist) use ($startDate, $endDate, $cashbackDate, $promotion, $target, $promoPolicy, &$dispatchedCount) {

            foreach ($itemlist as $items) {
                Log::channel('cashback')->info(json_encode([
                    'member_code' => $items->member_code,
                    'mode' => $this->option('mode'),
                    'target' => $target,
                    'promo_policy' => $promoPolicy,
                    'eligibility_status' => 'passed',
                    'eligibility_reason' => 'selected_for_cashback_calculation',
                    'promo_topup_count' => $items->promo_topup_count,
                    'promo_deposit_amount' => $items->promo_deposit_amount,
                    'deposit_amount' => $items->deposit_amount,
                    'withdraw_amount' => $items->withdraw_amount,
                    'cashback_date' => $cashbackDate,
                ]));

                \App\Jobs\CashbackCalculate::dispatch(
                    $startDate->toDateTimeString(),
                    $endDate->toDateTimeString(),
                    $cashbackDate,
                    $items,
                    $promotion,
                    $target
                )->onQueue('default');

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

    private function resolvePromoPolicy(string $policyOption = ''): string
    {
        $policy = strtolower(trim($policyOption));

        if ($policy === '') {
            $policy = strtolower(trim((string) config('gametech.cashback.start.promo_policy', 'exclude_member')));
        }

        return in_array($policy, ['exclude_member', 'exclude_deposit'], true)
            ? $policy
            : 'exclude_member';
    }

    private function logExcludedMembersByPromotion($lists, string $cashbackDate, string $dateStart, string $dateEnd): void
    {
        $excludedLists = $lists
            ->whereRaw('IFNULL(promo_topup_bills.promo_topup_count, 0) > 0')
            ->select(
                'members.code as member_code',
                'members.user_name as user_name',
                DB::raw('IFNULL(promo_topup_bills.promo_topup_count,0) as promo_topup_count')
            );

        $excludedLists->chunk(50, function ($members) use ($cashbackDate, $dateStart, $dateEnd) {
            foreach ($members as $member) {
                Log::channel('cashback')->info(json_encode([
                    'member_code' => $member->member_code,
                    'mode' => $this->option('mode'),
                    'target' => $this->option('target'),
                    'promo_policy' => $this->option('promo-policy'),
                    'eligibility_status' => 'failed',
                    'eligibility_reason' => 'excluded_member_received_promo_topup',
                    'promo_topup_count' => (int) ($member->promo_topup_count ?? 0),
                    'cashback_date' => $cashbackDate,
                    'date_start' => Carbon::parse($dateStart)->toDateString(),
                    'date_end' => Carbon::parse($dateEnd)->toDateString(),
                ]));
                $this->insertExcludedMemberCreditLog($member, $cashbackDate, $dateStart, $dateEnd);
            }
        });
    }

    private function insertExcludedMemberCreditLog(object $member, string $cashbackDate, string $dateStart, string $dateEnd): void
    {
        $promoTopupCount = (int) ($member->promo_topup_count ?? 0);
        $remark = sprintf(
            'ตัดสิทธิ์ Cashback รอบ %s (%s - %s) เพราะรับโปรจากการฝาก %d รายการ',
            $cashbackDate,
            Carbon::parse($dateStart)->format('Y-m-d'),
            Carbon::parse($dateEnd)->format('Y-m-d'),
            $promoTopupCount
        );

        $existing = DB::table('members_credit_log')
            ->where('member_code', $member->member_code)
            ->where('kind', 'CASHBACK')
            ->where('refer_table', 'cashback')
            ->where('remark', $remark)
            ->exists();

        if ($existing) {
            return;
        }

        DB::table('members_credit_log')->insert([
            'refer_code' => 0,
            'refer_table' => 'cashback',
            'credit_type' => 'W',
            'amount' => 0,
            'bonus' => 0,
            'total' => 0,
            'balance_before' => 0,
            'balance_after' => 0,
            'credit' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => 0,
            'credit_after' => 0,
            'member_code' => $member->member_code,
            'kind' => 'CASHBACK',
            'auto' => 'Y',
            'remark' => $remark,
            'emp_code' => 0,
            'ip' => request()->ip() ?? 'SYSTEM',
            'amount_balance' => 0,
            'withdraw_limit' => 0,
            'withdraw_limit_amount' => 0,
            'user_create' => 'SYSTEM',
            'user_update' => 'SYSTEM',
            'date_create' => now()->toDateTimeString(),
            'date_update' => now()->toDateTimeString(),
        ]);
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
