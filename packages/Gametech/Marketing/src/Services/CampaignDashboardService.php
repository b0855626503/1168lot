<?php

namespace Gametech\Marketing\Services;

use App\Services\Dashboard\LottoDashboardMetricConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CampaignDashboardService
{
    private const CACHE_TTL_SECONDS = 40;

    /**
     * @return array<string, mixed>
     */
    public function getDashboard(int|string $campaignId, string $dateStart, string $dateEnd): array
    {
        $cacheKey = "marketing:campaign-dashboard:{$campaignId}:{$dateStart}:{$dateEnd}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($campaignId, $dateStart, $dateEnd) {
            $memberCodes = $this->memberCodesForCampaign($campaignId);

            return [
                'campaign_id' => $campaignId,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'generated_at' => Carbon::now()->toIso8601String(),
                'cache_ttl' => self::CACHE_TTL_SECONDS,
                'financial' => $this->getFinancialSummary($memberCodes, $dateStart, $dateEnd),
                'lotto_cash' => $this->getLottoCash($memberCodes, $dateStart, $dateEnd),
                'lotto_product' => $this->getLottoProduct($memberCodes, $dateStart, $dateEnd),
                'register' => $this->getRegisterSummary((int) $campaignId, $dateStart, $dateEnd),
                'clicks' => $this->getClickSummary((int) $campaignId, $dateStart, $dateEnd),
                'recent_lotto_bets' => $this->getRecentLottoBets($memberCodes),
                'latest_registers' => $this->getLatestRegisters((int) $campaignId),
                'recent_clicks' => $this->getRecentClicks((int) $campaignId),
            ];
        });
    }

    /**
     * @param  string[]  $memberCodes
     * @return array<string, mixed>
     */
    public function getFinancialSummary(array $memberCodes, string $dateStart, string $dateEnd): array
    {
        if ($memberCodes === []) {
            return $this->emptyFinancial();
        }

        [$startAt, $endAt] = $this->dateRange($dateStart, $dateEnd);

        $depositQuery = DB::table('bank_payment')
            ->whereIn('member_topup', $memberCodes)
            ->where('enable', 'Y')
            ->where('status', 1)
            ->whereBetween('date_create', [$startAt, $endAt]);

        $depositAmount = (float) (clone $depositQuery)->sum('value');
        $depositCount = (int) (clone $depositQuery)->count();
        $depositMembers = (int) (clone $depositQuery)
            ->whereNotNull('member_topup')
            ->distinct('member_topup')
            ->count('member_topup');
        $bonusAmount = $this->bonusAmount($memberCodes, $startAt, $endAt);

        $firstDepositMembers = (int) DB::table('bank_payment')
            ->whereIn('member_topup', $memberCodes)
            ->where('enable', 'Y')
            ->where('status', 1)
            ->whereBetween('date_create', [$startAt, $endAt])
            ->whereNotIn('member_topup', function ($sub) use ($startAt) {
                $sub->select('member_topup')
                    ->from('bank_payment')
                    ->where('enable', 'Y')
                    ->where('status', 1)
                    ->where('date_create', '<', $startAt);
            })
            ->distinct('member_topup')
            ->count('member_topup');

        $withdrawAmount = $this->withdrawTotal($memberCodes, $startAt, $endAt);

        return [
            'deposit_amount' => $depositAmount,
            'deposit_count' => $depositCount,
            'deposit_members' => $depositMembers,
            'bonus_amount' => $bonusAmount,
            'withdraw_amount' => $withdrawAmount,
            'net_amount' => round($depositAmount - $withdrawAmount, 2),
            'first_deposit_members' => $firstDepositMembers,
        ];
    }

    /**
     * @param  string[]  $memberCodes
     * @return array<string, mixed>
     */
    public function getLottoCash(array $memberCodes, string $dateStart, string $dateEnd): array
    {
        if ($memberCodes === []) {
            return ['sales_amount' => 0.0, 'payout_amount' => 0.0, 'refund_amount' => 0.0, 'net_amount' => 0.0];
        }

        [$startAt, $endAt] = $this->dateRange($dateStart, $dateEnd);

        $memberIds = $this->memberIdsByCodes($memberCodes);

        $base = fn (string $direction, array $refTypes) => DB::table('wallet_transactions')
            ->where('status', LottoDashboardMetricConfig::WALLET_SUCCESS_STATUS)
            ->where('direction', $direction)
            ->whereIn('ref_type', $refTypes)
            ->whereIn('member_id', $memberIds)
            ->whereBetween('created_at', [$startAt, $endAt]);

        $sales = (float) $base('DEBIT', LottoDashboardMetricConfig::salesRefTypes())->sum('amount');
        $payout = (float) $base('CREDIT', LottoDashboardMetricConfig::payoutRefTypes())->sum('amount');
        $refund = (float) $base('CREDIT', LottoDashboardMetricConfig::refundRefTypes())->sum('amount');

        return [
            'sales_amount' => $sales,
            'payout_amount' => $payout,
            'refund_amount' => $refund,
            'net_amount' => round($sales - $payout - $refund, 2),
        ];
    }

    /**
     * @param  string[]  $memberCodes
     * @return array<string, mixed>
     */
    public function getLottoProduct(array $memberCodes, string $dateStart, string $dateEnd): array
    {
        if ($memberCodes === []) {
            return $this->emptyLottoProduct();
        }

        [$startAt, $endAt] = $this->dateRange($dateStart, $dateEnd);

        $base = DB::table('lotto_tickets')
            ->whereIn('member_id', $memberCodes)
            ->whereBetween('created_at', [$startAt, $endAt]);

        $ticketCount = (int) (clone $base)->count();
        $playerCount = (int) (clone $base)->distinct('member_id')->count('member_id');
        $salesAmount = (float) (clone $base)->sum('bet_amount');
        $winAmount = (float) (clone $base)->sum('total_win_amount');
        $winCount = (int) (clone $base)->where('status', 'resulted')->where('total_win_amount', '>', 0)->count();
        $loseCount = (int) (clone $base)->where('status', 'resulted')->where('total_win_amount', '<=', 0)->count();
        $pendingCount = (int) (clone $base)->where('status', 'active')->count();
        $settledCount = (int) (clone $base)->where('status', 'resulted')->count();

        return [
            'ticket_count' => $ticketCount,
            'player_count' => $playerCount,
            'sales_amount' => $salesAmount,
            'win_amount' => $winAmount,
            'profit_amount' => round($salesAmount - $winAmount, 2),
            'win_ticket_count' => $winCount,
            'lose_ticket_count' => $loseCount,
            'pending_ticket_count' => $pendingCount,
            'settled_ticket_count' => $settledCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRegisterSummary(int $campaignId, string $dateStart, string $dateEnd): array
    {
        [$startAt, $endAt] = $this->dateRange($dateStart, $dateEnd);

        $rangeCount = (int) DB::table('members')
            ->where('campaign_id', $campaignId)
            ->whereBetween('date_regis', [$startAt, $endAt])
            ->count();

        $totalCount = (int) DB::table('members')
            ->where('campaign_id', $campaignId)
            ->count();

        $withDeposit = (int) DB::table('members')
            ->where('campaign_id', $campaignId)
            ->whereBetween('date_regis', [$startAt, $endAt])
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('bank_payment')
                    ->whereColumn('bank_payment.member_topup', 'members.code')
                    ->where('bank_payment.enable', 'Y')
                    ->where('bank_payment.status', 1);
            })
            ->count();

        $rate = $rangeCount > 0 ? round($withDeposit / $rangeCount * 100, 2) : 0.0;

        return [
            'registered_in_range' => $rangeCount,
            'registered_total' => $totalCount,
            'registered_with_deposit' => $withDeposit,
            'first_deposit_rate' => $rate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getClickSummary(int $campaignId, string $dateStart, string $dateEnd): array
    {
        [$startAt, $endAt] = $this->dateRange($dateStart, $dateEnd);

        $base = DB::table('registration_link_clicks')
            ->join('registration_links', 'registration_links.id', '=', 'registration_link_clicks.registration_link_id')
            ->where('registration_links.campaign_id', $campaignId)
            ->whereBetween('registration_link_clicks.created_at', [$startAt, $endAt]);

        $total = (int) (clone $base)->count();
        $human = (int) (clone $base)->where('registration_link_clicks.classification_type', 'human')->count();
        $bot = (int) (clone $base)->where('registration_link_clicks.classification_type', 'bot')->count();
        $previewBot = (int) (clone $base)->where('registration_link_clicks.classification_type', 'preview_bot')->count();
        $suspicious = (int) (clone $base)->where('registration_link_clicks.classification_type', 'suspicious')->count();
        $unknown = (int) (clone $base)->where('registration_link_clicks.classification_type', 'unknown')->count();
        $uniqueVisitors = (int) (clone $base)->whereNotNull('registration_link_clicks.visitor_id')->distinct('registration_link_clicks.visitor_id')->count('registration_link_clicks.visitor_id');
        $converted = (int) (clone $base)->whereNotNull('registration_link_clicks.converted_member_id')->count();

        $conversionRate = $human > 0 ? round($converted / $human * 100, 2) : 0.0;

        return [
            'click_total' => $total,
            'click_human' => $human,
            'click_bot' => $bot,
            'click_preview_bot' => $previewBot,
            'click_suspicious' => $suspicious,
            'click_unknown' => $unknown,
            'unique_visitors' => $uniqueVisitors,
            'converted_count' => $converted,
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * @param  string[]  $memberCodes
     * @return array<int, array<string, mixed>>
     */
    public function getRecentLottoBets(array $memberCodes, int $limit = 20): array
    {
        if ($memberCodes === []) {
            return [];
        }

        return DB::table('lotto_tickets')
            ->join('lotto_draws', 'lotto_draws.id', '=', 'lotto_tickets.draw_id')
            ->join('lotto_markets', 'lotto_markets.id', '=', 'lotto_draws.market_id')
            ->whereIn('lotto_tickets.member_id', $memberCodes)
            ->orderByDesc('lotto_tickets.created_at')
            ->limit($limit)
            ->get([
                'lotto_tickets.id as ticket_id',
                'lotto_tickets.member_id as member_code',
                'lotto_markets.name as market_name',
                'lotto_draws.draw_date',
                'lotto_tickets.bet_amount',
                'lotto_tickets.total_win_amount as win_amount',
                'lotto_tickets.status',
                'lotto_tickets.created_at',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLatestRegisters(int $campaignId, int $limit = 20): array
    {
        return DB::table('members')
            ->where('campaign_id', $campaignId)
            ->orderByDesc('date_regis')
            ->limit($limit)
            ->get(['code', 'username', 'phone', 'date_regis'])
            ->map(function ($row) {
                return [
                    'member_code' => $row->code,
                    'username' => $row->username,
                    'phone_masked' => $this->maskPhone((string) ($row->phone ?? '')),
                    'date_regis' => $row->date_regis,
                    'has_deposit' => false,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentClicks(int $campaignId, int $limit = 20): array
    {
        return DB::table('registration_link_clicks')
            ->join('registration_links', 'registration_links.id', '=', 'registration_link_clicks.registration_link_id')
            ->where('registration_links.campaign_id', $campaignId)
            ->orderByDesc('registration_link_clicks.created_at')
            ->limit($limit)
            ->get([
                'registration_link_clicks.created_at',
                'registration_link_clicks.classification_type',
                'registration_link_clicks.classification_reason',
                'registration_link_clicks.risk_score',
                'registration_link_clicks.referrer_domain',
                'registration_link_clicks.device_type',
                'registration_link_clicks.browser_name',
                'registration_link_clicks.os_name',
                'registration_link_clicks.converted_member_id',
            ])
            ->map(fn ($row) => (array) $row + ['converted' => $row->converted_member_id !== null])
            ->all();
    }

    /**
     * @param  string[]  $memberCodes
     * @return array<int|string>
     */
    private function memberIdsByCodes(array $memberCodes): array
    {
        return DB::table('members')
            ->whereIn('code', $memberCodes)
            ->pluck('code')
            ->map(static function ($code) {
                $value = (string) $code;

                return ctype_digit($value) ? (int) $value : $value;
            })
            ->all();
    }

    /**
     * @return string[]
     */
    private function memberCodesForCampaign(int|string $campaignId): array
    {
        return DB::table('members')
            ->where('campaign_id', $campaignId)
            ->pluck('code')
            ->map(fn ($code) => (string) $code)
            ->all();
    }

    /**
     * @param  string[]  $memberCodes
     */
    private function withdrawTotal(array $memberCodes, string $startAt, string $endAt): float
    {
        $tables = ['withdraws', 'withdraws_seamless', 'withdraws_free'];
        $total = 0.0;

        foreach ($tables as $table) {
            try {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $total += (float) DB::table($table)
                        ->whereIn('member_code', $memberCodes)
                        ->where('status', 'complete')
                        ->whereBetween('date_approve', [$startAt, $endAt])
                        ->sum('amount');
                }
            } catch (\Throwable) {
                // table missing or schema differs — skip
            }
        }

        return $total;
    }

    /** @return array{0: string, 1: string} */
    private function dateRange(string $dateStart, string $dateEnd): array
    {
        return [
            Carbon::parse($dateStart)->startOfDay()->toDateTimeString(),
            Carbon::parse($dateEnd)->endOfDay()->toDateTimeString(),
        ];
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 6) {
            return $phone;
        }

        return substr($phone, 0, 3).'****'.substr($phone, -3);
    }

    /** @return array<string, mixed> */
    private function emptyFinancial(): array
    {
        return [
            'deposit_amount' => 0.0,
            'deposit_count' => 0,
            'deposit_members' => 0,
            'bonus_amount' => 0.0,
            'withdraw_amount' => 0.0,
            'net_amount' => 0.0,
            'first_deposit_members' => 0,
        ];
    }

    /**
     * @param  string[]  $memberCodes
     */
    private function bonusAmount(array $memberCodes, string $startAt, string $endAt): float
    {
        if ($memberCodes === []) {
            return 0.0;
        }

        $total = 0.0;

        if (Schema::hasTable('payments_promotion')) {
            $depositBonus = DB::table('payments_promotion')
                ->where('enable', 'Y')
                ->where('date_create', '>=', $startAt)
                ->where('date_create', '<=', $endAt);

            if (Schema::hasColumn('payments_promotion', 'credit_bonus')) {
                $depositBonus->where('credit_bonus', '>', 0);
            }

            if (Schema::hasColumn('payments_promotion', 'pro_code')) {
                $depositBonus->where('pro_code', 6);
            }

            $this->applyMemberScope($depositBonus, 'payments_promotion', $memberCodes);
            $total += (float) (clone $depositBonus)->sum('credit_bonus');
        }

        if (Schema::hasTable('bills')) {
            $billBase = DB::table('bills')
                ->where('enable', 'Y')
                ->where('date_create', '>=', $startAt)
                ->where('date_create', '<=', $endAt);

            if (Schema::hasColumn('bills', 'credit_bonus')) {
                $billBase->where('credit_bonus', '>', 0);
            }

            if (Schema::hasColumn('bills', 'pro_code')) {
                $billBase->where('pro_code', '>', 0);
            }

            $this->applyMemberScope($billBase, 'bills', $memberCodes);

            $activity = (clone $billBase);
            if (Schema::hasColumn('bills', 'transfer_type')) {
                $activity->where('transfer_type', 1);
            }

            $manual = (clone $billBase);
            if (Schema::hasColumn('bills', 'transfer_type')) {
                $manual->where(function ($query): void {
                    $query->whereNull('transfer_type')->orWhere('transfer_type', '<>', 1);
                });
            }

            $total += (float) (clone $activity)->sum('credit_bonus');
            $total += (float) (clone $manual)->sum('credit_bonus');
        }

        return round($total, 2);
    }

    /**
     * @param  string[]  $memberCodes
     */
    private function applyMemberScope($query, string $table, array $memberCodes): void
    {
        if (Schema::hasColumn($table, 'member_code')) {
            $query->whereIn("{$table}.member_code", $memberCodes);

            return;
        }

        if (Schema::hasColumn($table, 'member_topup')) {
            $query->whereIn("{$table}.member_topup", $memberCodes);

            return;
        }

        if (Schema::hasColumn($table, 'member_id')) {
            $query->whereIn("{$table}.member_id", $memberCodes);
        }
    }

    /** @return array<string, mixed> */
    private function emptyLottoProduct(): array
    {
        return [
            'ticket_count' => 0,
            'player_count' => 0,
            'sales_amount' => 0.0,
            'win_amount' => 0.0,
            'profit_amount' => 0.0,
            'win_ticket_count' => 0,
            'lose_ticket_count' => 0,
            'pending_ticket_count' => 0,
            'settled_ticket_count' => 0,
        ];
    }
}
