<?php

namespace Gametech\Admin\Services;

use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\DashboardWebCodeResolver;
use App\Services\Dashboard\LottoDashboardMetricConfig;
use Carbon\Carbon;
use Gametech\Lotto\Enums\BetType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DashboardService
{
    public const CACHE_TTL_SECONDS = 45;
    private const ACTIVITY_CACHE_TTL_SECONDS = 5;
    private const CACHE_VERSION_KEY = 'dashboard:summary:version';
    private array $columnCache = [];
    private array $columnListingCache = [];
    private array $tableCache = [];
    private array $summaryWarmCache = [];

    public function getOptions(): array
    {
        return Cache::remember($this->cacheKey('options', []), now()->addSeconds(300), function () {
            $teams = collect();
            $campaigns = collect();

            $depositChannels = collect();
            if ($this->hasTable('bank_payment') && $this->hasColumn('bank_payment', 'channel')) {
                $depositChannels = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
                    ->active()
                    ->select('channel')
                    ->whereNotNull('channel')
                    ->groupBy('channel')
                    ->orderBy('channel')
                    ->pluck('channel')
                    ->filter()
                    ->values();
            }

            return [
                'webs' => $teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
                'brands' => $campaigns->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                'register_channels' => [
                    ['id' => 'direct', 'name' => 'สมัครตรง'],
                    ['id' => 'referral', 'name' => 'การแนะนำ'],
                ],
                'deposit_channels' => $depositChannels->map(fn ($ch) => ['id' => $ch, 'name' => $ch])->values(),
            ];
        });
    }

    public function getSummary(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        if (
            $this->hasTable('dashboard_summary_daily')
            && empty($filters['register_channel'])
            && empty($filters['deposit_channel'])
        ) {
            return $this->getSummaryFromSummaryTable($filters);
        }

        return Cache::remember($this->cacheKey('summary', $filters), now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($filters) {
            [$startDate, $endDate] = $this->range($filters);
            [$prevStart, $prevEnd] = $this->previousRange($filters);

            $depositBase = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
                ->income();
            $this->applyDateTimeWindow($depositBase, 'date_create', $startDate, $endDate);
            $depositBase = $this->applyPaymentFilters($depositBase, $filters);

            $depositTotal = (clone $depositBase)->where('enable', 'Y');
            if ($this->hasColumn('bank_payment', 'status')) {
                $depositTotal->whereNotIn('status', [2, 3]);
            }

            $depositTotalAmount = (float) (clone $depositTotal)->sum('value');
            $depositTotalCount = (int) (clone $depositTotal)->count();
            $depositTotalUsers = (int) (clone $depositTotal)
                ->whereNotNull('member_topup')
                ->where('member_topup', '>', 0)
                ->distinct('member_topup')
                ->count('member_topup');

            $depositSuccess = (clone $depositBase)->where('enable', 'Y')->where('status', 1);
            $depositSuccessAmount = (float) (clone $depositSuccess)->sum('value');
            $depositSuccessCount = (int) (clone $depositSuccess)->count();
            $depositSuccessUsers = (int) (clone $depositSuccess)
                ->whereNotNull('member_topup')
                ->where('member_topup', '>', 0)
                ->distinct('member_topup')
                ->count('member_topup');

            $depositPending = (clone $depositBase)->where('enable', 'Y')->where('status', 0);
            $depositPendingAmount = (float) (clone $depositPending)->sum('value');
            $depositPendingCount = (int) (clone $depositPending)->count();
            $depositPendingUsers = (int) (clone $depositPending)
                ->whereNotNull('member_topup')
                ->where('member_topup', '>', 0)
                ->distinct('member_topup')
                ->count('member_topup');

            $depositReject = (clone $depositBase)->where('enable', 'Y')->where('status', 2);
            $depositRejectAmount = (float) (clone $depositReject)->sum('value');
            $depositRejectCount = (int) (clone $depositReject)->count();
            $depositRejectUsers = (int) (clone $depositReject)
                ->whereNotNull('member_topup')
                ->where('member_topup', '>', 0)
                ->distinct('member_topup')
                ->count('member_topup');

            $depositDeleted = (clone $depositBase)->where(function ($query) {
                $query->where('enable', '<>', 'Y')->orWhere('status', 3);
            });
            $depositDeletedAmount = (float) (clone $depositDeleted)->sum('value');
            $depositDeletedCount = (int) (clone $depositDeleted)->count();
            $depositDeletedUsers = (int) (clone $depositDeleted)
                ->whereNotNull('member_topup')
                ->where('member_topup', '>', 0)
                ->distinct('member_topup')
                ->count('member_topup');

            // ตามนิยามล่าสุด: "ฝากมีปัญหา" ให้นับเฉพาะ pending (status=0, enable='Y')
            $depositProblemAmount = $depositPendingAmount;
            $depositProblemCount = $depositPendingCount;
            $depositProblemUsers = $depositPendingUsers;

            $withdrawTotals = $this->withdrawTotals($filters, 'complete', 'date_approve', $startDate, $endDate);
            $withdrawAmount = (float) $withdrawTotals['amount'];
            $withdrawCount = (int) $withdrawTotals['count'];
            $withdrawUsers = (int) $withdrawTotals['users'];
            $withdrawMainTotals = $this->withdrawTotals($filters, 'complete', 'date_approve', $startDate, $endDate, 'main');
            $withdrawMainAmount = (float) $withdrawMainTotals['amount'];
            $withdrawMainCount = (int) $withdrawMainTotals['count'];
            $withdrawMainUsers = (int) $withdrawMainTotals['users'];
            $withdrawFreeTotals = $this->withdrawTotals($filters, 'complete', 'date_approve', $startDate, $endDate, 'free');
            $withdrawFreeAmount = (float) $withdrawFreeTotals['amount'];
            $withdrawFreeCount = (int) $withdrawFreeTotals['count'];
            $withdrawFreeUsers = (int) $withdrawFreeTotals['users'];

            $withdrawPendingTotals = $this->withdrawTotals($filters, 'waiting', 'date_create', $startDate, $endDate);
            $withdrawPendingAmount = (float) $withdrawPendingTotals['amount'];
            $withdrawPendingCount = (int) $withdrawPendingTotals['count'];
            $withdrawMainPendingTotals = $this->withdrawTotals($filters, 'waiting', 'date_create', $startDate, $endDate, 'main');
            $withdrawMainPendingAmount = (float) $withdrawMainPendingTotals['amount'];
            $withdrawMainPendingCount = (int) $withdrawMainPendingTotals['count'];
            $withdrawFreePendingTotals = $this->withdrawTotals($filters, 'waiting', 'date_create', $startDate, $endDate, 'free');
            $withdrawFreePendingAmount = (float) $withdrawFreePendingTotals['amount'];
            $withdrawFreePendingCount = (int) $withdrawFreePendingTotals['count'];

            $bonus = $this->bonusTotals($filters, $startDate, $endDate);
            $lotto = $this->lottoCashMetrics($startDate, $endDate);
            $lottoProduct = $this->lottoProductSummaryMetrics($startDate, $endDate);
            $lottoRisk = $this->lottoRiskSummaryMetrics($startDate, $endDate);
            $lottoBetTypeInsights = $this->lottoBetTypeInsightsSummary($startDate, $endDate);

            $net = $depositSuccessAmount - $withdrawAmount + (float) ($lotto['net_cash'] ?? 0);
            $prevNet = $this->netCashflow($filters, $prevStart, $prevEnd);
            $netChangePct = $this->pctChange($prevNet, $net);

            $register = $this->registerTotals($filters, $startDate, $endDate);

            $firstDepositCount = $this->firstDepositCount($filters, $startDate, $endDate);
            $ftdRate = $register['total'] > 0 ? round(($firstDepositCount / $register['total']) * 100, 2) : 0;

            $bonusRatio = $depositSuccessAmount > 0 ? round(($bonus['amount'] / $depositSuccessAmount) * 100, 2) : 0;

            return [
                'deposit' => [
                    'amount' => core()->currency($depositTotalAmount),
                    'amount_raw' => $depositTotalAmount,
                    'count' => $depositTotalCount,
                    'users' => $depositTotalUsers,
                    'total' => [
                        'amount' => core()->currency($depositTotalAmount),
                        'amount_raw' => $depositTotalAmount,
                        'count' => $depositTotalCount,
                        'users' => $depositTotalUsers,
                    ],
                    'success' => [
                        'amount' => core()->currency($depositSuccessAmount),
                        'amount_raw' => $depositSuccessAmount,
                        'count' => $depositSuccessCount,
                        'users' => $depositSuccessUsers,
                    ],
                    'pending' => [
                        'amount' => core()->currency($depositPendingAmount),
                        'amount_raw' => $depositPendingAmount,
                        'count' => $depositPendingCount,
                        'users' => $depositPendingUsers,
                    ],
                    'reject' => [
                        'amount' => core()->currency($depositRejectAmount),
                        'amount_raw' => $depositRejectAmount,
                        'count' => $depositRejectCount,
                        'users' => $depositRejectUsers,
                    ],
                    'deleted' => [
                        'amount' => core()->currency($depositDeletedAmount),
                        'amount_raw' => $depositDeletedAmount,
                        'count' => $depositDeletedCount,
                        'users' => $depositDeletedUsers,
                    ],
                    'problem' => [
                        'amount' => core()->currency($depositProblemAmount),
                        'amount_raw' => $depositProblemAmount,
                        'count' => $depositProblemCount,
                        'users' => $depositProblemUsers,
                    ],
                ],
                'withdraw' => [
                    'amount' => core()->currency($withdrawAmount),
                    'amount_raw' => $withdrawAmount,
                    'count' => $withdrawCount,
                    'users' => $withdrawUsers,
                    'pending' => [
                        'amount' => core()->currency($withdrawPendingAmount),
                        'amount_raw' => $withdrawPendingAmount,
                        'count' => $withdrawPendingCount,
                    ],
                    'main' => [
                        'amount' => core()->currency($withdrawMainAmount),
                        'amount_raw' => $withdrawMainAmount,
                        'count' => $withdrawMainCount,
                        'users' => $withdrawMainUsers,
                        'pending' => [
                            'amount' => core()->currency($withdrawMainPendingAmount),
                            'amount_raw' => $withdrawMainPendingAmount,
                            'count' => $withdrawMainPendingCount,
                        ],
                    ],
                    'free' => [
                        'amount' => core()->currency($withdrawFreeAmount),
                        'amount_raw' => $withdrawFreeAmount,
                        'count' => $withdrawFreeCount,
                        'users' => $withdrawFreeUsers,
                        'pending' => [
                            'amount' => core()->currency($withdrawFreePendingAmount),
                            'amount_raw' => $withdrawFreePendingAmount,
                            'count' => $withdrawFreePendingCount,
                        ],
                    ],
                ],
                'bonus' => [
                    'amount' => core()->currency($bonus['amount']),
                    'amount_raw' => $bonus['amount'],
                    'count' => $bonus['count'],
                    'ratio' => $bonusRatio,
                    'deposit' => [
                        'amount' => core()->currency($bonus['deposit_amount'] ?? 0),
                        'amount_raw' => (float) ($bonus['deposit_amount'] ?? 0),
                        'count' => (int) ($bonus['deposit_count'] ?? 0),
                    ],
                    'activity' => [
                        'amount' => core()->currency($bonus['activity_amount'] ?? 0),
                        'amount_raw' => (float) ($bonus['activity_amount'] ?? 0),
                        'count' => (int) ($bonus['activity_count'] ?? 0),
                    ],
                    'manual' => [
                        'amount' => core()->currency($bonus['manual_amount'] ?? 0),
                        'amount_raw' => (float) ($bonus['manual_amount'] ?? 0),
                        'count' => (int) ($bonus['manual_count'] ?? 0),
                    ],
                ],
                'lotto' => [
                    'sales_cash' => core()->currency((float) ($lotto['sales_cash'] ?? 0)),
                    'sales_cash_raw' => (float) ($lotto['sales_cash'] ?? 0),
                    'payout_cash' => core()->currency((float) ($lotto['payout_cash'] ?? 0)),
                    'payout_cash_raw' => (float) ($lotto['payout_cash'] ?? 0),
                    'refund_cash' => core()->currency((float) ($lotto['refund_cash'] ?? 0)),
                    'refund_cash_raw' => (float) ($lotto['refund_cash'] ?? 0),
                    'net_cash' => core()->currency((float) ($lotto['net_cash'] ?? 0)),
                    'net_cash_raw' => (float) ($lotto['net_cash'] ?? 0),
                ],
                'lotto_product' => [
                    'total_sales' => core()->currency((float) ($lottoProduct['total_sales'] ?? 0)),
                    'total_sales_raw' => (float) ($lottoProduct['total_sales'] ?? 0),
                    'total_payout' => core()->currency((float) ($lottoProduct['total_payout'] ?? 0)),
                    'total_payout_raw' => (float) ($lottoProduct['total_payout'] ?? 0),
                    'total_tickets' => (int) ($lottoProduct['total_tickets'] ?? 0),
                    'total_players' => (int) ($lottoProduct['total_players'] ?? 0),
                    'win_tickets' => (int) ($lottoProduct['win_tickets'] ?? 0),
                    'lose_tickets' => (int) ($lottoProduct['lose_tickets'] ?? 0),
                    'pending_tickets' => (int) ($lottoProduct['pending_tickets'] ?? 0),
                    'settled_tickets' => (int) ($lottoProduct['settled_tickets'] ?? 0),
                ],
                'lotto_risk' => [
                    'markets' => (int) ($lottoRisk['markets'] ?? 0),
                    'rounds' => (int) ($lottoRisk['rounds'] ?? 0),
                    'numbers' => (int) ($lottoRisk['numbers'] ?? 0),
                    'exposure_total' => core()->currency((float) ($lottoRisk['exposure_total'] ?? 0)),
                    'exposure_total_raw' => (float) ($lottoRisk['exposure_total'] ?? 0),
                    'liability_total' => core()->currency((float) ($lottoRisk['liability_total'] ?? 0)),
                    'liability_total_raw' => (float) ($lottoRisk['liability_total'] ?? 0),
                    'liability_max' => core()->currency((float) ($lottoRisk['liability_max'] ?? 0)),
                    'liability_max_raw' => (float) ($lottoRisk['liability_max'] ?? 0),
                    'last_snapshot_at' => (string) ($lottoRisk['last_snapshot_at'] ?? ''),
                ],
                'lotto_bet_type_insights' => $lottoBetTypeInsights,
                'net' => [
                    'amount' => core()->currency($net),
                    'amount_raw' => $net,
                    'change_pct' => $netChangePct,
                ],
                'register' => [
                    'total' => $register['total'],
                    'normal' => $register['normal'],
                    'referral' => $register['referral'],
                    'campaign' => $register['campaign'],
                ],
                'first_deposit' => [
                    'count' => $firstDepositCount,
                    'rate' => $ftdRate,
                ],
            ];
        });
    }

    public function getConversion(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        if (
            $this->hasTable('dashboard_summary_daily')
            && empty($filters['register_channel'])
            && empty($filters['deposit_channel'])
        ) {
            return $this->getConversionFromSummaryTable($filters);
        }

        return Cache::remember($this->cacheKey('conversion', $filters), now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($filters) {
            [$startDate, $endDate] = $this->range($filters);
            $dateColumn = $this->memberDateColumn();

            $registerTotalQuery = $this->memberQuery($filters);
            $this->applyDateTimeWindow($registerTotalQuery, $dateColumn, $startDate, $endDate);
            $registerTotal = $registerTotalQuery->count();

            $registerDepositQuery = $this->memberQuery($filters)
                ->whereHas('payment', function ($query) use ($startDate, $endDate) {
                    $query->where('status', 1)->where('enable', 'Y')
                        ->where('value', '>', 0);
                    $this->applyDateTimeWindow($query, 'date_create', $startDate, $endDate);
                });
            $this->applyDateTimeWindow($registerDepositQuery, $dateColumn, $startDate, $endDate);
            $registerDeposit = $registerDepositQuery->count();
            $registerRepeatDeposit = $this->registerRepeatDepositCount($filters, $startDate, $endDate);

            $registerNotDeposit = max(0, $registerTotal - $registerDeposit);
            $registerRate = $registerTotal > 0 ? round(($registerDeposit / $registerTotal) * 100, 2) : 0;

            $referralTotal = 0;
            $referralDeposit = 0;
            if ($this->hasColumn('members', 'upline_code')) {
                $referralTotalQuery = $this->memberQuery($filters)
                    ->where('upline_code', '>', 0);
                $this->applyDateTimeWindow($referralTotalQuery, $dateColumn, $startDate, $endDate);

                if ($this->hasColumn('members', 'campaign_id')) {
                    $referralTotalQuery->where(function ($query) {
                        $query->whereNull('campaign_id')->orWhere('campaign_id', 0);
                    });
                }

                $referralTotal = $referralTotalQuery->count();

                $referralDepositQuery = $this->memberQuery($filters)
                    ->where('upline_code', '>', 0)
                    ->whereHas('payment', function ($query) use ($startDate, $endDate) {
                        $query->where('status', 1)->where('enable', 'Y')
                            ->where('value', '>', 0);
                        $this->applyDateTimeWindow($query, 'date_create', $startDate, $endDate);
                    });
                $this->applyDateTimeWindow($referralDepositQuery, $dateColumn, $startDate, $endDate);

                if ($this->hasColumn('members', 'campaign_id')) {
                    $referralDepositQuery->where(function ($query) {
                        $query->whereNull('campaign_id')->orWhere('campaign_id', 0);
                    });
                }

                $referralDeposit = $referralDepositQuery->count();
            }

            $referralNotDeposit = max(0, $referralTotal - $referralDeposit);
            $referralRate = $referralTotal > 0 ? round(($referralDeposit / $referralTotal) * 100, 2) : 0;

            $staffMain = $this->staffAdjustMetricsByRepository(
                'Gametech\\Member\\Repositories\\MemberCreditLogRepository',
                $filters,
                $startDate,
                $endDate
            );
            $staffFree = $this->staffAdjustMetricsByRepository(
                'Gametech\\Member\\Repositories\\MemberCreditFreeLogRepository',
                $filters,
                $startDate,
                $endDate,
                'members_credit_free_log'
            );
            $addAmount = (float) $staffMain['add_raw'] + (float) $staffFree['add_raw'];
            $reduceAmount = (float) $staffMain['reduce_raw'] + (float) $staffFree['reduce_raw'];
            $adjustCount = (int) $staffMain['count'] + (int) $staffFree['count'];

            return [
                'register' => [
                    'total' => $registerTotal,
                    'deposit' => $registerDeposit,
                    'repeat_deposit' => $registerRepeatDeposit,
                    'not_deposit' => $registerNotDeposit,
                    'rate' => $registerRate,
                ],
                'referral' => [
                    'total' => $referralTotal,
                    'deposit' => $referralDeposit,
                    'not_deposit' => $referralNotDeposit,
                    'rate' => $referralRate,
                ],
                'staff' => [
                    'add' => core()->currency($addAmount),
                    'add_raw' => $addAmount,
                    'reduce' => core()->currency($reduceAmount),
                    'reduce_raw' => $reduceAmount,
                    'net' => core()->currency($addAmount - $reduceAmount),
                    'net_raw' => $addAmount - $reduceAmount,
                    'count' => $adjustCount,
                    'main' => $staffMain,
                    'free' => $staffFree,
                ],
            ];
        });
    }

    public function getTrends(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        if (
            $this->hasTable('dashboard_summary_daily')
            && ($filters['trend_mode'] ?? 'day') !== 'hour'
            && empty($filters['register_channel'])
            && empty($filters['deposit_channel'])
        ) {
            [$startDate, $endDate] = $this->range($filters);

            return $this->getDailyTrendsFromSummaryTable($startDate, $endDate);
        }

        return Cache::remember($this->cacheKey('trends', $filters), now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($filters) {
            [$startDate, $endDate] = $this->range($filters);
            $mode = $filters['trend_mode'] === 'hour' ? 'hour' : 'day';

            if ($mode === 'hour') {
                $labels = collect(range(0, 23))->map(fn ($h) => str_pad((string) $h, 2, '0', STR_PAD_LEFT))->values();

                $depositQuery = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
                    ->income();
                $this->applyDateTimeWindow($depositQuery, 'date_create', $startDate, $endDate);
                $depositQuery = $this->applyPaymentFilters($depositQuery, $filters);
                $depositQuery->where('enable', 'Y');
                if ($this->hasColumn('bank_payment', 'status')) {
                    $depositQuery->whereNotIn('status', [2, 3]);
                }

                $depositData = $depositQuery
                    ->selectRaw('HOUR(date_create) as h, SUM(value) as v')
                    ->groupBy('h')->pluck('v', 'h')->toArray();

                $withdrawData = $this->withdrawTrendByHour($filters, $startDate, $endDate);

                $bonusData = $this->bonusTrendsByHour($filters, $startDate, $endDate);

                $deposit = $labels->map(fn ($h) => (float) ($depositData[(int) $h] ?? 0))->values();
                $withdraw = $labels->map(fn ($h) => (float) ($withdrawData[(int) $h] ?? 0))->values();
                $bonus = $labels->map(fn ($h) => (float) ($bonusData[(int) $h] ?? 0))->values();

                return [
                    'mode' => 'hour',
                    'labels' => $labels,
                    'deposit' => $deposit,
                    'withdraw' => $withdraw,
                    'bonus' => $bonus,
                ];
            }

            $dateArr = core()->generateDateRange($startDate, $endDate);

            $depositQuery = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
                ->income();
            $this->applyDateTimeWindow($depositQuery, 'date_create', $startDate, $endDate);
            $depositQuery = $this->applyPaymentFilters($depositQuery, $filters);
            $depositQuery->where('enable', 'Y');
            if ($this->hasColumn('bank_payment', 'status')) {
                $depositQuery->whereNotIn('status', [2, 3]);
            }

            $depositRows = $depositQuery
                ->selectRaw("DATE_FORMAT(date_create,'%Y-%m-%d') as d, SUM(value) as v")
                ->groupBy('d')->pluck('v', 'd')->toArray();

            $withdrawRows = $this->withdrawTrendByDay($filters, $startDate, $endDate);

            $bonusRows = $this->bonusTrendsByDay($filters, $startDate, $endDate);

            $labels = [];
            $deposit = [];
            $withdraw = [];
            $bonus = [];

            foreach ($dateArr as $dt) {
                $labels[] = core()->Date($dt, 'd M');
                $deposit[] = (float) ($depositRows[$dt] ?? 0);
                $withdraw[] = (float) ($withdrawRows[$dt] ?? 0);
                $bonus[] = (float) ($bonusRows[$dt] ?? 0);
            }

            return [
                'mode' => 'day',
                'labels' => $labels,
                'deposit' => $deposit,
                'withdraw' => $withdraw,
                'bonus' => $bonus,
            ];
        });
    }

    public function getActivity(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        return Cache::remember($this->cacheKey('activity', $filters), now()->addSeconds(self::ACTIVITY_CACHE_TTL_SECONDS), function () use ($filters) {
            [$startDate, $endDate] = $this->range($filters);
            $dateColumn = $this->memberDateColumn();
            $depositCountColumn = $this->memberDepositCountColumn();
            $lottoMarketId = (int) Arr::get($filters, 'lotto_market_id', 0);

            $depositBase = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
                ->income()->active()->whereIn('status', [0, 1]);
            $this->applyDateTimeWindow($depositBase, 'date_create', $startDate, $endDate);
            $depositBase = $this->applyPaymentFilters($depositBase, $filters);

            $mapDeposit = function ($row) {
                $customerBank = $this->bankInfo(
                    optional($row->member)->bank,
                    optional($row->member)->acc_no ?? null,
                    $row->bankname ?? $row->bank ?? ''
                );
                $receiveBank = $this->bankInfo(
                    optional(optional($row->bank_account)->bank),
                    optional($row->bank_account)->acc_no ?? null
                );
                $admin = $row->admin ?? null;
                $staffName = ((int) ($row->emp_topup ?? 0) === 0)
                    ? ((string) ($row->create_by ?: $row->topup_by ?: '-'))
                    : ((string) ($admin?->user_name ?? $admin?->name ?? $admin?->nickname ?? '-'));

                return [
                    'time' => optional($row->date_create)->format('Y-m-d H:i'),
                    'username' => optional($row->member)->user_name ?? '-',
                    'amount' => core()->currency($row->value),
                    'from_bank' => $customerBank['name'],
                    'from_bank_name' => $customerBank['name'],
                    'from_bank_logo' => $customerBank['logo'],
                    'from_account_no' => $customerBank['account'],
                    'to_bank' => $receiveBank['name'],
                    'to_bank_name' => $receiveBank['name'],
                    'to_bank_logo' => $receiveBank['logo'],
                    'to_account_no' => $receiveBank['account'],
                    'channel' => $row->channel ?: '-',
                    'staff' => $staffName,
                    'status' => $row->status == 1 ? 'สำเร็จ' : 'รอ',
                ];
            };

            $depositQuery = (clone $depositBase)
                ->orderBy('date_create', 'desc')
                ->take(100)
                ->with(['member.bank', 'bank_account.bank', 'admin']);

            $depositManualQuery = (clone $depositBase)
                ->where('channel', 'MANUAL')
                ->orderBy('date_create', 'desc')
                ->take(100)
                ->with(['member.bank', 'bank_account.bank', 'admin']);

            $deposits = $depositQuery->get()->map($mapDeposit);
            $depositsManual = $depositManualQuery->get()->map($mapDeposit);

            $config = core()->getConfigData();
            $withdrawQueries = [];
            if ($config->seamless == 'Y') {
                $withdrawQueries[] = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')->active();
                if (($config->freecredit_open ?? 'N') == 'Y') {
                    $withdrawQueries[] = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessFreeRepository')->active();
                }
            } else {
                $withdrawQueries[] = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->active();
                if (($config->freecredit_open ?? 'N') == 'Y') {
                    $withdrawQueries[] = app('Gametech\\Payment\\Repositories\\WithdrawFreeRepository')->active();
                }
            }

            $withdrawRows = collect();
            foreach ($withdrawQueries as $withdrawQuery) {
                $withdrawQuery->whereIn('status', [0, 1]);
                $withdrawQuery = $this->applyMemberRelationFilters($withdrawQuery, $filters);
                $withdrawQuery
                    ->orderByRaw('COALESCE(date_approve, date_create, date_update) DESC')
                    ->take(10);
                $this->applyDateTimeWindow($withdrawQuery, 'date_create', $startDate, $endDate);
                $withdrawQuery->with(['bank_tran.bank', 'bank', 'member.bank']);

                $rows = $withdrawQuery->get()->map(function ($row) {
                    $timeValue = $row->date_approve ?? $row->date_create ?? $row->date_update;
                    $timeText = '-';
                    $sortAt = 0;
                    if (!empty($timeValue) && !in_array($timeValue, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
                        try {
                            $parsedTime = Carbon::parse($timeValue);
                            $timeText = $parsedTime->format('Y-m-d H:i');
                            $sortAt = $parsedTime->getTimestamp();
                        } catch (\Throwable $e) {
                            $timeText = '-';
                        }
                    }

                    $withdrawBank = $this->bankInfo(
                        optional(optional($row->bank_tran)->bank),
                        optional($row->bank_tran)->acc_no ?? ($row->bankout ?? null),
                        $row->bankout ?? ''
                    );
                    $customerBank = $this->bankInfo(
                        $row->bank ?? optional($row->member)->bank,
                        optional($row->member)->acc_no ?? null
                    );

                    return [
                        '__sort_at' => $sortAt,
                        'time' => $timeText,
                        'username' => $row->member_user ?: (optional($row->member)->user_name ?? '-'),
                        'amount' => core()->currency($row->amount),
                        'from_bank' => $withdrawBank['name'],
                        'from_bank_name' => $withdrawBank['name'],
                        'from_bank_logo' => $withdrawBank['logo'],
                        'from_account_no' => $withdrawBank['account'],
                        'to_bank' => $customerBank['name'],
                        'to_bank_name' => $customerBank['name'],
                        'to_bank_logo' => $customerBank['logo'],
                        'to_account_no' => $customerBank['account'],
                        'status' => $row->status == 1 ? 'สำเร็จ' : 'รอ',
                    ];
                });

                $withdrawRows = $withdrawRows->concat($rows);
            }

            $withdraws = $withdrawRows
                ->sortByDesc('__sort_at')
                ->take(10)
                ->values()
                ->map(function ($row) {
                    unset($row['__sort_at']);

                    return $row;
                });

            $registerQuery = $this->memberQuery($filters)
                ->orderBy($dateColumn, 'desc')
                ->take(10);
            $this->applyDateTimeWindow($registerQuery, $dateColumn, $startDate, $endDate);
            $registers = $registerQuery->get()
                ->map(function ($row) use ($depositCountColumn, $dateColumn) {
                    $source = $this->resolveRegisterChannel($row);
                    $depositCount = $depositCountColumn ? (int) ($row->{$depositCountColumn} ?? 0) : 0;
                    $deposited = $depositCount > 0 ? 'ฝากแล้ว' : 'ยังไม่ฝาก';
                    $timeText = '-';
                    $timeValue = $row->{$dateColumn} ?? null;
                    if (!empty($timeValue) && !in_array($timeValue, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
                        try {
                            $timeText = Carbon::parse($timeValue)->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $timeText = '-';
                        }
                    }
                    return [
                        'time' => $timeText,
                        'username' => $row->user_name ?? '-',
                        'source' => $source,
                        'status' => $deposited,
                    ];
                });

            $staffQueries = [
                app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->active()->where('kind', 'SETWALLET'),
            ];
            if (($config->freecredit_open ?? 'N') == 'Y' && $this->hasTable('members_credit_free_log')) {
                $staffQueries[] = app('Gametech\\Member\\Repositories\\MemberCreditFreeLogRepository')
                    ->active()
                    ->where('kind', 'SETWALLET');
            }

            $staffRows = collect();
            foreach ($staffQueries as $staffQuery) {
                $staffQuery
                    ->orderBy('date_create', 'desc')
                    ->take(10);
                $this->applyDateTimeWindow($staffQuery, 'date_create', $startDate, $endDate);
                $staffQuery = $this->applyMemberRelationFilters($staffQuery, $filters);

                $rows = $staffQuery->get()->map(function ($row) {
                    $sortAt = 0;
                    $time = '-';
                    if (!empty($row->date_create)) {
                        try {
                            $parsedTime = Carbon::parse($row->date_create);
                            $time = $parsedTime->format('Y-m-d H:i');
                            $sortAt = $parsedTime->getTimestamp();
                        } catch (\Throwable $e) {
                            $time = '-';
                        }
                    }

                    return [
                        '__sort_at' => $sortAt,
                        'time' => $time,
                        'staff' => optional($row->admin)->user_name ?? $row->user_create ?? '-',
                        'member' => optional($row->member)->user_name ?? '-',
                        'type' => $row->credit_type === 'D' ? 'เพิ่ม' : 'ลด',
                        'amount' => core()->currency($row->amount),
                    ];
                });

                $staffRows = $staffRows->concat($rows);
            }

            $staff = $staffRows
                ->sortByDesc('__sort_at')
                ->take(10)
                ->values()
                ->map(function ($row) {
                    unset($row['__sort_at']);

                    return $row;
                });

            $lottoRecentBets = $this->getRecentLottoBetsActivity(20, $lottoMarketId > 0 ? $lottoMarketId : null);

            return [
                'deposits' => $deposits,
                'deposits_manual' => $depositsManual,
                'withdraws' => $withdraws,
                'registers' => $registers,
                'staff' => $staff,
                'lotto_recent_bets' => $lottoRecentBets,
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRecentLottoBetsActivity(int $limit = 20, ?int $marketId = null): array
    {
        $limit = max(1, min($limit, 20));

        if (
            !$this->hasTable('lotto_tickets')
            || !$this->hasTable('lotto_draws')
            || !$this->hasTable('lotto_markets')
            || !$this->hasTable('lotto_groups')
        ) {
            return [];
        }

        $query = DB::table('lotto_tickets as t')
            ->join('lotto_draws as d', 'd.id', '=', 't.draw_id')
            ->join('lotto_markets as m', 'm.id', '=', 'd.market_id')
            ->join('lotto_groups as g', 'g.id', '=', 'm.group_id')
            ->select([
                't.id',
                't.member_id',
                't.status',
                't.total_net_amount',
                't.total_win_amount',
                't.created_at',
                'm.name as market_name',
                'g.name as group_name',
            ])
            ->selectRaw(
                ($this->hasColumn('lotto_tickets', 'bet_type_summary') ? 't.bet_type_summary' : "''")
                . ' as bet_type_summary'
            )
            ->orderByDesc('t.created_at')
            ->orderByDesc('t.id');

        if ($marketId !== null && $marketId > 0) {
            $query->where('d.market_id', $marketId);
        }

        $rows = $query->limit($limit)->get();

        return $rows->map(function ($row): array {
            $time = '-';
            if (!empty($row->created_at)) {
                try {
                    $time = Carbon::parse($row->created_at)->format('Y-m-d H:i');
                } catch (\Throwable $e) {
                    $time = '-';
                }
            }

            $statusRaw = (string) ($row->status ?? '');
            $winAmount = (float) ($row->total_win_amount ?? 0);
            $status = 'pending';
            if ($statusRaw === 'cancelled') {
                $status = 'cancel';
            } elseif ($statusRaw === 'resulted') {
                $status = $winAmount > 0 ? 'win' : 'lose';
            }

            return [
                'ticket_id' => (int) ($row->id ?? 0),
                'bet_at' => $time,
                'member_code' => (string) ($row->member_id ?? '-'),
                'group_name' => (string) ($row->group_name ?? '-'),
                'market_name' => (string) ($row->market_name ?? '-'),
                'bet_type_summary' => (string) ($row->bet_type_summary ?: '-'),
                'amount' => core()->currency((float) ($row->total_net_amount ?? 0)),
                'status' => $status,
                'win_amount' => core()->currency($winAmount),
            ];
        })->values()->all();
    }

    public function getFunnel(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        if (
            $this->hasTable('dashboard_summary_daily')
            && empty($filters['register_channel'])
            && empty($filters['deposit_channel'])
        ) {
            return $this->getFunnelFromSummaryTable($filters);
        }

        return Cache::remember($this->cacheKey('funnel', $filters), now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($filters) {
            [$startDate, $endDate] = $this->range($filters);
            $dateColumn = $this->memberDateColumn();

            $registerQuery = $this->memberQuery($filters);
            $this->applyDateTimeWindow($registerQuery, $dateColumn, $startDate, $endDate);
            $register = $registerQuery->count();

            $registerDeposit = $this->registerDepositCount($filters, $startDate, $endDate);
            $registerRepeatDeposit = $this->registerRepeatDepositCount($filters, $startDate, $endDate);

            $confirmed = 0;
            if ($this->hasColumn('members', 'confirm')) {
                $confirmedQuery = $this->memberQuery($filters)
                    ->whereIn('confirm', ['Y', '1', 1]);
                $this->applyDateTimeWindow($confirmedQuery, $dateColumn, $startDate, $endDate);
                $confirmed = $confirmedQuery->count();
            }

            $ftd = $this->firstDepositCount($filters, $startDate, $endDate);

            $repeatDeposit = $this->repeatDepositCount($filters, $startDate, $endDate);

            $sources = $this->sourceBreakdown($filters, $startDate, $endDate);

            return [
                'funnel' => [
                    'register' => $register,
                    'register_deposit' => $registerDeposit,
                    'register_repeat_deposit' => $registerRepeatDeposit,
                    'confirmed' => $confirmed,
                    'first_deposit' => $ftd,
                    'repeat_deposit' => $repeatDeposit,
                ],
                'sources' => $sources,
            ];
        });
    }

    public function getAlerts(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        return Cache::remember($this->cacheKey('alerts', $filters), now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($filters) {
            $alerts = [];
            $thresholdMinutes = 30;

            $withdrawPending = $this->withdrawPendingCountOlderThan($filters, now()->subMinutes($thresholdMinutes));
            if ($withdrawPending > 0) {
                $alerts[] = [
                    'code' => 'withdraw_pending_timeout',
                    'level' => 'danger',
                    'title' => 'ถอนรอดำเนินการเกินเวลา',
                    'message' => "มีรายการถอนค้างเกิน {$thresholdMinutes} นาที: {$withdrawPending} รายการ",
                ];
            }

            $depositPending = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
                ->waiting()->active()
                ->where('date_create', '<', now()->subMinutes($thresholdMinutes));
            $depositPending = $this->applyPaymentFilters($depositPending, $filters);
            $depositPendingCount = $depositPending->count();
            if ($depositPendingCount > 0) {
                $alerts[] = [
                    'code' => 'deposit_pending_timeout',
                    'level' => 'warning',
                    'title' => 'ฝากค้าง match',
                    'message' => "มีรายการฝากค้างเกิน {$thresholdMinutes} นาที: {$depositPendingCount} รายการ",
                ];
            }

            $summary = $this->getSummary($filters);
            if ($summary['bonus']['ratio'] >= 30) {
                $alerts[] = [
                    'code' => 'bonus_ratio_high',
                    'level' => 'warning',
                    'title' => 'โบนัสผิดปกติ',
                    'message' => "โบนัส/ฝากสูง {$summary['bonus']['ratio']}%",
                ];
            }

            $conversion = $this->getConversion($filters);
            $netAdjust = (float) ($conversion['staff']['net_raw'] ?? 0);
            if (abs($netAdjust) >= 10000) {
                $alerts[] = [
                    'code' => 'staff_adjustment_high',
                    'level' => $netAdjust >= 0 ? 'warning' : 'danger',
                    'title' => 'staff adjustment สูงผิดปกติ',
                    'message' => "ปรับยอดสุทธิสูง: {$conversion['staff']['net']}",
                ];
            }

            if ($conversion['referral']['total'] >= 20 && $conversion['referral']['rate'] < 30) {
                $alerts[] = [
                    'code' => 'referral_low_conversion',
                    'level' => 'warning',
                    'title' => 'referral สมัครเยอะแต่ไม่ฝาก',
                    'message' => "Conversion ต่ำ {$conversion['referral']['rate']}% จาก {$conversion['referral']['total']} คน",
                ];
            }

            if ($summary['net']['amount_raw'] < 0) {
                $alerts[] = [
                    'code' => 'net_negative_short_range',
                    'level' => 'danger',
                    'title' => 'ยอดถอนสูงกว่าฝากในช่วงสั้น',
                    'message' => "คงเหลือสุทธิเป็นลบ: {$summary['net']['amount']}",
                ];
            }

            return $alerts;
        });
    }

    public function getMemberList(array $filters, string $type): array
    {
        $filters = $this->normalizeFilters($filters);
        $allowedTypes = [
            'register_deposit',
            'register_repeat_deposit',
            'register_not_deposit',
            'first_deposit',
            'repeat_deposit',
            'referral_total',
            'referral_deposit',
            'referral_not_deposit',
        ];
        $type = in_array($type, $allowedTypes, true)
            ? $type
            : 'register_deposit';

        [$startDate, $endDate] = $this->range($filters);
        $limit = (int) Arr::get($filters, 'limit', 200);
        if ($limit <= 0 || $limit > 1000) {
            $limit = 200;
        }

        $memberKey = $this->memberKeyColumn();
        $paymentMemberKey = $this->paymentMemberKeyColumn();
        $paymentKey = $this->paymentKeyColumn();
        $dateColumn = $this->memberDateColumn();
        $paymentDateColumn = $this->paymentDateColumn();
        $isReferralType = in_array($type, ['referral_total', 'referral_deposit', 'referral_not_deposit'], true);

        $query = app('Gametech\\Member\\Repositories\\MemberRepository')->getModel()->newQuery();
        $query = $this->applyMemberFilters($query, $filters);

        $select = [
            'members.' . $memberKey . ' as member_key',
        ];

        $usernameColumn = $this->memberUsernameColumn();
        if ($usernameColumn) {
            $select[] = 'members.' . $usernameColumn . ' as username';
        }
        if ($this->hasColumn('members', 'name')) {
            $select[] = 'members.name';
        }
        if ($this->hasColumn('members', 'firstname')) {
            $select[] = 'members.firstname';
        }
        if ($this->hasColumn('members', 'lastname')) {
            $select[] = 'members.lastname';
        }
        if ($this->hasColumn('members', $dateColumn)) {
            $select[] = 'members.' . $dateColumn . ' as register_at';
        }
        if ($this->hasColumn('members', 'date_create')) {
            $select[] = 'members.date_create as register_date_create';
        }
        if ($this->hasColumn('members', 'tel')) {
            $select[] = 'members.tel';
        }
        if ($this->hasColumn('members', 'upline_code')) {
            $select[] = 'members.upline_code';
        }
        if ($this->hasColumn('members', 'upline_id')) {
            $select[] = 'members.upline_id';
        }
        if ($this->hasColumn('members', 'campaign_id')) {
            $select[] = 'members.campaign_id';
        }

        if ($isReferralType) {
            $inviterJoinColumn = null;
            if ($this->hasColumn('members', 'upline_code')) {
                $inviterJoinColumn = 'upline_code';
            } elseif ($this->hasColumn('members', 'upline_id')) {
                $inviterJoinColumn = 'upline_id';
            }

            if ($inviterJoinColumn) {
                $query->leftJoin('members as inviter', 'inviter.' . $memberKey, '=', 'members.' . $inviterJoinColumn);
                $select[] = DB::raw('inviter.' . $memberKey . ' as inviter_id');

                if ($usernameColumn) {
                    $select[] = DB::raw('inviter.' . $usernameColumn . ' as inviter_username');
                }
                if ($this->hasColumn('members', 'name')) {
                    $select[] = DB::raw('inviter.name as inviter_name');
                }
                if ($this->hasColumn('members', 'firstname')) {
                    $select[] = DB::raw('inviter.firstname as inviter_firstname');
                }
                if ($this->hasColumn('members', 'lastname')) {
                    $select[] = DB::raw('inviter.lastname as inviter_lastname');
                }
            }
        }

        if ($this->hasTable('bank_payment')) {
            $paymentBase = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
                ->income()->active()->where('status', 1);
            $paymentBase = $this->applyPaymentFilters($paymentBase, $filters);

            $paymentStats = clone $paymentBase;
            $statsSub = $paymentStats
                ->selectRaw($paymentMemberKey . ' as member_key, COUNT(*) as deposit_count, SUM(value) as deposit_sum, MAX(date_create) as last_deposit_at')
                ->groupBy($paymentMemberKey)
                ->toBase();

            $paymentFirst = clone $paymentBase;
            $firstSub = $paymentFirst
                ->selectRaw($paymentMemberKey . ' as member_key, MIN(code) as first_code')
                ->groupBy($paymentMemberKey)
                ->toBase();

            $query->leftJoinSub($statsSub, 'ds', function ($join) use ($memberKey) {
                $join->on('members.' . $memberKey, '=', 'ds.member_key');
            });
            $query->leftJoinSub($firstSub, 'fd', function ($join) use ($memberKey) {
                $join->on('members.' . $memberKey, '=', 'fd.member_key');
            });
            $query->leftJoin('bank_payment as bp_first', 'bp_first.' . $paymentKey, '=', 'fd.first_code');

            $select[] = DB::raw('ds.deposit_count as deposit_count');
            $select[] = DB::raw('ds.deposit_sum as deposit_sum');
            $select[] = DB::raw('ds.last_deposit_at as last_deposit_at');
            $select[] = DB::raw('bp_first.date_create as first_deposit_at');
            $select[] = DB::raw('bp_first.value as first_deposit_amount');
        }

        $query->select($select);

        if ($isReferralType) {
            if ($this->hasColumn('members', 'campaign_id')) {
                $query->where(function ($q) {
                    $q->whereNull('members.campaign_id')->orWhere('members.campaign_id', 0);
                });
            }

            if ($this->hasColumn('members', 'upline_code')) {
                $query->where('members.upline_code', '>', 0);
            } elseif ($this->hasColumn('members', 'upline_id')) {
                $query->where('members.upline_id', '>', 0);
            }
        }

        if (in_array($type, ['register_deposit', 'register_repeat_deposit', 'register_not_deposit', 'referral_total', 'referral_deposit', 'referral_not_deposit'], true)) {
            $this->applyDateTimeWindow($query, 'members.' . $dateColumn, $startDate, $endDate);
        }

        if (in_array($type, ['register_deposit', 'register_repeat_deposit', 'referral_deposit'], true)) {
            $query->whereExists(function ($q) use ($startDate, $endDate, $memberKey, $paymentMemberKey, $paymentDateColumn, $filters) {
                $q->select(DB::raw(1))
                    ->from('bank_payment as bp_range')
                    ->whereColumn('bp_range.' . $paymentMemberKey, 'members.' . $memberKey)
                    ->where('bp_range.enable', 'Y')
                    ->where('bp_range.status', 1)
                    ->where('bp_range.value', '>', 0);
                $this->applyDateTimeWindow($q, 'bp_range.' . $paymentDateColumn, $startDate, $endDate);
                if (!empty($filters['deposit_channel']) && $this->hasColumn('bank_payment', 'channel')) {
                    $q->where('bp_range.channel', $filters['deposit_channel']);
                }
            });

            if ($type === 'register_repeat_deposit') {
                $query->whereExists(function ($q) use ($memberKey, $paymentMemberKey, $filters) {
                    $q->select(DB::raw(1))
                        ->from('bank_payment as bp_life')
                        ->whereColumn('bp_life.' . $paymentMemberKey, 'members.' . $memberKey)
                        ->where('bp_life.enable', 'Y')
                        ->where('bp_life.status', 1)
                        ->where('bp_life.value', '>', 0)
                        ->groupBy('bp_life.' . $paymentMemberKey)
                        ->havingRaw('COUNT(*) >= 2');

                    if (!empty($filters['deposit_channel']) && $this->hasColumn('bank_payment', 'channel')) {
                        $q->where('bp_life.channel', $filters['deposit_channel']);
                    }
                });
            }
        } elseif (in_array($type, ['register_not_deposit', 'referral_not_deposit'], true)) {
            $query->whereNotExists(function ($q) use ($startDate, $endDate, $memberKey, $paymentMemberKey, $paymentDateColumn, $filters) {
                $q->select(DB::raw(1))
                    ->from('bank_payment as bp_range')
                    ->whereColumn('bp_range.' . $paymentMemberKey, 'members.' . $memberKey)
                    ->where('bp_range.enable', 'Y')
                    ->where('bp_range.status', 1)
                    ->where('bp_range.value', '>', 0);
                $this->applyDateTimeWindow($q, 'bp_range.' . $paymentDateColumn, $startDate, $endDate);
                if (!empty($filters['deposit_channel']) && $this->hasColumn('bank_payment', 'channel')) {
                    $q->where('bp_range.channel', $filters['deposit_channel']);
                }
            });
        } elseif ($type === 'first_deposit') {
            $this->applyDateTimeWindow($query, 'bp_first.date_create', $startDate, $endDate);
        } elseif ($type === 'repeat_deposit') {
            if (!$this->hasTable('bank_payment')) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereNotNull('ds.deposit_count')
                    ->where('ds.deposit_count', '>=', 2)
                    ->whereExists(function ($q) use ($startDate, $endDate, $memberKey, $paymentMemberKey, $paymentDateColumn, $filters) {
                        $q->select(DB::raw(1))
                            ->from('bank_payment as bp_repeat')
                            ->whereColumn('bp_repeat.' . $paymentMemberKey, 'members.' . $memberKey)
                            ->where('bp_repeat.enable', 'Y')
                            ->where('bp_repeat.status', 1)
                            ->where('bp_repeat.value', '>', 0);
                        $this->applyDateTimeWindow($q, 'bp_repeat.' . $paymentDateColumn, $startDate, $endDate);

                        if (!empty($filters['deposit_channel']) && $this->hasColumn('bank_payment', 'channel')) {
                            $q->where('bp_repeat.channel', $filters['deposit_channel']);
                        }
                    });
            }
        }

        if ($type === 'first_deposit') {
            $query->orderByDesc('bp_first.date_create');
        } elseif (in_array($type, ['repeat_deposit', 'register_repeat_deposit'], true) && $this->hasTable('bank_payment')) {
            $query->orderByDesc('ds.last_deposit_at');
        } else {
            $query->orderByDesc('members.' . $dateColumn);
        }

        $total = (clone $query)->count();
        $rows = $query->limit($limit)->get();

        $items = $rows->map(function ($row) use ($type) {
            $username = $row->username ?? '-';
            $name = $this->memberName($row);
            $registerAt = $this->formatDate($row->register_at ?? null);
            $firstDepositAt = $this->formatDateTime($row->first_deposit_at ?? null);
            $lastDepositAt = $this->formatDateTime($row->last_deposit_at ?? null);
            $depositCount = (int) ($row->deposit_count ?? 0);
            $depositSum = isset($row->deposit_sum) ? core()->currency((float) $row->deposit_sum) : '-';
            $firstDepositAmount = isset($row->first_deposit_amount) ? core()->currency((float) $row->first_deposit_amount) : '-';
            $inviterId = '-';
            $inviterName = '-';
            if (in_array($type, ['referral_total', 'referral_deposit', 'referral_not_deposit'], true)) {
                $inviterId = trim((string) ($row->inviter_username ?? ''));
                if ($inviterId === '') {
                    $inviterId = isset($row->inviter_id) && $row->inviter_id !== null
                        ? (string) $row->inviter_id
                        : '-';
                }

                $inviterName = trim((string) ($row->inviter_name ?? ''));
                if ($inviterName === '') {
                    $inviterFirst = trim((string) ($row->inviter_firstname ?? ''));
                    $inviterLast = trim((string) ($row->inviter_lastname ?? ''));
                    $inviterFullName = trim($inviterFirst . ' ' . $inviterLast);
                    $inviterName = $inviterFullName !== '' ? $inviterFullName : '-';
                }
            }

            if (in_array($type, ['register_not_deposit', 'referral_not_deposit'], true)) {
                $result = [
                    'username' => $username,
                    'name' => $name,
                    'register_at' => $registerAt,
                    'tel' => $row->tel ?? '-',
                    'channel' => $this->resolveRegisterChannel($row),
                    'no_deposit_age' => $this->diffForDisplay($row->register_at ?? null, now()),
                ];

                if ($type === 'referral_not_deposit') {
                    $result['inviter_id'] = $inviterId;
                    $result['inviter_name'] = $inviterName;
                }

                return $result;
            }

            if ($type === 'first_deposit') {
                $registerAtFromCreate = $row->register_date_create ?? $row->register_at ?? null;
                return [
                    'username' => $username,
                    'name' => $name,
                    'register_at' => $this->formatDate($registerAtFromCreate),
                    'first_deposit_at' => $firstDepositAt,
                    'time_to_first' => $this->diffForDisplay($registerAtFromCreate, $row->first_deposit_at ?? null),
                    'first_deposit_amount' => $firstDepositAmount,
                ];
            }

            $result = [
                'username' => $username,
                'name' => $name,
                'register_at' => $registerAt,
                'first_deposit_at' => $firstDepositAt,
                'first_deposit_amount' => $firstDepositAmount,
                'last_deposit_at' => $lastDepositAt,
                'deposit_count' => $depositCount,
                'deposit_sum' => $depositSum,
            ];

            if (in_array($type, ['referral_total', 'referral_deposit'], true)) {
                $result['inviter_id'] = $inviterId;
                $result['inviter_name'] = $inviterName;
            }

            return $result;
        })->values();

        return [
            'type' => $type,
            'total' => $total,
            'limit' => $limit,
            'items' => $items,
        ];
    }

    public function syncSummaryRange(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        [$startDate, $endDate] = $this->range($filters);

        if (!$this->hasTable('dashboard_summary_daily')) {
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'requested_days' => 0,
                'synced_days' => 0,
                'failed_days' => 0,
                'message' => 'dashboard_summary_daily not found',
            ];
        }

        $dateRange = core()->generateDateRange($startDate, $endDate);
        $syncService = app(DashboardSummarySyncService::class);
        $webCode = $this->dashboardWebCode();

        $syncedDays = 0;
        $failed = [];
        foreach ($dateRange as $date) {
            try {
                $syncService->syncBucket(
                    summaryDate: $date,
                    webCode: $webCode,
                    updatedSections: ['deposit', 'withdraw', 'bonus', 'register', 'conversion', 'funnel', 'net', 'lotto_cash', 'lotto_product', 'lotto_risk', 'lotto_operations'],
                    sourceType: 'manual_resync',
                    sourceId: 'dashboard_sync_button'
                );
                $syncedDays++;
            } catch (\Throwable $e) {
                report($e);
                $failed[] = [
                    'summary_date' => $date,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->summaryWarmCache = [];
        $this->forgetDashboardCaches($filters);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'requested_days' => count($dateRange),
            'synced_days' => $syncedDays,
            'failed_days' => count($failed),
            'failed' => $failed,
        ];
    }

    public function getSummarySide(array $filters): array
    {
        $summary = $this->getSummary($filters);

        $avgDeposit = $summary['deposit']['count'] > 0
            ? round($summary['deposit']['amount_raw'] / $summary['deposit']['count'], 2)
            : 0;

        $avgWithdraw = $summary['withdraw']['count'] > 0
            ? round($summary['withdraw']['amount_raw'] / $summary['withdraw']['count'], 2)
            : 0;

        $bonusPerUser = $summary['deposit']['users'] > 0
            ? round($summary['bonus']['amount_raw'] / $summary['deposit']['users'], 2)
            : 0;

        return [
            'avg_deposit' => core()->currency($avgDeposit),
            'avg_withdraw' => core()->currency($avgWithdraw),
            'avg_bonus_per_user' => core()->currency($bonusPerUser),
            'active_deposit_users' => $summary['deposit']['users'],
            'active_withdraw_users' => $summary['withdraw']['users'],
        ];
    }

    private function getSummaryFromSummaryTable(array $filters): array
    {
        [$startDate, $endDate] = $this->range($filters);
        [$prevStart, $prevEnd] = $this->previousRange($filters);
        $this->ensureSummaryRangeReady($startDate, $endDate);
        $this->ensureSummaryRangeReady($prevStart, $prevEnd);

        $current = $this->aggregateSummaryRange($startDate, $endDate);
        $previous = $this->aggregateSummaryRange($prevStart, $prevEnd);

        $depositTotalAmount = (float) ($current['deposit_total_amount'] ?? 0);
        $depositTotalCount = (int) ($current['deposit_total_count'] ?? 0);
        $depositTotalUsers = (int) ($current['deposit_total_users'] ?? 0);

        $depositSuccessAmount = (float) ($current['deposit_success_amount'] ?? 0);
        $depositSuccessCount = (int) ($current['deposit_success_count'] ?? 0);
        $depositSuccessUsers = (int) ($current['deposit_success_users'] ?? 0);

        $depositPendingAmount = (float) ($current['deposit_pending_amount'] ?? 0);
        $depositPendingCount = (int) ($current['deposit_pending_count'] ?? 0);
        $depositPendingUsers = (int) ($current['deposit_pending_users'] ?? 0);

        $depositRejectAmount = (float) ($current['deposit_reject_amount'] ?? 0);
        $depositRejectCount = (int) ($current['deposit_reject_count'] ?? 0);
        $depositRejectUsers = (int) ($current['deposit_reject_users'] ?? 0);

        $depositDeletedAmount = (float) ($current['deposit_deleted_amount'] ?? 0);
        $depositDeletedCount = (int) ($current['deposit_deleted_count'] ?? 0);
        $depositDeletedUsers = (int) ($current['deposit_deleted_users'] ?? 0);

        // ตามนิยามล่าสุด: "ฝากมีปัญหา" ให้นับเฉพาะ pending (status=0, enable='Y')
        $depositProblemAmount = $depositPendingAmount;
        $depositProblemCount = $depositPendingCount;
        $depositProblemUsers = $depositPendingUsers;

        $withdrawAmount = (float) ($current['withdraw_total_amount'] ?? 0);
        $withdrawCount = (int) ($current['withdraw_total_count'] ?? 0);
        $withdrawUsers = (int) ($current['withdraw_total_users'] ?? 0);

        $withdrawPendingAmount = (float) ($current['withdraw_pending_amount'] ?? 0);
        $withdrawPendingCount = (int) ($current['withdraw_pending_count'] ?? 0);
        $withdrawFreeAmount = (float) ($current['withdraw_free_total_amount'] ?? 0);
        $withdrawFreeCount = (int) ($current['withdraw_free_total_count'] ?? 0);
        $withdrawFreeUsers = (int) ($current['withdraw_free_total_users'] ?? 0);
        $withdrawFreePendingAmount = (float) ($current['withdraw_free_pending_amount'] ?? 0);
        $withdrawFreePendingCount = (int) ($current['withdraw_free_pending_count'] ?? 0);
        $hasWithdrawMainTotals = $this->hasColumn('dashboard_summary_daily', 'withdraw_main_total_amount')
            && $this->hasColumn('dashboard_summary_daily', 'withdraw_main_total_count')
            && $this->hasColumn('dashboard_summary_daily', 'withdraw_main_total_users');
        $hasWithdrawMainPending = $this->hasColumn('dashboard_summary_daily', 'withdraw_main_pending_amount')
            && $this->hasColumn('dashboard_summary_daily', 'withdraw_main_pending_count');
        $withdrawMainAmount = $hasWithdrawMainTotals
            ? (float) ($current['withdraw_main_total_amount'] ?? 0)
            : max(0, $withdrawAmount - $withdrawFreeAmount);
        $withdrawMainCount = $hasWithdrawMainTotals
            ? (int) ($current['withdraw_main_total_count'] ?? 0)
            : max(0, $withdrawCount - $withdrawFreeCount);
        $withdrawMainUsers = $hasWithdrawMainTotals
            ? (int) ($current['withdraw_main_total_users'] ?? 0)
            : max(0, $withdrawUsers - $withdrawFreeUsers);
        $withdrawMainPendingAmount = $hasWithdrawMainPending
            ? (float) ($current['withdraw_main_pending_amount'] ?? 0)
            : max(0, $withdrawPendingAmount - $withdrawFreePendingAmount);
        $withdrawMainPendingCount = $hasWithdrawMainPending
            ? (int) ($current['withdraw_main_pending_count'] ?? 0)
            : max(0, $withdrawPendingCount - $withdrawFreePendingCount);

        $bonusDepositAmount = (float) ($current['bonus_deposit_amount'] ?? 0);
        $bonusDepositCount = (int) ($current['bonus_deposit_count'] ?? 0);
        $bonusActivityAmount = (float) ($current['bonus_activity_amount'] ?? 0);
        $bonusActivityCount = (int) ($current['bonus_activity_count'] ?? 0);
        $bonusManualAmount = (float) ($current['bonus_manual_amount'] ?? 0);
        $bonusManualCount = (int) ($current['bonus_manual_count'] ?? 0);
        $bonusAmount = (float) ($current['bonus_total_amount'] ?? 0);
        $bonusCount = (int) ($current['bonus_total_count'] ?? 0);
        $bonusRatio = $depositSuccessAmount > 0 ? round(($bonusAmount / $depositSuccessAmount) * 100, 2) : 0;
        $lottoSalesCash = (float) ($current['lotto_sales_cash'] ?? 0);
        $lottoPayoutCash = (float) ($current['lotto_payout_cash'] ?? 0);
        $lottoRefundCash = (float) ($current['lotto_refund_cash'] ?? 0);
        $lottoNetCash = (float) ($current['lotto_net_cash'] ?? ($lottoSalesCash - $lottoPayoutCash - $lottoRefundCash));
        $lottoProduct = $this->lottoProductSummaryMetrics($startDate, $endDate);
        $lottoRisk = $this->lottoRiskSummaryMetrics($startDate, $endDate);
        $lottoBetTypeInsights = $this->lottoBetTypeInsightsSummary($startDate, $endDate);

        $net = (float) ($current['net_amount'] ?? ($depositSuccessAmount - $withdrawAmount + $lottoNetCash));
        $prevNet = (float) ($previous['net_amount'] ?? 0);
        $netChangePct = $this->pctChange($prevNet, $net);

        $registerTotal = (int) ($current['register_total'] ?? 0);
        $registerDirect = (int) ($current['register_direct'] ?? 0);
        $registerReferral = (int) ($current['register_referral'] ?? 0);
        $registerCampaign = (int) ($current['register_campaign'] ?? 0);

        $firstDepositCount = (int) ($current['first_deposit_count'] ?? 0);
        $ftdRate = $registerTotal > 0 ? round(($firstDepositCount / $registerTotal) * 100, 2) : 0;

        return [
            'deposit' => [
                'amount' => core()->currency($depositTotalAmount),
                'amount_raw' => $depositTotalAmount,
                'count' => $depositTotalCount,
                'users' => $depositTotalUsers,
                'total' => [
                    'amount' => core()->currency($depositTotalAmount),
                    'amount_raw' => $depositTotalAmount,
                    'count' => $depositTotalCount,
                    'users' => $depositTotalUsers,
                ],
                'success' => [
                    'amount' => core()->currency($depositSuccessAmount),
                    'amount_raw' => $depositSuccessAmount,
                    'count' => $depositSuccessCount,
                    'users' => $depositSuccessUsers,
                ],
                'pending' => [
                    'amount' => core()->currency($depositPendingAmount),
                    'amount_raw' => $depositPendingAmount,
                    'count' => $depositPendingCount,
                    'users' => $depositPendingUsers,
                ],
                'reject' => [
                    'amount' => core()->currency($depositRejectAmount),
                    'amount_raw' => $depositRejectAmount,
                    'count' => $depositRejectCount,
                    'users' => $depositRejectUsers,
                ],
                'deleted' => [
                    'amount' => core()->currency($depositDeletedAmount),
                    'amount_raw' => $depositDeletedAmount,
                    'count' => $depositDeletedCount,
                    'users' => $depositDeletedUsers,
                ],
                'problem' => [
                    'amount' => core()->currency($depositProblemAmount),
                    'amount_raw' => $depositProblemAmount,
                    'count' => $depositProblemCount,
                    'users' => $depositProblemUsers,
                ],
            ],
            'withdraw' => [
                'amount' => core()->currency($withdrawAmount),
                'amount_raw' => $withdrawAmount,
                'count' => $withdrawCount,
                'users' => $withdrawUsers,
                'pending' => [
                    'amount' => core()->currency($withdrawPendingAmount),
                    'amount_raw' => $withdrawPendingAmount,
                    'count' => $withdrawPendingCount,
                ],
                'main' => [
                    'amount' => core()->currency($withdrawMainAmount),
                    'amount_raw' => $withdrawMainAmount,
                    'count' => $withdrawMainCount,
                    'users' => $withdrawMainUsers,
                    'pending' => [
                        'amount' => core()->currency($withdrawMainPendingAmount),
                        'amount_raw' => $withdrawMainPendingAmount,
                        'count' => $withdrawMainPendingCount,
                    ],
                ],
                'free' => [
                    'amount' => core()->currency($withdrawFreeAmount),
                    'amount_raw' => $withdrawFreeAmount,
                    'count' => $withdrawFreeCount,
                    'users' => $withdrawFreeUsers,
                    'pending' => [
                        'amount' => core()->currency($withdrawFreePendingAmount),
                        'amount_raw' => $withdrawFreePendingAmount,
                        'count' => $withdrawFreePendingCount,
                    ],
                ],
            ],
            'bonus' => [
                'amount' => core()->currency($bonusAmount),
                'amount_raw' => $bonusAmount,
                'count' => $bonusCount,
                'ratio' => $bonusRatio,
                'deposit' => [
                    'amount' => core()->currency($bonusDepositAmount),
                    'amount_raw' => $bonusDepositAmount,
                    'count' => $bonusDepositCount,
                ],
                'activity' => [
                    'amount' => core()->currency($bonusActivityAmount),
                    'amount_raw' => $bonusActivityAmount,
                    'count' => $bonusActivityCount,
                ],
                'manual' => [
                    'amount' => core()->currency($bonusManualAmount),
                    'amount_raw' => $bonusManualAmount,
                    'count' => $bonusManualCount,
                ],
            ],
            'lotto' => [
                'sales_cash' => core()->currency($lottoSalesCash),
                'sales_cash_raw' => $lottoSalesCash,
                'payout_cash' => core()->currency($lottoPayoutCash),
                'payout_cash_raw' => $lottoPayoutCash,
                'refund_cash' => core()->currency($lottoRefundCash),
                'refund_cash_raw' => $lottoRefundCash,
                'net_cash' => core()->currency($lottoNetCash),
                'net_cash_raw' => $lottoNetCash,
            ],
            'lotto_product' => [
                'total_sales' => core()->currency((float) ($lottoProduct['total_sales'] ?? 0)),
                'total_sales_raw' => (float) ($lottoProduct['total_sales'] ?? 0),
                'total_payout' => core()->currency((float) ($lottoProduct['total_payout'] ?? 0)),
                'total_payout_raw' => (float) ($lottoProduct['total_payout'] ?? 0),
                'total_tickets' => (int) ($lottoProduct['total_tickets'] ?? 0),
                'total_players' => (int) ($lottoProduct['total_players'] ?? 0),
                'win_tickets' => (int) ($lottoProduct['win_tickets'] ?? 0),
                'lose_tickets' => (int) ($lottoProduct['lose_tickets'] ?? 0),
                'pending_tickets' => (int) ($lottoProduct['pending_tickets'] ?? 0),
                'settled_tickets' => (int) ($lottoProduct['settled_tickets'] ?? 0),
            ],
            'lotto_risk' => [
                'markets' => (int) ($lottoRisk['markets'] ?? 0),
                'rounds' => (int) ($lottoRisk['rounds'] ?? 0),
                'numbers' => (int) ($lottoRisk['numbers'] ?? 0),
                'exposure_total' => core()->currency((float) ($lottoRisk['exposure_total'] ?? 0)),
                'exposure_total_raw' => (float) ($lottoRisk['exposure_total'] ?? 0),
                'liability_total' => core()->currency((float) ($lottoRisk['liability_total'] ?? 0)),
                'liability_total_raw' => (float) ($lottoRisk['liability_total'] ?? 0),
                'liability_max' => core()->currency((float) ($lottoRisk['liability_max'] ?? 0)),
                'liability_max_raw' => (float) ($lottoRisk['liability_max'] ?? 0),
                'last_snapshot_at' => (string) ($lottoRisk['last_snapshot_at'] ?? ''),
            ],
            'lotto_bet_type_insights' => $lottoBetTypeInsights,
            'net' => [
                'amount' => core()->currency($net),
                'amount_raw' => $net,
                'change_pct' => $netChangePct,
            ],
            'register' => [
                'total' => $registerTotal,
                'normal' => $registerDirect,
                'referral' => $registerReferral,
                'campaign' => $registerCampaign,
            ],
            'first_deposit' => [
                'count' => $firstDepositCount,
                'rate' => $ftdRate,
            ],
        ];
    }

    private function getConversionFromSummaryTable(array $filters): array
    {
        [$startDate, $endDate] = $this->range($filters);
        $this->ensureSummaryRangeReady($startDate, $endDate);
        $current = $this->aggregateSummaryRange($startDate, $endDate);

        $registerTotal = (int) ($current['register_total'] ?? 0);
        $registerDeposit = (int) ($current['register_deposit_count'] ?? 0);
        $registerRepeatDeposit = $this->registerRepeatDepositCount($filters, $startDate, $endDate);
        $registerNotDeposit = max(0, $registerTotal - $registerDeposit);
        $registerRate = $registerTotal > 0 ? round(($registerDeposit / $registerTotal) * 100, 2) : 0;

        $referralTotal = (int) ($current['register_referral'] ?? 0);
        $referralDeposit = (int) ($current['register_referral_deposit_count'] ?? 0);
        $referralNotDeposit = max(0, $referralTotal - $referralDeposit);
        $referralRate = $referralTotal > 0 ? round(($referralDeposit / $referralTotal) * 100, 2) : 0;

        $staffMain = $this->staffMetricPayload(
            (float) ($current['staff_add_amount'] ?? 0),
            (float) ($current['staff_reduce_amount'] ?? 0),
            (int) ($current['staff_adjust_count'] ?? 0)
        );
        $staffFree = $this->staffAdjustMetricsByRepository(
            'Gametech\\Member\\Repositories\\MemberCreditFreeLogRepository',
            $filters,
            $startDate,
            $endDate,
            'members_credit_free_log'
        );
        $staffAdd = (float) $staffMain['add_raw'] + (float) $staffFree['add_raw'];
        $staffReduce = (float) $staffMain['reduce_raw'] + (float) $staffFree['reduce_raw'];
        $staffCount = (int) $staffMain['count'] + (int) $staffFree['count'];

        return [
            'register' => [
                'total' => $registerTotal,
                'deposit' => $registerDeposit,
                'repeat_deposit' => $registerRepeatDeposit,
                'not_deposit' => $registerNotDeposit,
                'rate' => $registerRate,
            ],
            'referral' => [
                'total' => $referralTotal,
                'deposit' => $referralDeposit,
                'not_deposit' => $referralNotDeposit,
                'rate' => $referralRate,
            ],
            'staff' => [
                'add' => core()->currency($staffAdd),
                'add_raw' => $staffAdd,
                'reduce' => core()->currency($staffReduce),
                'reduce_raw' => $staffReduce,
                'net' => core()->currency($staffAdd - $staffReduce),
                'net_raw' => $staffAdd - $staffReduce,
                'count' => $staffCount,
                'main' => $staffMain,
                'free' => $staffFree,
            ],
        ];
    }

    private function getFunnelFromSummaryTable(array $filters): array
    {
        [$startDate, $endDate] = $this->range($filters);
        $this->ensureSummaryRangeReady($startDate, $endDate);
        $current = $this->aggregateSummaryRange($startDate, $endDate);
        $registerRepeatDeposit = $this->registerRepeatDepositCount($filters, $startDate, $endDate);

        return [
            'funnel' => [
                'register' => (int) ($current['register_total'] ?? 0),
                'register_deposit' => (int) ($current['register_deposit_count'] ?? 0),
                'register_repeat_deposit' => $registerRepeatDeposit,
                'confirmed' => (int) ($current['register_confirmed_count'] ?? 0),
                'first_deposit' => (int) ($current['first_deposit_count'] ?? 0),
                'repeat_deposit' => (int) ($current['repeat_deposit_count'] ?? 0),
            ],
            'sources' => [
                'direct' => (int) ($current['register_direct'] ?? 0),
                'campaign' => (int) ($current['register_campaign'] ?? 0),
                'referral' => (int) ($current['register_referral'] ?? 0),
            ],
        ];
    }

    private function aggregateSummaryRange(string $startDate, string $endDate): array
    {
        if (!$this->hasTable('dashboard_summary_daily')) {
            return [];
        }

        $selects = [
            'COALESCE(SUM(register_total), 0) as register_total',
            'COALESCE(SUM(register_direct), 0) as register_direct',
            'COALESCE(SUM(register_referral), 0) as register_referral',
            'COALESCE(SUM(register_campaign), 0) as register_campaign',
            'COALESCE(SUM(deposit_total_amount), 0) as deposit_total_amount',
            'COALESCE(SUM(deposit_total_count), 0) as deposit_total_count',
            'COALESCE(SUM(deposit_total_users), 0) as deposit_total_users',
            'COALESCE(SUM(deposit_success_amount), 0) as deposit_success_amount',
            'COALESCE(SUM(deposit_success_count), 0) as deposit_success_count',
            'COALESCE(SUM(deposit_success_users), 0) as deposit_success_users',
            'COALESCE(SUM(deposit_pending_amount), 0) as deposit_pending_amount',
            'COALESCE(SUM(deposit_pending_count), 0) as deposit_pending_count',
            'COALESCE(SUM(deposit_pending_users), 0) as deposit_pending_users',
            'COALESCE(SUM(deposit_reject_amount), 0) as deposit_reject_amount',
            'COALESCE(SUM(deposit_reject_count), 0) as deposit_reject_count',
            'COALESCE(SUM(deposit_reject_users), 0) as deposit_reject_users',
            'COALESCE(SUM(deposit_deleted_amount), 0) as deposit_deleted_amount',
            'COALESCE(SUM(deposit_deleted_count), 0) as deposit_deleted_count',
            'COALESCE(SUM(deposit_deleted_users), 0) as deposit_deleted_users',
            'COALESCE(SUM(withdraw_total_amount), 0) as withdraw_total_amount',
            'COALESCE(SUM(withdraw_total_count), 0) as withdraw_total_count',
            'COALESCE(SUM(withdraw_total_users), 0) as withdraw_total_users',
            'COALESCE(SUM(withdraw_pending_amount), 0) as withdraw_pending_amount',
            'COALESCE(SUM(withdraw_pending_count), 0) as withdraw_pending_count',
            $this->summarySumExpression('withdraw_main_total_amount') . ' as withdraw_main_total_amount',
            $this->summarySumExpression('withdraw_main_total_count') . ' as withdraw_main_total_count',
            $this->summarySumExpression('withdraw_main_total_users') . ' as withdraw_main_total_users',
            $this->summarySumExpression('withdraw_main_pending_amount') . ' as withdraw_main_pending_amount',
            $this->summarySumExpression('withdraw_main_pending_count') . ' as withdraw_main_pending_count',
            $this->summarySumExpression('withdraw_free_total_amount') . ' as withdraw_free_total_amount',
            $this->summarySumExpression('withdraw_free_total_count') . ' as withdraw_free_total_count',
            $this->summarySumExpression('withdraw_free_total_users') . ' as withdraw_free_total_users',
            $this->summarySumExpression('withdraw_free_pending_amount') . ' as withdraw_free_pending_amount',
            $this->summarySumExpression('withdraw_free_pending_count') . ' as withdraw_free_pending_count',
            'COALESCE(SUM(bonus_deposit_amount), 0) as bonus_deposit_amount',
            'COALESCE(SUM(bonus_deposit_count), 0) as bonus_deposit_count',
            'COALESCE(SUM(bonus_activity_amount), 0) as bonus_activity_amount',
            'COALESCE(SUM(bonus_activity_count), 0) as bonus_activity_count',
            'COALESCE(SUM(bonus_manual_amount), 0) as bonus_manual_amount',
            'COALESCE(SUM(bonus_manual_count), 0) as bonus_manual_count',
            'COALESCE(SUM(bonus_total_amount), 0) as bonus_total_amount',
            'COALESCE(SUM(bonus_total_count), 0) as bonus_total_count',
            $this->summarySumExpression('lotto_sales_cash') . ' as lotto_sales_cash',
            $this->summarySumExpression('lotto_payout_cash') . ' as lotto_payout_cash',
            $this->summarySumExpression('lotto_refund_cash') . ' as lotto_refund_cash',
            $this->summarySumExpression('lotto_net_cash') . ' as lotto_net_cash',
            'COALESCE(SUM(net_amount), 0) as net_amount',
            'COALESCE(SUM(first_deposit_count), 0) as first_deposit_count',
            'COALESCE(SUM(repeat_deposit_count), 0) as repeat_deposit_count',
            'COALESCE(SUM(register_confirmed_count), 0) as register_confirmed_count',
            'COALESCE(SUM(register_deposit_count), 0) as register_deposit_count',
            'COALESCE(SUM(register_referral_deposit_count), 0) as register_referral_deposit_count',
            'COALESCE(SUM(staff_add_amount), 0) as staff_add_amount',
            'COALESCE(SUM(staff_reduce_amount), 0) as staff_reduce_amount',
            'COALESCE(SUM(staff_adjust_count), 0) as staff_adjust_count',
        ];

        $row = DB::table('dashboard_summary_daily')
            ->where('web_code', $this->dashboardWebCode())
            ->whereBetween('summary_date', [$startDate, $endDate])
            ->selectRaw(implode(",\n", $selects))
            ->first();

        return $row ? (array) $row : [];
    }

    private function getDailyTrendsFromSummaryTable(string $startDate, string $endDate): array
    {
        $this->ensureSummaryRangeReady($startDate, $endDate);

        $dateArr = core()->generateDateRange($startDate, $endDate);
        $rows = DB::table('dashboard_summary_daily')
            ->where('web_code', $this->dashboardWebCode())
            ->whereBetween('summary_date', [$startDate, $endDate])
            ->select(['summary_date', 'deposit_total_amount', 'withdraw_total_amount', 'bonus_total_amount'])
            ->orderBy('summary_date')
            ->get();

        $maps = $rows->mapWithKeys(function ($row) {
            return [
                (string) $row->summary_date => [
                    'deposit' => (float) ($row->deposit_total_amount ?? 0),
                    'withdraw' => (float) ($row->withdraw_total_amount ?? 0),
                    'bonus' => (float) ($row->bonus_total_amount ?? 0),
                ],
            ];
        });

        $labels = [];
        $deposit = [];
        $withdraw = [];
        $bonus = [];

        foreach ($dateArr as $date) {
            $labels[] = core()->Date($date, 'd M');
            $daily = $maps[$date] ?? ['deposit' => 0, 'withdraw' => 0, 'bonus' => 0];
            $deposit[] = (float) $daily['deposit'];
            $withdraw[] = (float) $daily['withdraw'];
            $bonus[] = (float) $daily['bonus'];
        }

        return [
            'mode' => 'day',
            'labels' => $labels,
            'deposit' => $deposit,
            'withdraw' => $withdraw,
            'bonus' => $bonus,
        ];
    }

    private function dashboardWebCode(): string
    {
        return app(DashboardWebCodeResolver::class)->resolve();
    }

    private function ensureSummaryRangeReady(string $startDate, string $endDate): void
    {
        if (!$this->hasTable('dashboard_summary_daily')) {
            return;
        }

        [$startDate, $endDate] = [
            Carbon::parse($startDate)->toDateString(),
            Carbon::parse($endDate)->toDateString(),
        ];
        if ($endDate < $startDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $webCode = $this->dashboardWebCode();
        $warmKey = implode('|', [$webCode, $startDate, $endDate]);
        if (isset($this->summaryWarmCache[$warmKey])) {
            return;
        }

        $dateRange = core()->generateDateRange($startDate, $endDate);
        if (empty($dateRange)) {
            $this->summaryWarmCache[$warmKey] = true;
            return;
        }

        $existingRows = DB::table('dashboard_summary_daily')
            ->where('web_code', $webCode)
            ->whereBetween('summary_date', [$startDate, $endDate])
            ->select(['summary_date', 'metric_version'])
            ->get();

        $existingMap = [];
        foreach ($existingRows as $row) {
            $existingMap[(string) $row->summary_date] = (int) ($row->metric_version ?? 0);
        }

        $datesToSync = [];
        foreach ($dateRange as $date) {
            if (!isset($existingMap[$date])) {
                $datesToSync[] = $date;
                continue;
            }

            if ($existingMap[$date] < \App\Services\Dashboard\DashboardSummaryProjector::METRIC_VERSION) {
                $datesToSync[] = $date;
            }
        }

        if (!empty($datesToSync)) {
            $syncService = app(DashboardSummarySyncService::class);
            foreach (array_values(array_unique($datesToSync)) as $date) {
                try {
                    $syncService->syncBucket(
                        summaryDate: $date,
                        webCode: $webCode,
                        updatedSections: ['deposit', 'withdraw', 'bonus', 'register', 'conversion', 'funnel', 'net', 'lotto_cash', 'lotto_product', 'lotto_risk', 'lotto_operations'],
                        sourceType: 'on_demand',
                        sourceId: 'dashboard_request'
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $this->summaryWarmCache[$warmKey] = true;
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'date_start' => Arr::get($filters, 'date_start') ?: now()->toDateString(),
            'date_end' => Arr::get($filters, 'date_end') ?: now()->toDateString(),
            'register_channel' => Arr::get($filters, 'register_channel'),
            'deposit_channel' => Arr::get($filters, 'deposit_channel'),
            'lotto_market_id' => Arr::get($filters, 'lotto_market_id'),
            'trend_mode' => Arr::get($filters, 'trend_mode', 'day'),
        ];
    }

    private function range(array $filters): array
    {
        $start = Carbon::parse($filters['date_start']);
        $end = Carbon::parse($filters['date_end']);
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    private function previousRange(array $filters): array
    {
        [$start, $end] = $this->range($filters);
        $startC = Carbon::parse($start);
        $endC = Carbon::parse($end);
        $diff = $startC->diffInDays($endC);
        $prevEnd = $startC->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($diff);

        return [$prevStart->toDateString(), $prevEnd->toDateString()];
    }

    private function dateTimeRange(string $startDate, string $endDate): array
    {
        $startAt = Carbon::parse($startDate)->startOfDay();
        $endAt = Carbon::parse($endDate)->addDay()->startOfDay();

        return [$startAt->toDateTimeString(), $endAt->toDateTimeString()];
    }

    private function applyDateTimeWindow($query, string $column, string $startDate, string $endDate): void
    {
        [$startAt, $endAt] = $this->dateTimeRange($startDate, $endDate);

        $query->where($column, '>=', $startAt)
            ->where($column, '<', $endAt);
    }

    private function pctChange(float $prev, float $cur): float
    {
        if ($prev == 0.0) {
            return $cur == 0.0 ? 0 : 100;
        }

        return round((($cur - $prev) / abs($prev)) * 100, 2);
    }

    private function cacheKey(string $name, array $filters): string
    {
        $version = (string) Cache::get(self::CACHE_VERSION_KEY, '0');

        return 'dashboard:' . $name . ':v' . $version . ':' . md5(json_encode($filters));
    }

    private function forgetDashboardCaches(array $filters): void
    {
        foreach (['summary', 'conversion', 'trends', 'funnel', 'activity', 'alerts'] as $name) {
            Cache::forget($this->cacheKey($name, $filters));
        }
    }

    private function memberQuery(array $filters)
    {
        $query = app('Gametech\\Member\\Repositories\\MemberRepository');
        return $this->applyMemberFilters($query, $filters);
    }

    private function applyMemberFilters($query, array $filters)
    {
        $hasUpline = $this->hasColumn('members', 'upline_code');

        if (!empty($filters['register_channel'])) {
            switch ($filters['register_channel']) {
                case 'direct':
                    if ($hasUpline) {
                        $query->where(function ($q) {
                            $q->whereNull('upline_code')->orWhere('upline_code', 0);
                        });
                    }
                    break;
                case 'referral':
                    if ($hasUpline) {
                        $query->where('upline_code', '>', 0);
                    }
                    break;
            }
        }

        return $query;
    }

    private function applyPaymentFilters($query, array $filters)
    {
        if (!empty($filters['deposit_channel']) && $this->hasColumn('bank_payment', 'channel')) {
            $query->where('channel', $filters['deposit_channel']);
        }

        return $this->applyMemberRelationFilters($query, $filters);
    }

    private function applyMemberRelationFilters($query, array $filters)
    {
        if (!empty($filters['register_channel'])) {
            $query->whereHas('member', function ($q) use ($filters) {
                $this->applyMemberFilters($q, $filters);
            });
        }

        return $query;
    }

    private function withdrawBaseQueries(array $filters, string $status = 'complete', string $scope = 'all'): array
    {
        $config = core()->getConfigData();
        $queries = [];
        $includeMain = $scope !== 'free';
        $includeFree = $scope !== 'main' && (($config->freecredit_open ?? 'N') == 'Y');

        if ($config->seamless == 'Y') {
            if ($includeMain) {
                $queries[] = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')->active();
            }
            if ($includeFree) {
                $queries[] = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessFreeRepository')->active();
            }
        } else {
            if ($includeMain) {
                $queries[] = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->active();
            }
            if ($includeFree) {
                $queries[] = app('Gametech\\Payment\\Repositories\\WithdrawFreeRepository')->active();
            }
        }

        $prepared = [];
        foreach ($queries as $query) {
            if ($status === 'waiting') {
                $query->waiting();
            } elseif ($status === 'complete') {
                $query->complete();
            }

            $prepared[] = $this->applyMemberRelationFilters($query, $filters);
        }

        return $prepared;
    }

    private function withdrawTotals(
        array $filters,
        string $status,
        string $dateColumn,
        string $startDate,
        string $endDate,
        string $scope = 'all'
    ): array {
        $queries = $this->withdrawBaseQueries($filters, $status, $scope);
        $amount = 0.0;
        $count = 0;

        foreach ($queries as $query) {
            $scoped = clone $query;
            $this->applyDateTimeWindow($scoped, $dateColumn, $startDate, $endDate);

            $amount += (float) (clone $scoped)->sum('amount');
            $count += (int) (clone $scoped)->count();
        }

        return [
            'amount' => $amount,
            'count' => $count,
            'users' => $this->withdrawDistinctUsersCount($queries, $dateColumn, $startDate, $endDate),
        ];
    }

    private function withdrawDistinctUsersCount(
        array $queries,
        string $dateColumn,
        string $startDate,
        string $endDate
    ): int {
        $union = null;

        foreach ($queries as $query) {
            $scoped = clone $query;
            $this->applyDateTimeWindow($scoped, $dateColumn, $startDate, $endDate);
            $members = (clone $scoped)
                ->select('member_code')
                ->whereNotNull('member_code')
                ->distinct();

            if ($union === null) {
                $union = $members;
            } else {
                $union->union($members);
            }
        }

        if ($union === null) {
            return 0;
        }

        return (int) DB::query()
            ->fromSub($union, 'withdraw_members')
            ->distinct('member_code')
            ->count('member_code');
    }

    private function withdrawTrendByHour(array $filters, string $startDate, string $endDate): array
    {
        $result = [];

        foreach ($this->withdrawBaseQueries($filters, 'complete') as $query) {
            $scoped = clone $query;
            $this->applyDateTimeWindow($scoped, 'date_approve', $startDate, $endDate);
            $rows = $scoped
                ->selectRaw('HOUR(date_approve) as h, SUM(amount) as v')
                ->groupBy('h')
                ->pluck('v', 'h')
                ->toArray();

            foreach ($rows as $hour => $amount) {
                $key = (int) $hour;
                $result[$key] = (float) ($result[$key] ?? 0) + (float) $amount;
            }
        }

        return $result;
    }

    private function withdrawTrendByDay(array $filters, string $startDate, string $endDate): array
    {
        $result = [];

        foreach ($this->withdrawBaseQueries($filters, 'complete') as $query) {
            $scoped = clone $query;
            $this->applyDateTimeWindow($scoped, 'date_approve', $startDate, $endDate);
            $rows = $scoped
                ->selectRaw("DATE_FORMAT(date_approve,'%Y-%m-%d') as d, SUM(amount) as v")
                ->groupBy('d')
                ->pluck('v', 'd')
                ->toArray();

            foreach ($rows as $date => $amount) {
                $key = (string) $date;
                $result[$key] = (float) ($result[$key] ?? 0) + (float) $amount;
            }
        }

        return $result;
    }

    private function withdrawPendingCountOlderThan(array $filters, $cutoff): int
    {
        $count = 0;

        foreach ($this->withdrawBaseQueries($filters, 'waiting') as $query) {
            $count += (int) (clone $query)
                ->where('date_create', '<', $cutoff)
                ->count();
        }

        return $count;
    }

    private function bonusTotals(array $filters, string $startDate, string $endDate): array
    {
        $deposit = $this->aggregateBonusSources(
            $this->bonusSourceDefinitions('deposit'),
            $startDate,
            $endDate,
            $filters
        );
        $activity = $this->aggregateBonusSources(
            $this->bonusSourceDefinitions('activity'),
            $startDate,
            $endDate,
            $filters
        );

        return [
            'deposit_amount' => $deposit['amount'],
            'deposit_count' => $deposit['count'],
            'activity_amount' => $activity['amount'],
            'activity_count' => $activity['count'],
            'manual_amount' => 0.0,
            'manual_count' => 0,
            'amount' => $deposit['amount'] + $activity['amount'],
            'count' => $deposit['count'] + $activity['count'],
        ];
    }

    private function bonusMode(): string
    {
        $config = core()->getConfigData();

        if (($config->seamless ?? 'N') === 'Y') {
            return (($config->freecredit_open ?? 'N') === 'Y')
                ? 'seamless_free'
                : 'legacy';
        }

        if (($config->multigame_open ?? 'N') === 'Y') {
            return 'multi';
        }

        return 'legacy';
    }

    private function bonusSourceDefinitions(string $bucket): array
    {
        $mode = $this->bonusMode();

        if ($bucket === 'deposit') {
            return match ($mode) {
                'seamless_free', 'multi' => [[
                    'table' => 'members_promotionlog',
                    'date_column' => 'date_create',
                    'amount_column' => 'bonus',
                    'member_column' => 'member_code',
                    'conditions' => [
                        ['enable', '=', 'Y'],
                        ['bonus', '>', 0],
                    ],
                ]],
                default => [[
                    'table' => 'bills',
                    'date_column' => 'date_create',
                    'amount_column' => 'credit_bonus',
                    'member_column' => 'member_code',
                    'conditions' => [
                        ['enable', '=', 'Y'],
                        ['method', '=', 'TOPUP'],
                        ['pro_code', '>', 0],
                        ['credit_bonus', '>', 0],
                    ],
                ]],
            };
        }

        return match ($mode) {
            'seamless_free' => [[
                'table' => 'members_credit_free_log',
                'date_column' => 'date_create',
                'amount_column' => 'total',
                'member_column' => 'member_code',
                'conditions' => [
                    ['enable', '=', 'Y'],
                    ['kind', 'in', ['TRANCB', 'TRANBONUS', 'TRANFT', 'TRANIC']],
                    ['total', '>', 0],
                ],
            ]],
            'multi' => [
                [
                    'table' => 'members_credit_log',
                    'date_column' => 'date_create',
                    'amount_column' => 'total',
                    'member_column' => 'member_code',
                    'conditions' => [
                        ['enable', '=', 'Y'],
                        ['kind', 'in', ['SPIN', 'CASHBACK', 'IC', 'FASTSTART']],
                        ['total', '>', 0],
                    ],
                ],
                [
                    'table' => 'members_credit_free_log',
                    'date_column' => 'date_create',
                    'amount_column' => 'total',
                    'member_column' => 'member_code',
                    'conditions' => [
                        ['enable', '=', 'Y'],
                        ['kind', 'in', ['SPIN', 'CASHBACK', 'IC', 'FASTSTART']],
                        ['total', '>', 0],
                    ],
                ],
            ],
            default => [[
                'table' => 'bills',
                'date_column' => 'date_create',
                'amount_column' => 'credit_bonus',
                'member_column' => 'member_code',
                'conditions' => [
                    ['enable', '=', 'Y'],
                    ['method', '=', 'BONUS'],
                    ['credit_bonus', '>', 0],
                ],
            ]],
        };
    }

    private function aggregateBonusSources(array $sources, string $startDate, string $endDate, array $filters): array
    {
        $amount = 0.0;
        $count = 0;

        foreach ($sources as $source) {
            $query = $this->buildBonusSourceQuery($source, $filters, $startDate, $endDate);
            if ($query === null) {
                continue;
            }

            $amount += (float) (clone $query)->sum($source['amount_column']);
            $count += (int) (clone $query)->count();
        }

        return [
            'amount' => $amount,
            'count' => $count,
        ];
    }

    private function buildBonusSourceQuery(array $source, array $filters, string $startDate, string $endDate)
    {
        $table = $source['table'];
        $dateColumn = $source['date_column'];
        $amountColumn = $source['amount_column'];
        $memberColumn = $source['member_column'] ?? null;

        if (
            !$this->hasTable($table)
            || !$this->hasColumn($table, $dateColumn)
            || !$this->hasColumn($table, $amountColumn)
        ) {
            return null;
        }

        if ($memberColumn && !$this->hasColumn($table, $memberColumn)) {
            return null;
        }

        foreach ($source['conditions'] ?? [] as $condition) {
            if (!$this->hasColumn($table, $condition[0])) {
                return null;
            }
        }

        $query = DB::table($table);
        $this->applyDateTimeWindow($query, $dateColumn, $startDate, $endDate);
        $this->applyBonusConditions($query, $source['conditions'] ?? []);

        if ($memberColumn) {
            $query = $this->applyMemberCodeFilter($query, $table . '.' . $memberColumn, $filters);
        }

        return $query;
    }

    private function applyBonusConditions($query, array $conditions): void
    {
        foreach ($conditions as [$column, $operator, $value]) {
            if ($operator === 'in') {
                $query->whereIn($column, $value);
                continue;
            }

            $query->where($column, $operator, $value);
        }
    }

    private function applyMemberCodeFilter($query, string $qualifiedMemberColumn, array $filters)
    {
        if (empty($filters['register_channel']) || !$this->hasTable('members')) {
            return $query;
        }

        $memberQuery = app('Gametech\\Member\\Repositories\\MemberRepository')
            ->getModel()
            ->newQuery()
            ->select('members.code');

        $memberQuery = $this->applyMemberFilters($memberQuery, $filters);

        return $query->whereIn($qualifiedMemberColumn, $memberQuery->toBase());
    }

    private function staffAdjustMetricsByRepository(
        string $repositoryClass,
        array $filters,
        string $startDate,
        string $endDate,
        ?string $requiredTable = null
    ): array {
        if ($requiredTable && !$this->hasTable($requiredTable)) {
            return $this->staffMetricPayload(0, 0, 0);
        }

        $addQuery = app($repositoryClass)
            ->active()
            ->where('kind', 'SETWALLET')
            ->where('credit_type', 'D');
        $this->applyDateTimeWindow($addQuery, 'date_create', $startDate, $endDate);
        $addQuery = $this->applyMemberRelationFilters($addQuery, $filters);

        $reduceQuery = app($repositoryClass)
            ->active()
            ->where('kind', 'SETWALLET')
            ->where('credit_type', 'W');
        $this->applyDateTimeWindow($reduceQuery, 'date_create', $startDate, $endDate);
        $reduceQuery = $this->applyMemberRelationFilters($reduceQuery, $filters);

        $addAmount = (float) (clone $addQuery)->sum('amount');
        $reduceAmount = (float) (clone $reduceQuery)->sum('amount');
        $count = (int) ((clone $addQuery)->count() + (clone $reduceQuery)->count());

        return $this->staffMetricPayload($addAmount, $reduceAmount, $count);
    }

    private function staffMetricPayload(float $addAmount, float $reduceAmount, int $count): array
    {
        return [
            'add' => core()->currency($addAmount),
            'add_raw' => $addAmount,
            'reduce' => core()->currency($reduceAmount),
            'reduce_raw' => $reduceAmount,
            'net' => core()->currency($addAmount - $reduceAmount),
            'net_raw' => $addAmount - $reduceAmount,
            'count' => $count,
        ];
    }

    private function netCashflow(array $filters, string $startDate, string $endDate): float
    {
        $depositQuery = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
            ->income()->active()->where('status', 1);
        $this->applyDateTimeWindow($depositQuery, 'date_create', $startDate, $endDate);
        $depositQuery = $this->applyPaymentFilters($depositQuery, $filters);

        $depositAmount = (float) $depositQuery->sum('value');
        $withdrawAmount = 0.0;
        foreach ($this->withdrawBaseQueries($filters, 'complete') as $withdrawQuery) {
            $scoped = clone $withdrawQuery;
            $this->applyDateTimeWindow($scoped, 'date_approve', $startDate, $endDate);
            $withdrawAmount += (float) (clone $scoped)->sum('amount');
        }

        $lotto = $this->lottoCashMetrics($startDate, $endDate);

        return $depositAmount - $withdrawAmount + (float) ($lotto['net_cash'] ?? 0);
    }

    private function lottoCashMetrics(string $startDate, string $endDate): array
    {
        $defaults = [
            'sales_cash' => 0.0,
            'payout_cash' => 0.0,
            'refund_cash' => 0.0,
            'net_cash' => 0.0,
        ];

        if (
            !$this->hasTable('wallet_transactions')
            || !$this->hasColumn('wallet_transactions', 'created_at')
            || !$this->hasColumn('wallet_transactions', 'status')
            || !$this->hasColumn('wallet_transactions', 'direction')
            || !$this->hasColumn('wallet_transactions', 'ref_type')
            || !$this->hasColumn('wallet_transactions', 'amount')
        ) {
            return $defaults;
        }

        [$startAt, $endAt] = $this->dateTimeRange($startDate, $endDate);

        $buildBase = function (string $direction, array $refTypes) use ($startAt, $endAt) {
            $query = DB::table('wallet_transactions')
                ->where('status', LottoDashboardMetricConfig::WALLET_SUCCESS_STATUS)
                ->where('created_at', '>=', $startAt)
                ->where('created_at', '<', $endAt)
                ->where('direction', $direction)
                ->whereIn('ref_type', $refTypes);

            if ($this->hasColumn('wallet_transactions', 'scope')) {
                $query->where('scope', 'MEMBER');
            }

            return $query;
        };

        $defaults['sales_cash'] = (float) $buildBase('DEBIT', LottoDashboardMetricConfig::salesRefTypes())->sum('amount');
        $defaults['payout_cash'] = (float) $buildBase('CREDIT', LottoDashboardMetricConfig::payoutRefTypes())->sum('amount');
        $defaults['refund_cash'] = (float) $buildBase('CREDIT', LottoDashboardMetricConfig::refundRefTypes())->sum('amount');
        $defaults['net_cash'] = round(
            (float) $defaults['sales_cash']
            - (float) $defaults['payout_cash']
            - (float) $defaults['refund_cash'],
            2
        );

        return $defaults;
    }

    private function lottoProductSummaryMetrics(string $startDate, string $endDate): array
    {
        $defaults = [
            'total_sales' => 0.0,
            'total_payout' => 0.0,
            'total_tickets' => 0,
            'total_players' => 0,
            'win_tickets' => 0,
            'lose_tickets' => 0,
            'pending_tickets' => 0,
            'settled_tickets' => 0,
        ];

        if (!$this->hasTable('lotto_dashboard_summary_daily')) {
            return $defaults;
        }

        $row = DB::table('lotto_dashboard_summary_daily')
            ->where('web_code', $this->dashboardWebCode())
            ->whereBetween('summary_date', [$startDate, $endDate])
            ->selectRaw(implode(",\n", [
                'COALESCE(SUM(total_sales), 0) as total_sales',
                'COALESCE(SUM(total_payout), 0) as total_payout',
                'COALESCE(SUM(total_tickets), 0) as total_tickets',
                'COALESCE(SUM(total_players), 0) as total_players',
                'COALESCE(SUM(win_tickets), 0) as win_tickets',
                'COALESCE(SUM(lose_tickets), 0) as lose_tickets',
                'COALESCE(SUM(pending_tickets), 0) as pending_tickets',
                'COALESCE(SUM(settled_tickets), 0) as settled_tickets',
            ]))
            ->first();

        if (!$row) {
            return $defaults;
        }

        return [
            'total_sales' => (float) ($row->total_sales ?? 0),
            'total_payout' => (float) ($row->total_payout ?? 0),
            'total_tickets' => (int) ($row->total_tickets ?? 0),
            'total_players' => (int) ($row->total_players ?? 0),
            'win_tickets' => (int) ($row->win_tickets ?? 0),
            'lose_tickets' => (int) ($row->lose_tickets ?? 0),
            'pending_tickets' => (int) ($row->pending_tickets ?? 0),
            'settled_tickets' => (int) ($row->settled_tickets ?? 0),
        ];
    }

    private function lottoRiskSummaryMetrics(string $startDate, string $endDate): array
    {
        $defaults = [
            'markets' => 0,
            'rounds' => 0,
            'numbers' => 0,
            'exposure_total' => 0.0,
            'liability_total' => 0.0,
            'liability_max' => 0.0,
            'last_snapshot_at' => '',
        ];

        if (
            !$this->hasTable('lotto_dashboard_risk_snapshot')
            || !$this->hasColumn('lotto_dashboard_risk_snapshot', 'snapshot_at')
        ) {
            return $defaults;
        }

        [$startAt, $endAt] = $this->dateTimeRange($startDate, $endDate);

        $row = DB::table('lotto_dashboard_risk_snapshot')
            ->where('web_code', $this->dashboardWebCode())
            ->where('snapshot_at', '>=', $startAt)
            ->where('snapshot_at', '<', $endAt)
            ->selectRaw(implode(",\n", [
                'COALESCE(COUNT(*), 0) as numbers',
                'COALESCE(COUNT(DISTINCT market_id), 0) as markets',
                'COALESCE(COUNT(DISTINCT round_id), 0) as rounds',
                'COALESCE(SUM(payout_if_hit), 0) as exposure_total',
                'COALESCE(SUM(liability), 0) as liability_total',
                'COALESCE(MAX(liability), 0) as liability_max',
                'MAX(snapshot_at) as last_snapshot_at',
            ]))
            ->first();

        if (!$row) {
            return $defaults;
        }

        return [
            'markets' => (int) ($row->markets ?? 0),
            'rounds' => (int) ($row->rounds ?? 0),
            'numbers' => (int) ($row->numbers ?? 0),
            'exposure_total' => (float) ($row->exposure_total ?? 0),
            'liability_total' => (float) ($row->liability_total ?? 0),
            'liability_max' => (float) ($row->liability_max ?? 0),
            'last_snapshot_at' => (string) ($row->last_snapshot_at ?? ''),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lottoBetTypeInsightsSummary(string $startDate, string $endDate): array
    {
        if (
            !$this->hasTable('lotto_dashboard_bet_type_summary_daily')
            || !$this->hasTable('lotto_dashboard_bet_type_number_daily')
        ) {
            return [];
        }

        $isSingleDay = $startDate === $endDate;

        $dailyRows = DB::table('lotto_dashboard_bet_type_summary_daily')
            ->whereBetween('summary_date', [$startDate, $endDate])
            ->selectRaw(implode(",\n", [
                'bet_type',
                'COALESCE(SUM(item_count), 0) as item_count',
                'COALESCE(SUM(total_amount), 0) as total_amount',
                'COALESCE(SUM(unique_players), 0) as unique_players',
            ]))
            ->groupBy('bet_type')
            ->orderBy('bet_type')
            ->get();

        if ($dailyRows->isEmpty()) {
            return [];
        }

        $numberRows = DB::table('lotto_dashboard_bet_type_number_daily')
            ->whereBetween('summary_date', [$startDate, $endDate])
            ->selectRaw(implode(",\n", [
                'bet_type',
                'number',
                'COALESCE(SUM(item_count), 0) as item_count',
                'COALESCE(SUM(total_amount), 0) as total_amount',
            ]))
            ->groupBy('bet_type', 'number')
            ->orderBy('bet_type')
            ->orderByDesc('total_amount')
            ->orderByDesc('item_count')
            ->orderBy('number')
            ->get();

        $topByType = [];
        foreach ($numberRows as $row) {
            $type = (string) ($row->bet_type ?? '');
            if ($type === '') {
                continue;
            }

            $amount = (float) ($row->total_amount ?? 0);
            $count = (int) ($row->item_count ?? 0);
            $number = (string) ($row->number ?? '');

            if (!isset($topByType[$type])) {
                $topByType[$type] = [
                    'top_number' => $number,
                    'top_number_amount_raw' => $amount,
                    'top_number_item_count' => $count,
                ];
                continue;
            }

            $current = $topByType[$type];
            $isBetter = false;
            if ($amount > (float) $current['top_number_amount_raw']) {
                $isBetter = true;
            } elseif ($amount === (float) $current['top_number_amount_raw'] && $count > (int) $current['top_number_item_count']) {
                $isBetter = true;
            } elseif (
                $amount === (float) $current['top_number_amount_raw']
                && $count === (int) $current['top_number_item_count']
                && strcmp($number, (string) $current['top_number']) < 0
            ) {
                $isBetter = true;
            }

            if ($isBetter) {
                $topByType[$type] = [
                    'top_number' => $number,
                    'top_number_amount_raw' => $amount,
                    'top_number_item_count' => $count,
                ];
            }
        }

        return $dailyRows->map(function ($row) use ($isSingleDay, $topByType): array {
            $betType = (string) ($row->bet_type ?? '');
            $top = $topByType[$betType] ?? [
                'top_number' => '',
                'top_number_amount_raw' => 0.0,
            ];

            $totalAmountRaw = (float) ($row->total_amount ?? 0);
            $topAmountRaw = (float) ($top['top_number_amount_raw'] ?? 0);
            $topNumber = (string) ($top['top_number'] ?? '');

            return [
                'bet_type' => $betType,
                'label' => BetType::label($betType),
                'item_count' => (int) ($row->item_count ?? 0),
                'total_amount' => core()->currency($totalAmountRaw),
                'total_amount_raw' => $totalAmountRaw,
                'unique_players' => $isSingleDay ? (int) ($row->unique_players ?? 0) : null,
                'top_number' => $topNumber !== '' ? $topNumber : null,
                'top_number_amount' => core()->currency($topAmountRaw),
                'top_number_amount_raw' => $topAmountRaw,
            ];
        })->values()->all();
    }

    private function registerTotals(array $filters, string $startDate, string $endDate): array
    {
        $dateColumn = $this->memberDateColumn();
        $totalQuery = $this->memberQuery($filters);
        $this->applyDateTimeWindow($totalQuery, $dateColumn, $startDate, $endDate);
        $total = $totalQuery->count();

        $campaign = 0;
        if ($this->hasColumn('members', 'campaign_id')) {
            $campaignQuery = $this->memberQuery($filters)
                ->whereNotNull('campaign_id')
                ->where('campaign_id', '>', 0);
            $this->applyDateTimeWindow($campaignQuery, $dateColumn, $startDate, $endDate);
            $campaign = $campaignQuery->count();
        }

        $referral = 0;
        if ($this->hasColumn('members', 'upline_code')) {
            $referralQuery = $this->memberQuery($filters)
                ->where('upline_code', '>', 0);
            $this->applyDateTimeWindow($referralQuery, $dateColumn, $startDate, $endDate);

            if ($this->hasColumn('members', 'campaign_id')) {
                $referralQuery->where(function ($query) {
                    $query->whereNull('campaign_id')->orWhere('campaign_id', 0);
                });
            }

            $referral = $referralQuery->count();
        }

        return [
            'total' => $total,
            'campaign' => $campaign,
            'referral' => $referral,
            'normal' => max(0, $total - $campaign - $referral),
        ];
    }

    private function firstDepositCount(array $filters, string $startDate, string $endDate): int
    {
        $query = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
            ->income()->active()->where('status', 1);
        $query = $this->applyPaymentFilters($query, $filters);

        $sub = $query
            ->selectRaw('member_topup, MIN(date_create) as first_at')
            ->groupBy('member_topup')
            ->toBase();

        [$startAt, $endAt] = $this->dateTimeRange($startDate, $endDate);

        return (int) DB::query()->fromSub($sub, 'fd')
            ->where('first_at', '>=', $startAt)
            ->where('first_at', '<', $endAt)
            ->count();
    }

    private function registerDepositCount(array $filters, string $startDate, string $endDate): int
    {
        if (!$this->hasTable('bank_payment')) {
            return 0;
        }

        $dateColumn = $this->memberDateColumn();
        $query = $this->memberQuery($filters)
            ->whereHas('payment', function ($query) use ($startDate, $endDate, $filters) {
                $query->where('status', 1)
                    ->where('enable', 'Y')
                    ->where('value', '>', 0);
                $this->applyDateTimeWindow($query, 'date_create', $startDate, $endDate);

                if (!empty($filters['deposit_channel']) && $this->hasColumn('bank_payment', 'channel')) {
                    $query->where('channel', $filters['deposit_channel']);
                }
            });
        $this->applyDateTimeWindow($query, $dateColumn, $startDate, $endDate);

        return (int) $query->count();
    }

    private function registerRepeatDepositCount(array $filters, string $startDate, string $endDate): int
    {
        if (!$this->hasTable('bank_payment')) {
            return 0;
        }

        $memberKey = $this->memberKeyColumn();
        $memberDateColumn = $this->memberDateColumn();
        $paymentMemberKey = $this->paymentMemberKeyColumn();
        $paymentDateColumn = $this->paymentDateColumn();

        if (!$this->hasColumn('bank_payment', $paymentMemberKey)) {
            return 0;
        }

        $query = app('Gametech\\Member\\Repositories\\MemberRepository')->getModel()->newQuery();
        $query = $this->applyMemberFilters($query, $filters);
        $this->applyDateTimeWindow($query, "members.{$memberDateColumn}", $startDate, $endDate);

        $query->whereExists(function ($q) use ($startDate, $endDate, $paymentMemberKey, $memberKey, $paymentDateColumn, $filters) {
            $q->select(DB::raw(1))
                ->from('bank_payment as bp_range')
                ->whereColumn('bp_range.' . $paymentMemberKey, 'members.' . $memberKey)
                ->where('bp_range.enable', 'Y')
                ->where('bp_range.status', 1)
                ->where('bp_range.value', '>', 0);
            $this->applyDateTimeWindow($q, 'bp_range.' . $paymentDateColumn, $startDate, $endDate);

            if (!empty($filters['deposit_channel']) && $this->hasColumn('bank_payment', 'channel')) {
                $q->where('bp_range.channel', $filters['deposit_channel']);
            }
        });

        $lifetimeRepeatQuery = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
            ->income()
            ->active()
            ->where('status', 1)
            ->where('value', '>', 0)
            ->whereNotNull($paymentMemberKey)
            ->where($paymentMemberKey, '>', 0);
        $lifetimeRepeatQuery = $this->applyPaymentFilters($lifetimeRepeatQuery, $filters);
        $lifetimeRepeatSub = $lifetimeRepeatQuery
            ->selectRaw("{$paymentMemberKey} as member_key")
            ->groupBy($paymentMemberKey)
            ->havingRaw('COUNT(*) >= 2')
            ->toBase();

        return (int) $query
            ->joinSub($lifetimeRepeatSub, 'life_repeat', function ($join) use ($memberKey) {
                $join->on('life_repeat.member_key', '=', 'members.' . $memberKey);
            })
            ->count();
    }

    private function repeatDepositCount(array $filters, string $startDate, string $endDate): int
    {
        if (!$this->hasTable('bank_payment') || !$this->hasColumn('bank_payment', 'member_topup')) {
            return 0;
        }

        $paymentMemberKey = $this->paymentMemberKeyColumn();
        $paymentDateColumn = $this->paymentDateColumn();

        $daySuccessQuery = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
            ->income()
            ->active()
            ->where('status', 1)
            ->where('value', '>', 0)
            ->whereNotNull($paymentMemberKey)
            ->where($paymentMemberKey, '>', 0);
        $this->applyDateTimeWindow($daySuccessQuery, $paymentDateColumn, $startDate, $endDate);
        $daySuccessQuery = $this->applyPaymentFilters($daySuccessQuery, $filters);
        $daySuccessSub = $daySuccessQuery
            ->selectRaw("{$paymentMemberKey} as member_key")
            ->groupBy($paymentMemberKey)
            ->toBase();

        $lifetimeRepeatQuery = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
            ->income()
            ->active()
            ->where('status', 1)
            ->where('value', '>', 0)
            ->whereNotNull($paymentMemberKey)
            ->where($paymentMemberKey, '>', 0);
        $lifetimeRepeatQuery = $this->applyPaymentFilters($lifetimeRepeatQuery, $filters);
        $lifetimeRepeatSub = $lifetimeRepeatQuery
            ->selectRaw("{$paymentMemberKey} as member_key")
            ->groupBy($paymentMemberKey)
            ->havingRaw('COUNT(*) >= 2')
            ->toBase();

        return (int) DB::query()
            ->fromSub($daySuccessSub, 'day_success')
            ->joinSub($lifetimeRepeatSub, 'life_repeat', function ($join) {
                $join->on('life_repeat.member_key', '=', 'day_success.member_key');
            })
            ->count();
    }

    private function bonusTrendsByDay(array $filters, string $startDate, string $endDate): array
    {
        return $this->mergeNumericSeries(
            $this->bonusTrendAggregate($this->bonusSourceDefinitions('deposit'), $filters, $startDate, $endDate, 'day'),
            $this->bonusTrendAggregate($this->bonusSourceDefinitions('activity'), $filters, $startDate, $endDate, 'day')
        );
    }

    private function bonusTrendsByHour(array $filters, string $startDate, string $endDate): array
    {
        return $this->mergeNumericSeries(
            $this->bonusTrendAggregate($this->bonusSourceDefinitions('deposit'), $filters, $startDate, $endDate, 'hour'),
            $this->bonusTrendAggregate($this->bonusSourceDefinitions('activity'), $filters, $startDate, $endDate, 'hour')
        );
    }

    private function bonusTrendAggregate(
        array $sources,
        array $filters,
        string $startDate,
        string $endDate,
        string $mode
    ): array {
        $result = [];

        foreach ($sources as $source) {
            $query = $this->buildBonusSourceQuery($source, $filters, $startDate, $endDate);
            if ($query === null) {
                continue;
            }

            if ($mode === 'hour') {
                $rows = $query
                    ->selectRaw('HOUR(' . $source['date_column'] . ') as k, SUM(' . $source['amount_column'] . ') as v')
                    ->groupBy('k')
                    ->pluck('v', 'k')
                    ->toArray();
            } else {
                $rows = $query
                    ->selectRaw("DATE_FORMAT({$source['date_column']},'%Y-%m-%d') as k, SUM({$source['amount_column']}) as v")
                    ->groupBy('k')
                    ->pluck('v', 'k')
                    ->toArray();
            }

            foreach ($rows as $key => $amount) {
                $bucket = $mode === 'hour' ? (int) $key : (string) $key;
                $result[$bucket] = (float) ($result[$bucket] ?? 0) + (float) $amount;
            }
        }

        return $result;
    }

    private function mergeNumericSeries(array ...$seriesSets): array
    {
        $merged = [];

        foreach ($seriesSets as $series) {
            foreach ($series as $key => $value) {
                $merged[$key] = (float) ($merged[$key] ?? 0) + (float) $value;
            }
        }

        return $merged;
    }

    private function sourceBreakdown(array $filters, string $startDate, string $endDate): array
    {
        $dateColumn = $this->memberDateColumn();
        $query = $this->memberQuery($filters);
        $this->applyDateTimeWindow($query, $dateColumn, $startDate, $endDate);
        $total = (clone $query)->count();

        $campaign = 0;
        if ($this->hasColumn('members', 'campaign_id')) {
            $campaign = (clone $query)
                ->whereNotNull('campaign_id')
                ->where('campaign_id', '>', 0)
                ->count();
        }

        $referral = 0;
        if ($this->hasColumn('members', 'upline_code')) {
            $referralQuery = (clone $query)->where('upline_code', '>', 0);

            if ($this->hasColumn('members', 'campaign_id')) {
                $referralQuery->where(function ($q) {
                    $q->whereNull('campaign_id')->orWhere('campaign_id', 0);
                });
            }

            $referral = $referralQuery->count();
        }

        $direct = max(0, $total - $campaign - $referral);

        return [
            'direct' => $direct,
            'campaign' => $campaign,
            'referral' => $referral,
        ];
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

    private function memberDepositCountColumn(): ?string
    {
        if ($this->hasColumn('members', 'count_deposit')) {
            return 'count_deposit';
        }
        if ($this->hasColumn('members', 'deposit')) {
            return 'deposit';
        }

        return null;
    }

    private function memberKeyColumn(): string
    {
        if ($this->hasColumn('members', 'code')) {
            return 'code';
        }

        return $this->hasColumn('members', 'id') ? 'id' : 'code';
    }

    private function memberUsernameColumn(): ?string
    {
        if ($this->hasColumn('members', 'user_name')) {
            return 'user_name';
        }
        if ($this->hasColumn('members', 'username')) {
            return 'username';
        }

        return null;
    }

    private function paymentMemberKeyColumn(): string
    {
        if ($this->hasColumn('bank_payment', 'member_topup')) {
            return 'member_topup';
        }
        if ($this->hasColumn('bank_payment', 'member_code')) {
            return 'member_code';
        }

        return 'member_topup';
    }

    private function paymentKeyColumn(): string
    {
        if ($this->hasColumn('bank_payment', 'code')) {
            return 'code';
        }

        return $this->hasColumn('bank_payment', 'id') ? 'id' : 'code';
    }

    private function paymentDateColumn(): string
    {
        if ($this->hasColumn('bank_payment', 'date_create')) {
            return 'date_create';
        }
        if ($this->hasColumn('bank_payment', 'date_approve')) {
            return 'date_approve';
        }
        if ($this->hasColumn('bank_payment', 'date_topup')) {
            return 'date_topup';
        }

        return 'date_create';
    }

    private function memberName($row): string
    {
        $name = trim((string) ($row->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $first = trim((string) ($row->firstname ?? ''));
        $last = trim((string) ($row->lastname ?? ''));
        $full = trim($first . ' ' . $last);

        return $full !== '' ? $full : '-';
    }

    private function resolveRegisterChannel($row): string
    {
        if ($this->hasColumn('members', 'campaign_id') && (int) ($row->campaign_id ?? 0) > 0) {
            return 'แคมเปญ';
        }

        $hasUplineCode = $this->hasColumn('members', 'upline_code');
        $hasUplineId = $this->hasColumn('members', 'upline_id');

        $upline = 0;
        if ($hasUplineCode) {
            $upline = (int) ($row->upline_code ?? 0);
        } elseif ($hasUplineId) {
            $upline = (int) ($row->upline_id ?? 0);
        }

        if ($upline > 0) {
            return 'การแนะนำ';
        }

        return 'สมัครตรง';
    }

    private function bankLabel($bank): string
    {
        if (!$bank) {
            return '-';
        }

        $shortcode = $this->normalizeText($bank->shortcode ?? '');
        $nameTh = $this->normalizeText($bank->name_th ?? '');
        $nameEn = $this->normalizeText($bank->name_en ?? '');

        if ($shortcode !== '-' && $nameTh !== '-' && strtolower($shortcode) !== strtolower($nameTh)) {
            return $shortcode . ' - ' . $nameTh;
        }

        if ($shortcode !== '-') {
            return $shortcode;
        }

        if ($nameTh !== '-') {
            return $nameTh;
        }

        return $nameEn;
    }

    private function bankInfo($bank, $accountNo = null, $fallbackName = ''): array
    {
        $name = $this->bankLabel($bank);
        $fallback = $this->normalizeText($fallbackName);
        if ($name === '-' && $fallback !== '-') {
            $name = $fallback;
        }

        return [
            'name' => $name,
            'logo' => $this->bankLogo($bank),
            'account' => $this->normalizeText($accountNo),
        ];
    }

    private function bankLogo($bank): string
    {
        if (!$bank) {
            return '';
        }

        $filepic = trim((string) ($bank->filepic ?? ''));
        if ($filepic === '') {
            return '';
        }

        try {
            return Storage::url('bank_img/' . $filepic);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function normalizeText($value): string
    {
        $text = trim((string) $value);

        return $text === '' ? '-' : $text;
    }

    private function formatDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function formatDate($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function diffForDisplay($start, $end): string
    {
        if (empty($start) || empty($end)) {
            return '-';
        }

        try {
            $startAt = Carbon::parse($start);
            $endAt = Carbon::parse($end);
        } catch (\Throwable $e) {
            return '-';
        }

        if ($endAt->lt($startAt)) {
            [$startAt, $endAt] = [$endAt, $startAt];
        }

        $diff = $startAt->diff($endAt);
        $days = (int) $diff->days;
        $hours = (int) $diff->h;
        $minutes = (int) $diff->i;

        if ($days > 0) {
            return $days . ' วัน ' . $hours . ' ชม.';
        }
        if ($hours > 0) {
            return $hours . ' ชม. ' . $minutes . ' นาที';
        }

        return $minutes . ' นาที';
    }

    private function summarySumExpression(string $column): string
    {
        if ($this->hasColumn('dashboard_summary_daily', $column)) {
            return "COALESCE(SUM({$column}), 0)";
        }

        return '0';
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
            if (!$this->hasTable($table)) {
                $this->columnCache[$key] = false;
            } else {
                if (!array_key_exists($table, $this->columnListingCache)) {
                    $this->columnListingCache[$table] = Schema::getColumnListing($table);
                }
                $this->columnCache[$key] = in_array($column, $this->columnListingCache[$table], true);
            }
        }

        return $this->columnCache[$key];
    }
}
