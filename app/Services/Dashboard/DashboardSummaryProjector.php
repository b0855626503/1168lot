<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardSummaryProjector
{
    public const METRIC_VERSION = 3;

    private array $tableCache = [];
    private array $columnCache = [];

    public function projectDaily(string $summaryDate, string $webCode): array
    {
        $summaryDate = $this->normalizeDate($summaryDate);

        $register = $this->registerMetrics($summaryDate, $webCode);
        $deposit = $this->depositMetrics($summaryDate, $webCode);
        $withdraw = $this->withdrawMetrics($summaryDate, $webCode);
        $bonus = $this->bonusMetrics($summaryDate, $webCode);
        $staff = $this->staffAdjustMetrics($summaryDate, $webCode);
        $lottoCash = $this->lottoCashMetrics($summaryDate, $webCode);

        $netAmount = round(
            (float) $deposit['deposit_success_amount']
            - (float) $withdraw['withdraw_total_amount']
            + (float) $lottoCash['lotto_net_cash'],
            2
        );

        return [
            'summary_date' => $summaryDate,
            'web_code' => $webCode,

            'register_total' => (int) $register['register_total'],
            'register_direct' => (int) $register['register_direct'],
            'register_referral' => (int) $register['register_referral'],
            'register_campaign' => (int) $register['register_campaign'],

            'deposit_total_amount' => $this->toDecimal($deposit['deposit_total_amount']),
            'deposit_total_count' => (int) $deposit['deposit_total_count'],
            'deposit_total_users' => (int) $deposit['deposit_total_users'],

            'deposit_success_amount' => $this->toDecimal($deposit['deposit_success_amount']),
            'deposit_success_count' => (int) $deposit['deposit_success_count'],
            'deposit_success_users' => (int) $deposit['deposit_success_users'],

            'deposit_pending_amount' => $this->toDecimal($deposit['deposit_pending_amount']),
            'deposit_pending_count' => (int) $deposit['deposit_pending_count'],
            'deposit_pending_users' => (int) $deposit['deposit_pending_users'],

            'deposit_reject_amount' => $this->toDecimal($deposit['deposit_reject_amount']),
            'deposit_reject_count' => (int) $deposit['deposit_reject_count'],
            'deposit_reject_users' => (int) $deposit['deposit_reject_users'],

            'deposit_deleted_amount' => $this->toDecimal($deposit['deposit_deleted_amount']),
            'deposit_deleted_count' => (int) $deposit['deposit_deleted_count'],
            'deposit_deleted_users' => (int) $deposit['deposit_deleted_users'],

            'withdraw_total_amount' => $this->toDecimal($withdraw['withdraw_total_amount']),
            'withdraw_total_count' => (int) $withdraw['withdraw_total_count'],
            'withdraw_total_users' => (int) $withdraw['withdraw_total_users'],

            'withdraw_pending_amount' => $this->toDecimal($withdraw['withdraw_pending_amount']),
            'withdraw_pending_count' => (int) $withdraw['withdraw_pending_count'],

            'bonus_deposit_amount' => $this->toDecimal($bonus['bonus_deposit_amount']),
            'bonus_deposit_count' => (int) $bonus['bonus_deposit_count'],

            'bonus_activity_amount' => $this->toDecimal($bonus['bonus_activity_amount']),
            'bonus_activity_count' => (int) $bonus['bonus_activity_count'],

            'bonus_manual_amount' => $this->toDecimal($bonus['bonus_manual_amount']),
            'bonus_manual_count' => (int) $bonus['bonus_manual_count'],

            'bonus_total_amount' => $this->toDecimal($bonus['bonus_total_amount']),
            'bonus_total_count' => (int) $bonus['bonus_total_count'],

            'lotto_sales_cash' => $this->toDecimal($lottoCash['lotto_sales_cash']),
            'lotto_payout_cash' => $this->toDecimal($lottoCash['lotto_payout_cash']),
            'lotto_refund_cash' => $this->toDecimal($lottoCash['lotto_refund_cash']),
            'lotto_net_cash' => $this->toDecimal($lottoCash['lotto_net_cash']),

            'net_amount' => $this->toDecimal($netAmount),

            'register_deposit_count' => (int) $register['register_deposit_count'],
            'register_referral_deposit_count' => (int) $register['register_referral_deposit_count'],
            'first_deposit_count' => (int) $register['first_deposit_count'],
            'repeat_deposit_count' => (int) $register['repeat_deposit_count'],
            'register_confirmed_count' => (int) $register['register_confirmed_count'],

            'staff_add_amount' => $this->toDecimal($staff['staff_add_amount']),
            'staff_reduce_amount' => $this->toDecimal($staff['staff_reduce_amount']),
            'staff_adjust_count' => (int) $staff['staff_adjust_count'],

            'last_synced_at' => now()->toDateTimeString(),
            'metric_version' => self::METRIC_VERSION,
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array{
     *     daily: array<string, mixed>,
     *     markets: array<int, array<string, mixed>>,
     *     risk: array<int, array<string, mixed>>
     * }
     */
    public function projectLotto(string $summaryDate, string $webCode): array
    {
        $summaryDate = $this->normalizeDate($summaryDate);

        return [
            'daily' => $this->lottoProductDailyMetrics($summaryDate, $webCode),
            'markets' => $this->lottoMarketSummaryMetrics($summaryDate, $webCode),
            'risk' => $this->lottoRiskSnapshotMetrics($summaryDate, $webCode),
        ];
    }

    private function depositMetrics(string $summaryDate, string $webCode): array
    {
        [$rangeStart, $rangeEnd] = $this->dayRange($summaryDate);

        $defaults = [
            'deposit_total_amount' => 0,
            'deposit_total_count' => 0,
            'deposit_total_users' => 0,
            'deposit_success_amount' => 0,
            'deposit_success_count' => 0,
            'deposit_success_users' => 0,
            'deposit_pending_amount' => 0,
            'deposit_pending_count' => 0,
            'deposit_pending_users' => 0,
            'deposit_reject_amount' => 0,
            'deposit_reject_count' => 0,
            'deposit_reject_users' => 0,
            'deposit_deleted_amount' => 0,
            'deposit_deleted_count' => 0,
            'deposit_deleted_users' => 0,
        ];

        if (!$this->hasTable('bank_payment')) {
            return $defaults;
        }

        $base = DB::table('bank_payment')
            ->where('value', '>', 0);
        $this->applyDateTimeWindow($base, 'date_create', $rangeStart, $rangeEnd);
        $base = $this->applyWebScope($base, 'bank_payment', $webCode);

        $total = (clone $base);
        if ($this->hasColumn('bank_payment', 'enable')) {
            $total->where('enable', 'Y');
        }
        if ($this->hasColumn('bank_payment', 'status')) {
            $total->whereNotIn('status', [2, 3]);
        }

        $defaults['deposit_total_amount'] = (float) (clone $total)->sum('value');
        $defaults['deposit_total_count'] = (int) (clone $total)->count();
        $defaults['deposit_total_users'] = $this->countDistinctMembers((clone $total), 'bank_payment', 'member_topup');

        $success = (clone $base)
            ->where('enable', 'Y')
            ->where('status', 1);

        $defaults['deposit_success_amount'] = (float) (clone $success)->sum('value');
        $defaults['deposit_success_count'] = (int) (clone $success)->count();
        $defaults['deposit_success_users'] = $this->countDistinctMembers((clone $success), 'bank_payment', 'member_topup');

        $pending = (clone $base)
            ->where('enable', 'Y')
            ->where('status', 0);

        $defaults['deposit_pending_amount'] = (float) (clone $pending)->sum('value');
        $defaults['deposit_pending_count'] = (int) (clone $pending)->count();
        $defaults['deposit_pending_users'] = $this->countDistinctMembers((clone $pending), 'bank_payment', 'member_topup');

        $reject = (clone $base)
            ->where('enable', 'Y')
            ->where('status', 2);

        $defaults['deposit_reject_amount'] = (float) (clone $reject)->sum('value');
        $defaults['deposit_reject_count'] = (int) (clone $reject)->count();
        $defaults['deposit_reject_users'] = $this->countDistinctMembers((clone $reject), 'bank_payment', 'member_topup');

        $deleted = (clone $base)->where(function ($query) {
            $query->where('enable', '<>', 'Y');
            if ($this->hasColumn('bank_payment', 'status')) {
                $query->orWhere('status', 3);
            }
        });

        $defaults['deposit_deleted_amount'] = (float) (clone $deleted)->sum('value');
        $defaults['deposit_deleted_count'] = (int) (clone $deleted)->count();
        $defaults['deposit_deleted_users'] = $this->countDistinctMembers((clone $deleted), 'bank_payment', 'member_topup');

        return $defaults;
    }

    private function withdrawMetrics(string $summaryDate, string $webCode): array
    {
        [$rangeStart, $rangeEnd] = $this->dayRange($summaryDate);

        $defaults = [
            'withdraw_total_amount' => 0,
            'withdraw_total_count' => 0,
            'withdraw_total_users' => 0,
            'withdraw_pending_amount' => 0,
            'withdraw_pending_count' => 0,
        ];

        foreach ($this->withdrawTables() as $table) {
            $approved = DB::table($table)
                ->where('enable', 'Y')
                ->where('status', 1);
            $this->applyDateTimeWindow($approved, 'date_approve', $rangeStart, $rangeEnd);
            $approved = $this->applyWebScope($approved, $table, $webCode);

            $defaults['withdraw_total_amount'] += (float) (clone $approved)->sum('amount');
            $defaults['withdraw_total_count'] += (int) (clone $approved)->count();
            $defaults['withdraw_total_users'] += $this->countDistinctMembers((clone $approved), $table, 'member_code');

            $pending = DB::table($table)
                ->where('enable', 'Y')
                ->where('status', 0);
            $this->applyDateTimeWindow($pending, 'date_create', $rangeStart, $rangeEnd);
            $pending = $this->applyWebScope($pending, $table, $webCode);

            $defaults['withdraw_pending_amount'] += (float) (clone $pending)->sum('amount');
            $defaults['withdraw_pending_count'] += (int) (clone $pending)->count();
        }

        return $defaults;
    }

    private function registerMetrics(string $summaryDate, string $webCode): array
    {
        [$rangeStart, $rangeEnd] = $this->dayRange($summaryDate);

        $defaults = [
            'register_total' => 0,
            'register_direct' => 0,
            'register_referral' => 0,
            'register_campaign' => 0,
            'register_deposit_count' => 0,
            'register_referral_deposit_count' => 0,
            'first_deposit_count' => 0,
            'repeat_deposit_count' => 0,
            'register_confirmed_count' => 0,
        ];

        if (!$this->hasTable('members')) {
            return $defaults;
        }

        $memberDateColumn = $this->memberDateColumn();

        $base = DB::table('members')
            ->where($memberDateColumn, '>=', $rangeStart)
            ->where($memberDateColumn, '<', $rangeEnd);
        $base = $this->applyWebScope($base, 'members', $webCode);

        $defaults['register_total'] = (int) (clone $base)->count();

        $campaign = 0;
        if ($this->hasColumn('members', 'campaign_id')) {
            $campaign = (int) (clone $base)
                ->whereNotNull('campaign_id')
                ->where('campaign_id', '>', 0)
                ->count();
        }
        $defaults['register_campaign'] = $campaign;

        $referral = 0;
        if ($this->hasColumn('members', 'upline_code')) {
            $referralQuery = (clone $base)
                ->where('upline_code', '>', 0);

            if ($this->hasColumn('members', 'campaign_id')) {
                $referralQuery->where(function ($query) {
                    $query->whereNull('campaign_id')->orWhere('campaign_id', 0);
                });
            }

            $referral = (int) $referralQuery->count();
        }
        $defaults['register_referral'] = $referral;

        $defaults['register_direct'] = max(0, $defaults['register_total'] - $campaign - $referral);

        if ($this->hasColumn('members', 'confirm')) {
            $defaults['register_confirmed_count'] = (int) (clone $base)
                ->whereIn('confirm', ['Y', '1', 1])
                ->count();
        }

        if (!$this->hasTable('bank_payment') || !$this->hasColumn('bank_payment', 'member_topup')) {
            return $defaults;
        }

        $registerDeposit = DB::table('members as m')
            ->where("m.{$memberDateColumn}", '>=', $rangeStart)
            ->where("m.{$memberDateColumn}", '<', $rangeEnd);
        $registerDeposit = $this->applyWebScope($registerDeposit, 'members', $webCode, 'm');

        $registerDeposit->whereExists(function ($query) use ($rangeStart, $rangeEnd, $webCode) {
            $query->select(DB::raw(1))
                ->from('bank_payment as bp')
                ->whereColumn('bp.member_topup', 'm.code')
                ->where('bp.enable', 'Y')
                ->where('bp.status', 1)
                ->where('bp.value', '>', 0)
                ->where('bp.date_create', '>=', $rangeStart)
                ->where('bp.date_create', '<', $rangeEnd);

            $this->applyWebScope($query, 'bank_payment', $webCode, 'bp');
        });

        $defaults['register_deposit_count'] = (int) $registerDeposit->count();

        if ($this->hasColumn('members', 'upline_code')) {
            $referralDeposit = DB::table('members as m')
                ->where("m.{$memberDateColumn}", '>=', $rangeStart)
                ->where("m.{$memberDateColumn}", '<', $rangeEnd)
                ->where('m.upline_code', '>', 0);
            $referralDeposit = $this->applyWebScope($referralDeposit, 'members', $webCode, 'm');

            if ($this->hasColumn('members', 'campaign_id')) {
                $referralDeposit->where(function ($query) {
                    $query->whereNull('m.campaign_id')->orWhere('m.campaign_id', 0);
                });
            }

            $referralDeposit->whereExists(function ($query) use ($rangeStart, $rangeEnd, $webCode) {
                $query->select(DB::raw(1))
                    ->from('bank_payment as bp')
                    ->whereColumn('bp.member_topup', 'm.code')
                    ->where('bp.enable', 'Y')
                    ->where('bp.status', 1)
                    ->where('bp.value', '>', 0)
                    ->where('bp.date_create', '>=', $rangeStart)
                    ->where('bp.date_create', '<', $rangeEnd);

                $this->applyWebScope($query, 'bank_payment', $webCode, 'bp');
            });

            $defaults['register_referral_deposit_count'] = (int) $referralDeposit->count();
        }

        $firstDepositSub = DB::table('bank_payment')
            ->where('enable', 'Y')
            ->where('status', 1)
            ->where('value', '>', 0)
            ->whereNotNull('member_topup')
            ->where('member_topup', '>', 0)
            ->selectRaw('member_topup, MIN(date_create) as first_at')
            ->groupBy('member_topup');
        $firstDepositSub = $this->applyWebScope($firstDepositSub, 'bank_payment', $webCode);

        $defaults['first_deposit_count'] = (int) DB::query()
            ->fromSub($firstDepositSub, 'fd')
            ->where('first_at', '>=', $rangeStart)
            ->where('first_at', '<', $rangeEnd)
            ->count();

        $daySuccess = DB::table('bank_payment as bp_day')
            ->select('bp_day.member_topup')
            ->where('bp_day.enable', 'Y')
            ->where('bp_day.status', 1)
            ->where('bp_day.value', '>', 0)
            ->whereNotNull('bp_day.member_topup')
            ->where('bp_day.member_topup', '>', 0)
            ->where('bp_day.date_create', '>=', $rangeStart)
            ->where('bp_day.date_create', '<', $rangeEnd)
            ->groupBy('bp_day.member_topup');
        $daySuccess = $this->applyWebScope($daySuccess, 'bank_payment', $webCode, 'bp_day');

        $lifetimeRepeat = DB::table('bank_payment as bp_all')
            ->select('bp_all.member_topup')
            ->where('bp_all.enable', 'Y')
            ->where('bp_all.status', 1)
            ->where('bp_all.value', '>', 0)
            ->whereNotNull('bp_all.member_topup')
            ->where('bp_all.member_topup', '>', 0)
            ->groupBy('bp_all.member_topup')
            ->havingRaw('COUNT(*) >= 2');
        $lifetimeRepeat = $this->applyWebScope($lifetimeRepeat, 'bank_payment', $webCode, 'bp_all');

        $defaults['repeat_deposit_count'] = (int) DB::query()
            ->fromSub($daySuccess, 'day_success')
            ->joinSub($lifetimeRepeat, 'life_repeat', function ($join) {
                $join->on('life_repeat.member_topup', '=', 'day_success.member_topup');
            })
            ->count();

        return $defaults;
    }

    private function staffAdjustMetrics(string $summaryDate, string $webCode): array
    {
        [$rangeStart, $rangeEnd] = $this->dayRange($summaryDate);

        $defaults = [
            'staff_add_amount' => 0,
            'staff_reduce_amount' => 0,
            'staff_adjust_count' => 0,
        ];

        if (!$this->hasTable('members_credit_log')) {
            return $defaults;
        }

        $base = DB::table('members_credit_log')
            ->where('kind', 'SETWALLET')
            ->where('members_credit_log.date_create', '>=', $rangeStart)
            ->where('members_credit_log.date_create', '<', $rangeEnd);
        $base = $this->applyWebScopeByMember($base, 'members_credit_log', $webCode);

        $add = (clone $base)->where('credit_type', 'D');
        $reduce = (clone $base)->where('credit_type', 'W');

        $defaults['staff_add_amount'] = (float) (clone $add)->sum('amount');
        $defaults['staff_reduce_amount'] = (float) (clone $reduce)->sum('amount');
        $defaults['staff_adjust_count'] = (int) ((clone $add)->count() + (clone $reduce)->count());

        return $defaults;
    }

    private function bonusMetrics(string $summaryDate, string $webCode): array
    {
        [$rangeStart, $rangeEnd] = $this->dayRange($summaryDate);

        $defaults = [
            'bonus_deposit_amount' => 0,
            'bonus_deposit_count' => 0,
            'bonus_activity_amount' => 0,
            'bonus_activity_count' => 0,
            'bonus_manual_amount' => 0,
            'bonus_manual_count' => 0,
            'bonus_total_amount' => 0,
            'bonus_total_count' => 0,
        ];

        if ($this->hasTable('payments_promotion')) {
            $depositBonus = DB::table('payments_promotion')
                ->where('enable', 'Y');
            $this->applyDateTimeWindow($depositBonus, 'date_create', $rangeStart, $rangeEnd);
            $depositBonus = $this->applyWebScope($depositBonus, 'payments_promotion', $webCode);

            if ($this->hasColumn('payments_promotion', 'credit_bonus')) {
                $depositBonus->where('credit_bonus', '>', 0);
            }

            if ($this->hasColumn('payments_promotion', 'pro_code')) {
                $depositBonus->where('pro_code', 6);
            }

            $defaults['bonus_deposit_amount'] = (float) (clone $depositBonus)->sum('credit_bonus');
            $defaults['bonus_deposit_count'] = (int) (clone $depositBonus)->count();
        }

        if ($this->hasTable('bills')) {
            $billBase = DB::table('bills')
                ->where('enable', 'Y');
            $this->applyDateTimeWindow($billBase, 'date_create', $rangeStart, $rangeEnd);
            $billBase = $this->applyWebScope($billBase, 'bills', $webCode);

            if ($this->hasColumn('bills', 'credit_bonus')) {
                $billBase->where('credit_bonus', '>', 0);
            }

            if ($this->hasColumn('bills', 'pro_code')) {
                $billBase->where('pro_code', '>', 0);
            }

            $activity = (clone $billBase);
            if ($this->hasColumn('bills', 'transfer_type')) {
                $activity->where('transfer_type', 1);
            }

            $defaults['bonus_activity_amount'] = (float) (clone $activity)->sum('credit_bonus');
            $defaults['bonus_activity_count'] = (int) (clone $activity)->count();

            $manual = (clone $billBase);
            if ($this->hasColumn('bills', 'transfer_type')) {
                $manual->where(function ($query) {
                    $query->whereNull('transfer_type')->orWhere('transfer_type', '<>', 1);
                });
            }

            $defaults['bonus_manual_amount'] = (float) (clone $manual)->sum('credit_bonus');
            $defaults['bonus_manual_count'] = (int) (clone $manual)->count();
        }

        $defaults['bonus_total_amount'] = (float) $defaults['bonus_deposit_amount']
            + (float) $defaults['bonus_activity_amount']
            + (float) $defaults['bonus_manual_amount'];

        $defaults['bonus_total_count'] = (int) $defaults['bonus_deposit_count']
            + (int) $defaults['bonus_activity_count']
            + (int) $defaults['bonus_manual_count'];

        return $defaults;
    }

    private function lottoCashMetrics(string $summaryDate, string $webCode): array
    {
        [$rangeStart, $rangeEnd] = $this->dayRange($summaryDate);

        $defaults = [
            'lotto_sales_cash' => 0.0,
            'lotto_payout_cash' => 0.0,
            'lotto_refund_cash' => 0.0,
            'lotto_net_cash' => 0.0,
        ];

        if (!$this->hasTable('wallet_transactions')) {
            return $defaults;
        }

        $base = DB::table('wallet_transactions')
            ->where('scope', 'MEMBER')
            ->where('status', LottoDashboardMetricConfig::WALLET_SUCCESS_STATUS)
            ->where('created_at', '>=', $rangeStart)
            ->where('created_at', '<', $rangeEnd);
        $base = $this->applyWebScopeByMember($base, 'wallet_transactions', $webCode);

        $sales = (clone $base)
            ->where('direction', 'DEBIT')
            ->whereIn('ref_type', LottoDashboardMetricConfig::salesRefTypes());
        $defaults['lotto_sales_cash'] = (float) (clone $sales)->sum('amount');

        $payout = (clone $base)
            ->where('direction', 'CREDIT')
            ->whereIn('ref_type', LottoDashboardMetricConfig::payoutRefTypes());
        $defaults['lotto_payout_cash'] = (float) (clone $payout)->sum('amount');

        $refund = (clone $base)
            ->where('direction', 'CREDIT')
            ->whereIn('ref_type', LottoDashboardMetricConfig::refundRefTypes());
        $defaults['lotto_refund_cash'] = (float) (clone $refund)->sum('amount');

        $defaults['lotto_net_cash'] = round(
            (float) $defaults['lotto_sales_cash']
            - (float) $defaults['lotto_payout_cash']
            - (float) $defaults['lotto_refund_cash'],
            2
        );

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    private function lottoProductDailyMetrics(string $summaryDate, string $webCode): array
    {
        [$rangeStart, $rangeEnd] = $this->dayRange($summaryDate);

        $defaults = [
            'summary_date' => $summaryDate,
            'web_code' => $webCode,
            'total_sales' => 0.0,
            'total_payout' => 0.0,
            'total_tickets' => 0,
            'total_players' => 0,
            'win_tickets' => 0,
            'lose_tickets' => 0,
            'pending_tickets' => 0,
            'settled_tickets' => 0,
            'sales_unique_players' => 0,
            'settled_unique_players' => 0,
            'metric_version' => self::METRIC_VERSION,
            'last_synced_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
        ];

        if (!$this->hasTable('lotto_tickets')) {
            return $defaults;
        }

        $salesBase = DB::table('lotto_tickets')
            ->where('created_at', '>=', $rangeStart)
            ->where('created_at', '<', $rangeEnd);
        $salesBase = $this->applyWebScopeByMember($salesBase, 'lotto_tickets', $webCode);

        $defaults['total_sales'] = (float) (clone $salesBase)->sum(DB::raw($this->lottoTicketNetAmountExpression('lotto_tickets')));
        $defaults['total_tickets'] = (int) (clone $salesBase)->count();
        $defaults['total_players'] = $this->countDistinctMembers((clone $salesBase), 'lotto_tickets', 'member_id');
        $defaults['sales_unique_players'] = $defaults['total_players'];

        $pending = (clone $salesBase)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'resulted');
            });
        $defaults['pending_tickets'] = (int) (clone $pending)->count();

        if ($this->hasTable('lotto_draws')) {
            $settledBase = DB::table('lotto_tickets as lt')
                ->join('lotto_draws as ld', 'ld.id', '=', 'lt.draw_id')
                ->where('ld.result_at', '>=', $rangeStart)
                ->where('ld.result_at', '<', $rangeEnd)
                ->where('lt.status', 'resulted');
            $settledBase = $this->applyWebScopeByMember($settledBase, 'lotto_tickets', $webCode, 'lt');

            $defaults['settled_tickets'] = (int) (clone $settledBase)->count();
            $winAmountColumn = $this->lottoTicketWinAmountExpression('lotto_tickets', 'lt');
            $defaults['win_tickets'] = (int) (clone $settledBase)->whereRaw($winAmountColumn . ' > 0')->count();
            $defaults['lose_tickets'] = max(0, $defaults['settled_tickets'] - $defaults['win_tickets']);
            $defaults['total_payout'] = (float) (clone $settledBase)->sum(DB::raw($winAmountColumn));

            $defaults['settled_unique_players'] = (int) (clone $settledBase)
                ->whereNotNull('lt.member_id')
                ->distinct('lt.member_id')
                ->count('lt.member_id');
        }

        return $defaults;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lottoMarketSummaryMetrics(string $summaryDate, string $webCode): array
    {
        [$rangeStart, $rangeEnd] = $this->dayRange($summaryDate);
        if (!$this->hasTable('lotto_tickets') || !$this->hasTable('lotto_draws')) {
            return [];
        }

        $salesRows = DB::table('lotto_tickets as lt')
            ->join('lotto_draws as ld', 'ld.id', '=', 'lt.draw_id')
            ->select([
                'ld.market_id',
                'lt.draw_id as round_id',
                DB::raw('COALESCE(SUM(' . $this->lottoTicketNetAmountExpression('lotto_tickets', 'lt') . '), 0) as total_sales'),
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('COUNT(DISTINCT lt.member_id) as total_players'),
                DB::raw('MAX(ld.status) as status'),
            ])
            ->where('lt.created_at', '>=', $rangeStart)
            ->where('lt.created_at', '<', $rangeEnd)
            ->groupBy('ld.market_id', 'lt.draw_id');
        $salesRows = $this->applyWebScopeByMember($salesRows, 'lotto_tickets', $webCode, 'lt');
        $salesRows = $salesRows->get();

        $payoutRows = DB::table('lotto_tickets as lt')
            ->join('lotto_draws as ld', 'ld.id', '=', 'lt.draw_id')
            ->select([
                'ld.market_id',
                'lt.draw_id as round_id',
                DB::raw('COALESCE(SUM(' . $this->lottoTicketWinAmountExpression('lotto_tickets', 'lt') . '), 0) as total_payout'),
                DB::raw('MAX(ld.status) as status'),
            ])
            ->where('ld.result_at', '>=', $rangeStart)
            ->where('ld.result_at', '<', $rangeEnd)
            ->where('lt.status', 'resulted')
            ->groupBy('ld.market_id', 'lt.draw_id');
        $payoutRows = $this->applyWebScopeByMember($payoutRows, 'lotto_tickets', $webCode, 'lt');
        $payoutRows = $payoutRows->get();

        $map = [];
        foreach ($salesRows as $row) {
            $key = (int) $row->market_id . ':' . (int) $row->round_id;
            $map[$key] = [
                'summary_date' => $summaryDate,
                'web_code' => $webCode,
                'market_id' => (int) $row->market_id,
                'round_id' => (int) $row->round_id,
                'total_sales' => $this->toDecimal((float) ($row->total_sales ?? 0)),
                'total_tickets' => (int) ($row->total_tickets ?? 0),
                'total_players' => (int) ($row->total_players ?? 0),
                'total_payout' => 0.0,
                'status' => (string) ($row->status ?? 'pending'),
                'last_synced_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
            ];
        }

        foreach ($payoutRows as $row) {
            $key = (int) $row->market_id . ':' . (int) $row->round_id;
            if (!isset($map[$key])) {
                $map[$key] = [
                    'summary_date' => $summaryDate,
                    'web_code' => $webCode,
                    'market_id' => (int) $row->market_id,
                    'round_id' => (int) $row->round_id,
                    'total_sales' => 0.0,
                    'total_tickets' => 0,
                    'total_players' => 0,
                    'total_payout' => 0.0,
                    'status' => (string) ($row->status ?? 'pending'),
                    'last_synced_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                ];
            }

            $map[$key]['total_payout'] = $this->toDecimal((float) ($row->total_payout ?? 0));
            $map[$key]['status'] = (string) ($row->status ?? $map[$key]['status']);
            $map[$key]['updated_at'] = now()->toDateTimeString();
            $map[$key]['last_synced_at'] = now()->toDateTimeString();
        }

        return array_values($map);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lottoRiskSnapshotMetrics(string $summaryDate, string $webCode): array
    {
        if (!$this->hasTable('lotto_number_exposures') || !$this->hasTable('lotto_draws')) {
            return [];
        }

        $snapshotAt = Carbon::parse($summaryDate)->endOfDay()->format('Y-m-d H:i:s');

        $rows = DB::table('lotto_number_exposures as e')
            ->join('lotto_draws as d', 'd.id', '=', 'e.draw_id')
            ->leftJoin('lotto_draw_bet_settings as s', function ($join) {
                $join->on('s.draw_id', '=', 'd.id')
                    ->on('s.bet_type', '=', 'e.bet_type');
            })
            ->select([
                'd.market_id',
                'e.draw_id as round_id',
                'e.bet_type',
                'e.number',
                DB::raw('COALESCE(e.sold_amount, 0) as stake_total'),
                DB::raw('COALESCE(e.sold_amount, 0) * COALESCE(s.payout, 0) as payout_if_hit'),
            ])
            ->whereDate('d.draw_date', '<=', $summaryDate)
            ->get();

        return $rows->map(function ($row) use ($snapshotAt, $webCode) {
            $payoutIfHit = $this->toDecimal((float) ($row->payout_if_hit ?? 0));

            return [
                'web_code' => $webCode,
                'market_id' => (int) $row->market_id,
                'round_id' => (int) $row->round_id,
                'bet_type' => (string) $row->bet_type,
                'number' => (string) $row->number,
                'snapshot_at' => $snapshotAt,
                'stake_total' => $this->toDecimal((float) ($row->stake_total ?? 0)),
                'payout_if_hit' => $payoutIfHit,
                'liability' => $payoutIfHit,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];
        })->values()->all();
    }

    private function withdrawTables(): array
    {
        $config = $this->coreConfig();
        if (($config->seamless ?? 'N') === 'Y') {
            return $this->hasTable('withdraws') ? ['withdraws'] : [];
        }

        $tables = [];
        foreach (['withdraws', 'withdraws_seamless'] as $table) {
            if ($this->hasTable($table)) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    private function coreConfig(): ?object
    {
        try {
            return core()->getConfigData();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function memberDateColumn(): string
    {
        if ($this->hasColumn('members', 'date_regis')) {
            return 'date_regis';
        }

        if ($this->hasColumn('members', 'date_create')) {
            return 'date_create';
        }

        return 'date_regis';
    }

    private function applyWebScope($query, string $table, string $webCode, ?string $alias = null)
    {
        $column = $this->webColumn($table);
        if (!$column) {
            return $query;
        }

        $target = $alias ? $alias . '.' . $column : $column;

        if (in_array($column, ['webcode', 'team_id'], true) && !ctype_digit($webCode)) {
            return $query;
        }

        $value = ctype_digit($webCode) ? (int) $webCode : $webCode;

        return $query->where($target, $value);
    }

    private function applyWebScopeByMember($query, string $table, string $webCode, ?string $alias = null)
    {
        $tableMemberKey = $this->memberForeignKey($table);
        $memberWebColumn = $this->webColumn('members');

        if (!$tableMemberKey || !$memberWebColumn || !$this->hasTable('members')) {
            return $query;
        }

        if (in_array($memberWebColumn, ['webcode', 'team_id'], true) && !ctype_digit($webCode)) {
            return $query;
        }

        $value = ctype_digit($webCode) ? (int) $webCode : $webCode;

        $targetPrefix = $alias ? $alias . '.' : $table . '.';

        return $query
            ->join('members as m_scope', 'm_scope.code', '=', $targetPrefix . $tableMemberKey)
            ->where('m_scope.' . $memberWebColumn, $value);
    }

    private function countDistinctMembers($query, string $table, string $memberColumn): int
    {
        if (!$this->hasColumn($table, $memberColumn)) {
            return 0;
        }

        $query->whereNotNull($memberColumn);

        if ($this->isLikelyNumericMemberKey($table, $memberColumn)) {
            $query->where($memberColumn, '>', 0);
        }

        return (int) $query->distinct($memberColumn)->count($memberColumn);
    }

    private function isLikelyNumericMemberKey(string $table, string $column): bool
    {
        return in_array($column, ['member_topup', 'member_code', 'member_id'], true) && $this->hasColumn($table, $column);
    }

    private function webColumn(string $table): ?string
    {
        $candidates = ['web_code', 'webcode', 'website', 'team_id'];

        foreach ($candidates as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function memberForeignKey(string $table): ?string
    {
        $candidates = ['member_code', 'member_topup', 'member_id'];
        foreach ($candidates as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function lottoTicketNetAmountExpression(string $table, ?string $alias = null): string
    {
        $prefix = $alias ? $alias . '.' : '';

        if ($this->hasColumn($table, 'total_net_amount')) {
            return 'COALESCE(' . $prefix . 'total_net_amount, ' . $prefix . 'total_amount, 0)';
        }

        if ($this->hasColumn($table, 'total_amount')) {
            return 'COALESCE(' . $prefix . 'total_amount, 0)';
        }

        return '0';
    }

    private function lottoTicketWinAmountExpression(string $table, ?string $alias = null): string
    {
        $prefix = $alias ? $alias . '.' : '';

        if ($this->hasColumn($table, 'total_win_amount')) {
            return 'COALESCE(' . $prefix . 'total_win_amount, 0)';
        }

        return '0';
    }

    private function normalizeDate(string $summaryDate): string
    {
        return Carbon::parse($summaryDate)->toDateString();
    }

    private function dayRange(string $summaryDate): array
    {
        $start = Carbon::parse($summaryDate)->startOfDay();

        return [$start->toDateTimeString(), $start->copy()->addDay()->toDateTimeString()];
    }

    private function applyDateTimeWindow($query, string $column, string $startAt, string $endAt): void
    {
        $query->where($column, '>=', $startAt)
            ->where($column, '<', $endAt);
    }

    private function toDecimal(float|int $value): float
    {
        return round((float) $value, 2);
    }

    private function hasTable(string $table): bool
    {
        if (!array_key_exists($table, $this->tableCache)) {
            $this->tableCache[$table] = Schema::hasTable($table);
        }

        return $this->tableCache[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = $this->hasTable($table) && Schema::hasColumn($table, $column);
        }

        return $this->columnCache[$key];
    }
}
