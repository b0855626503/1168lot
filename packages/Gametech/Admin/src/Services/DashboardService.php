<?php

namespace Gametech\Admin\Services;

use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\DashboardWebCodeResolver;
use Carbon\Carbon;
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

            $withdrawQuery = $this->withdrawBaseQuery($filters)
                ;
            $this->applyDateTimeWindow($withdrawQuery, 'date_approve', $startDate, $endDate);

            $withdrawAmount = (float) $withdrawQuery->sum('amount');
            $withdrawCount = (int) (clone $withdrawQuery)->count();
            $withdrawUsers = (int) (clone $withdrawQuery)->distinct('member_code')->count('member_code');
            $withdrawPending = $this->withdrawBaseQuery($filters, 'waiting')
                ;
            $this->applyDateTimeWindow($withdrawPending, 'date_create', $startDate, $endDate);
            $withdrawPendingAmount = (float) $withdrawPending->sum('amount');
            $withdrawPendingCount = (int) (clone $withdrawPending)->count();

            $bonus = $this->bonusTotals($filters, $startDate, $endDate);

            $net = $depositSuccessAmount - $withdrawAmount;
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

            $staffAdd = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')
                ->active()->where('kind', 'SETWALLET')->where('credit_type', 'D');
            $this->applyDateTimeWindow($staffAdd, 'date_create', $startDate, $endDate);
            $staffAdd = $this->applyMemberRelationFilters($staffAdd, $filters);

            $staffReduce = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')
                ->active()->where('kind', 'SETWALLET')->where('credit_type', 'W');
            $this->applyDateTimeWindow($staffReduce, 'date_create', $startDate, $endDate);
            $staffReduce = $this->applyMemberRelationFilters($staffReduce, $filters);

            $addAmount = (float) $staffAdd->sum('amount');
            $reduceAmount = (float) $staffReduce->sum('amount');
            $adjustCount = (int) ((clone $staffAdd)->count() + (clone $staffReduce)->count());

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

                $withdrawQuery = $this->withdrawBaseQuery($filters);
                $this->applyDateTimeWindow($withdrawQuery, 'date_approve', $startDate, $endDate);
                $withdrawData = $withdrawQuery
                    ->selectRaw('HOUR(date_approve) as h, SUM(amount) as v')
                    ->groupBy('h')->pluck('v', 'h')->toArray();

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

            $withdrawQuery = $this->withdrawBaseQuery($filters);
            $this->applyDateTimeWindow($withdrawQuery, 'date_approve', $startDate, $endDate);
            $withdrawRows = $withdrawQuery
                ->selectRaw("DATE_FORMAT(date_approve,'%Y-%m-%d') as d, SUM(amount) as v")
                ->groupBy('d')->pluck('v', 'd')->toArray();

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

            $depositQuery = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
                ->income()->active()->whereIn('status', [0, 1])
                ->orderBy('date_create', 'desc')
                ->take(10);
            $this->applyDateTimeWindow($depositQuery, 'date_create', $startDate, $endDate);
            $depositQuery = $this->applyPaymentFilters($depositQuery, $filters);
            $depositQuery->with(['member.bank', 'bank_account.bank']);

            $deposits = $depositQuery->get()->map(function ($row) {
                $customerBank = $this->bankInfo(
                    optional($row->member)->bank,
                    optional($row->member)->acc_no ?? null,
                    $row->bankname ?? $row->bank ?? ''
                );
                $receiveBank = $this->bankInfo(
                    optional(optional($row->bank_account)->bank),
                    optional($row->bank_account)->acc_no ?? null
                );

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
                    'status' => $row->status == 1 ? 'สำเร็จ' : 'รอ',
                ];
            });

            $config = core()->getConfigData();
            if ($config->seamless == 'Y') {
                $withdrawQuery = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->active();
            } else {
                $withdrawQuery = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->active();
            }

            $withdrawQuery->whereIn('status', [0, 1]);
            $withdrawQuery = $this->applyMemberRelationFilters($withdrawQuery, $filters);
            $withdrawQuery
                ->orderByRaw('COALESCE(date_approve, date_create, date_update) DESC')
                ->take(10);
            $this->applyDateTimeWindow($withdrawQuery, 'date_create', $startDate, $endDate);
            $withdrawQuery->with(['bank_tran.bank', 'bank', 'member.bank']);

            $withdraws = $withdrawQuery->get()->map(function ($row) {
                $timeValue = $row->date_approve ?? $row->date_create ?? $row->date_update;
                $timeText = '-';
                if (!empty($timeValue) && !in_array($timeValue, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
                    try {
                        $timeText = Carbon::parse($timeValue)->format('Y-m-d H:i');
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

            $staffQuery = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')
                ->active()->where('kind', 'SETWALLET')
                ->orderBy('date_create', 'desc')
                ->take(10);
            $this->applyDateTimeWindow($staffQuery, 'date_create', $startDate, $endDate);
            $staffQuery = $this->applyMemberRelationFilters($staffQuery, $filters);

            $staff = $staffQuery->get()->map(function ($row) {
                return [
                    'time' => optional($row->date_create)->format('Y-m-d H:i'),
                    'staff' => optional($row->admin)->user_name ?? $row->user_create ?? '-',
                    'member' => optional($row->member)->user_name ?? '-',
                    'type' => $row->credit_type === 'D' ? 'เพิ่ม' : 'ลด',
                    'amount' => core()->currency($row->amount),
                ];
            });

            return [
                'deposits' => $deposits,
                'withdraws' => $withdraws,
                'registers' => $registers,
                'staff' => $staff,
            ];
        });
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

            $withdrawPending = $this->withdrawBaseQuery($filters, 'waiting')
                ->where('date_create', '<', now()->subMinutes($thresholdMinutes))
                ->count();
            if ($withdrawPending > 0) {
                $alerts[] = [
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
                    'level' => 'warning',
                    'title' => 'ฝากค้าง match',
                    'message' => "มีรายการฝากค้างเกิน {$thresholdMinutes} นาที: {$depositPendingCount} รายการ",
                ];
            }

            $summary = $this->getSummary($filters);
            if ($summary['bonus']['ratio'] >= 30) {
                $alerts[] = [
                    'level' => 'warning',
                    'title' => 'โบนัสผิดปกติ',
                    'message' => "โบนัส/ฝากสูง {$summary['bonus']['ratio']}%",
                ];
            }

            $conversion = $this->getConversion($filters);
            $netAdjust = (float) ($conversion['staff']['net_raw'] ?? 0);
            if (abs($netAdjust) >= 10000) {
                $alerts[] = [
                    'level' => $netAdjust >= 0 ? 'warning' : 'danger',
                    'title' => 'staff adjustment สูงผิดปกติ',
                    'message' => "ปรับยอดสุทธิสูง: {$conversion['staff']['net']}",
                ];
            }

            if ($conversion['referral']['total'] >= 20 && $conversion['referral']['rate'] < 30) {
                $alerts[] = [
                    'level' => 'warning',
                    'title' => 'referral สมัครเยอะแต่ไม่ฝาก',
                    'message' => "Conversion ต่ำ {$conversion['referral']['rate']}% จาก {$conversion['referral']['total']} คน",
                ];
            }

            if ($summary['net']['amount_raw'] < 0) {
                $alerts[] = [
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
                    updatedSections: ['deposit', 'withdraw', 'bonus', 'register', 'conversion', 'funnel', 'net'],
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

        $bonusDepositAmount = (float) ($current['bonus_deposit_amount'] ?? 0);
        $bonusDepositCount = (int) ($current['bonus_deposit_count'] ?? 0);
        $bonusActivityAmount = (float) ($current['bonus_activity_amount'] ?? 0);
        $bonusActivityCount = (int) ($current['bonus_activity_count'] ?? 0);
        $bonusManualAmount = (float) ($current['bonus_manual_amount'] ?? 0);
        $bonusManualCount = (int) ($current['bonus_manual_count'] ?? 0);
        $bonusAmount = (float) ($current['bonus_total_amount'] ?? 0);
        $bonusCount = (int) ($current['bonus_total_count'] ?? 0);
        $bonusRatio = $depositSuccessAmount > 0 ? round(($bonusAmount / $depositSuccessAmount) * 100, 2) : 0;

        $net = (float) ($current['net_amount'] ?? ($depositSuccessAmount - $withdrawAmount));
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

        $staffAdd = (float) ($current['staff_add_amount'] ?? 0);
        $staffReduce = (float) ($current['staff_reduce_amount'] ?? 0);
        $staffCount = (int) ($current['staff_adjust_count'] ?? 0);

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

        $row = DB::table('dashboard_summary_daily')
            ->where('web_code', $this->dashboardWebCode())
            ->whereBetween('summary_date', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(register_total), 0) as register_total,
                COALESCE(SUM(register_direct), 0) as register_direct,
                COALESCE(SUM(register_referral), 0) as register_referral,
                COALESCE(SUM(register_campaign), 0) as register_campaign,
                COALESCE(SUM(deposit_total_amount), 0) as deposit_total_amount,
                COALESCE(SUM(deposit_total_count), 0) as deposit_total_count,
                COALESCE(SUM(deposit_total_users), 0) as deposit_total_users,
                COALESCE(SUM(deposit_success_amount), 0) as deposit_success_amount,
                COALESCE(SUM(deposit_success_count), 0) as deposit_success_count,
                COALESCE(SUM(deposit_success_users), 0) as deposit_success_users,
                COALESCE(SUM(deposit_pending_amount), 0) as deposit_pending_amount,
                COALESCE(SUM(deposit_pending_count), 0) as deposit_pending_count,
                COALESCE(SUM(deposit_pending_users), 0) as deposit_pending_users,
                COALESCE(SUM(deposit_reject_amount), 0) as deposit_reject_amount,
                COALESCE(SUM(deposit_reject_count), 0) as deposit_reject_count,
                COALESCE(SUM(deposit_reject_users), 0) as deposit_reject_users,
                COALESCE(SUM(deposit_deleted_amount), 0) as deposit_deleted_amount,
                COALESCE(SUM(deposit_deleted_count), 0) as deposit_deleted_count,
                COALESCE(SUM(deposit_deleted_users), 0) as deposit_deleted_users,
                COALESCE(SUM(withdraw_total_amount), 0) as withdraw_total_amount,
                COALESCE(SUM(withdraw_total_count), 0) as withdraw_total_count,
                COALESCE(SUM(withdraw_total_users), 0) as withdraw_total_users,
                COALESCE(SUM(withdraw_pending_amount), 0) as withdraw_pending_amount,
                COALESCE(SUM(withdraw_pending_count), 0) as withdraw_pending_count,
                COALESCE(SUM(bonus_deposit_amount), 0) as bonus_deposit_amount,
                COALESCE(SUM(bonus_deposit_count), 0) as bonus_deposit_count,
                COALESCE(SUM(bonus_activity_amount), 0) as bonus_activity_amount,
                COALESCE(SUM(bonus_activity_count), 0) as bonus_activity_count,
                COALESCE(SUM(bonus_manual_amount), 0) as bonus_manual_amount,
                COALESCE(SUM(bonus_manual_count), 0) as bonus_manual_count,
                COALESCE(SUM(bonus_total_amount), 0) as bonus_total_amount,
                COALESCE(SUM(bonus_total_count), 0) as bonus_total_count,
                COALESCE(SUM(net_amount), 0) as net_amount,
                COALESCE(SUM(first_deposit_count), 0) as first_deposit_count,
                COALESCE(SUM(repeat_deposit_count), 0) as repeat_deposit_count,
                COALESCE(SUM(register_confirmed_count), 0) as register_confirmed_count,
                COALESCE(SUM(register_deposit_count), 0) as register_deposit_count,
                COALESCE(SUM(register_referral_deposit_count), 0) as register_referral_deposit_count,
                COALESCE(SUM(staff_add_amount), 0) as staff_add_amount,
                COALESCE(SUM(staff_reduce_amount), 0) as staff_reduce_amount,
                COALESCE(SUM(staff_adjust_count), 0) as staff_adjust_count
            ')
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
                        updatedSections: ['deposit', 'withdraw', 'bonus', 'register', 'conversion', 'funnel', 'net'],
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

    private function withdrawBaseQuery(array $filters, string $status = 'complete')
    {
        $config = core()->getConfigData();
        if ($config->seamless == 'Y') {
            $query = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->active();
        } else {
            $query = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->active();
        }

        if ($status === 'waiting') {
            $query->waiting();
        } elseif ($status === 'complete') {
            $query->complete();
        }

        return $this->applyMemberRelationFilters($query, $filters);
    }

    private function bonusTotals(array $filters, string $startDate, string $endDate): array
    {
        $promoQuery = app('Gametech\\Payment\\Repositories\\PaymentPromotionRepository')
            ->active()->aff();
        $this->applyDateTimeWindow($promoQuery, 'date_create', $startDate, $endDate);
        $promoQuery = $this->applyMemberRelationFilters($promoQuery, $filters);
        $promoQuery->where('credit_bonus', '>', 0);

        $billBase = app('Gametech\\Payment\\Repositories\\BillRepository')
            ->active()
            ->getpro();
        $this->applyDateTimeWindow($billBase, 'date_create', $startDate, $endDate);
        $billBase = $this->applyMemberRelationFilters($billBase, $filters);
        $billBase->where('credit_bonus', '>', 0);

        $billActivity = clone $billBase;
        $billManual = clone $billBase;

        if ($this->hasColumn('bills', 'transfer_type')) {
            $billActivity->where('transfer_type', 1);
            $billManual->where(function ($query) {
                $query->whereNull('transfer_type')->orWhere('transfer_type', '<>', 1);
            });
        } else {
            $billManual->whereRaw('1 = 0');
        }

        $promoAmount = (float) (clone $promoQuery)->sum('credit_bonus');
        $activityAmount = (float) (clone $billActivity)->sum('credit_bonus');
        $manualAmount = (float) (clone $billManual)->sum('credit_bonus');
        $promoCount = (int) (clone $promoQuery)->count();
        $activityCount = (int) (clone $billActivity)->count();
        $manualCount = (int) (clone $billManual)->count();

        return [
            'deposit_amount' => $promoAmount,
            'deposit_count' => $promoCount,
            'activity_amount' => $activityAmount,
            'activity_count' => $activityCount,
            'manual_amount' => $manualAmount,
            'manual_count' => $manualCount,
            'amount' => $promoAmount + $activityAmount + $manualAmount,
            'count' => $promoCount + $activityCount + $manualCount,
        ];
    }

    private function netCashflow(array $filters, string $startDate, string $endDate): float
    {
        $depositQuery = app('Gametech\\Payment\\Repositories\\BankPaymentRepository')
            ->income()->active()->where('status', 1);
        $this->applyDateTimeWindow($depositQuery, 'date_create', $startDate, $endDate);
        $depositQuery = $this->applyPaymentFilters($depositQuery, $filters);

        $withdrawQuery = $this->withdrawBaseQuery($filters);
        $this->applyDateTimeWindow($withdrawQuery, 'date_approve', $startDate, $endDate);

        $depositAmount = (float) $depositQuery->sum('value');
        $withdrawAmount = (float) $withdrawQuery->sum('amount');

        return $depositAmount - $withdrawAmount;
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
        $promoQuery = app('Gametech\\Payment\\Repositories\\PaymentPromotionRepository')
            ->active()->aff();
        $this->applyDateTimeWindow($promoQuery, 'date_create', $startDate, $endDate);
        $promoQuery = $this->applyMemberRelationFilters($promoQuery, $filters);

        $promo = $promoQuery
            ->selectRaw("DATE_FORMAT(date_create,'%Y-%m-%d') as d, SUM(credit_bonus) as v")
            ->groupBy('d')->pluck('v', 'd')->toArray();

        $billQuery = app('Gametech\\Payment\\Repositories\\BillRepository')
            ->active()->getpro()->where('transfer_type', 1);
        $this->applyDateTimeWindow($billQuery, 'date_create', $startDate, $endDate);
        $billQuery = $this->applyMemberRelationFilters($billQuery, $filters);

        $bill = $billQuery
            ->selectRaw("DATE_FORMAT(date_create,'%Y-%m-%d') as d, SUM(credit_bonus) as v")
            ->groupBy('d')->pluck('v', 'd')->toArray();

        $result = [];
        foreach (array_keys($promo + $bill) as $dt) {
            $result[$dt] = (float) ($promo[$dt] ?? 0) + (float) ($bill[$dt] ?? 0);
        }

        return $result;
    }

    private function bonusTrendsByHour(array $filters, string $startDate, string $endDate): array
    {
        $promoQuery = app('Gametech\\Payment\\Repositories\\PaymentPromotionRepository')
            ->active()->aff();
        $this->applyDateTimeWindow($promoQuery, 'date_create', $startDate, $endDate);
        $promoQuery = $this->applyMemberRelationFilters($promoQuery, $filters);

        $promo = $promoQuery
            ->selectRaw('HOUR(date_create) as h, SUM(credit_bonus) as v')
            ->groupBy('h')->pluck('v', 'h')->toArray();

        $billQuery = app('Gametech\\Payment\\Repositories\\BillRepository')
            ->active()->getpro()->where('transfer_type', 1);
        $this->applyDateTimeWindow($billQuery, 'date_create', $startDate, $endDate);
        $billQuery = $this->applyMemberRelationFilters($billQuery, $filters);

        $bill = $billQuery
            ->selectRaw('HOUR(date_create) as h, SUM(credit_bonus) as v')
            ->groupBy('h')->pluck('v', 'h')->toArray();

        $result = [];
        foreach (array_keys($promo + $bill) as $h) {
            $result[(int) $h] = (float) ($promo[$h] ?? 0) + (float) ($bill[$h] ?? 0);
        }

        return $result;
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
