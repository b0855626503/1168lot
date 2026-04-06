<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Enums\BetType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LottoProfitLossForecastReportService
{
    public function build(int $marketId, int $drawId, int $packageId): array
    {
        $draw = DB::table('lotto_draws as draws')
            ->join('lotto_markets as markets', 'markets.id', '=', 'draws.market_id')
            ->where('draws.id', $drawId)
            ->where('draws.market_id', $marketId)
            ->first([
                'draws.id',
                'draws.market_id',
                'draws.draw_date',
                'draws.status',
                'markets.group_id',
                'markets.name as market_name',
                'markets.logo as market_logo',
                'markets.icon as market_icon',
            ]);

        if (! $draw) {
            throw new \RuntimeException('ไม่พบตลาดหรือ งวดหวย ที่ระบุ');
        }

        $package = DB::table('lotto_group_packages')
            ->where('id', $packageId)
            ->where('group_id', (int) $draw->group_id)
            ->where('is_active', true)
            ->first(['id', 'name', 'image']);

        if (! $package) {
            throw new \RuntimeException('ไม่พบแพกเกจที่เลือก หรือแพกเกจไม่อยู่ในกลุ่มของตลาดนี้');
        }

        $settings = DB::table('lotto_group_package_bet_settings')
            ->where('package_id', $packageId)
            ->where('is_enabled', true)
            ->orderBy('id')
            ->get([
                'bet_type',
                'payout',
                'discount_percent',
            ])
            ->keyBy('bet_type');

        $drawBetSettings = DB::table('lotto_draw_bet_settings')
            ->where('draw_id', $drawId)
            ->get([
                'bet_type',
                'max_per_number',
            ])
            ->keyBy('bet_type');

        if ($settings->isEmpty()) {
            return [
                'draw' => $this->mapDrawContext($draw),
                'package' => $this->mapPackageContext($package),
                'columns' => [],
                'summary_rows' => [],
                'number_rows' => [],
            ];
        }

        $hasDiscountAmountAtTime = Schema::hasColumn('lotto_ticket_items', 'discount_amount_at_time');
        $hasPayableAmountAtTime = Schema::hasColumn('lotto_ticket_items', 'payable_amount_at_time');
        $hasDiscountPercentAtTime = Schema::hasColumn('lotto_ticket_items', 'discount_percent_at_time');

        $discountAmountExpression = $hasDiscountAmountAtTime
            ? 'lotto_ticket_items.discount_amount_at_time'
            : ($hasDiscountPercentAtTime
                ? '(lotto_ticket_items.amount * lotto_ticket_items.discount_percent_at_time / 100)'
                : '0');

        $netAmountExpression = $hasPayableAmountAtTime
            ? 'lotto_ticket_items.payable_amount_at_time'
            : sprintf(
                '(lotto_ticket_items.amount - (%s))',
                $discountAmountExpression
            );

        $betStats = DB::table('lotto_ticket_items')
            ->join('lotto_tickets', 'lotto_tickets.id', '=', 'lotto_ticket_items.ticket_id')
            ->where('lotto_tickets.draw_id', $drawId)
            ->where('lotto_tickets.status', '!=', 'cancelled')
            ->where('lotto_ticket_items.package_id_at_time', $packageId)
            ->groupBy('lotto_ticket_items.bet_type')
            ->get([
                'lotto_ticket_items.bet_type',
                DB::raw('COALESCE(SUM(lotto_ticket_items.amount), 0) as total_bet_amount'),
                DB::raw(sprintf(
                    'COALESCE(SUM(%s), 0) as total_discount_amount',
                    $discountAmountExpression
                )),
                DB::raw(sprintf(
                    'COALESCE(SUM(%s), 0) as total_net_amount',
                    $netAmountExpression
                )),
                DB::raw('COALESCE(SUM(lotto_ticket_items.win_amount), 0) as total_win_amount'),
            ])
            ->keyBy('bet_type');

        $numberRows = DB::table('lotto_ticket_items')
            ->join('lotto_tickets', 'lotto_tickets.id', '=', 'lotto_ticket_items.ticket_id')
            ->where('lotto_tickets.draw_id', $drawId)
            ->where('lotto_tickets.status', '!=', 'cancelled')
            ->where('lotto_ticket_items.package_id_at_time', $packageId)
            ->whereIn('bet_type', array_keys($settings->all()))
            ->groupBy('lotto_ticket_items.bet_type', 'lotto_ticket_items.number')
            ->get([
                'lotto_ticket_items.bet_type as bet_type',
                'lotto_ticket_items.number as number',
                DB::raw('COALESCE(SUM(lotto_ticket_items.amount), 0) as sold_amount'),
            ]);

        $exposureByType = $numberRows
            ->groupBy('bet_type')
            ->map(static function (Collection $rows): array {
                return $rows->mapWithKeys(static function ($row): array {
                    return [(string) $row->number => (float) $row->sold_amount];
                })->all();
            });

        $columns = collect(BetType::all())
            ->filter(fn (string $betType): bool => $settings->has($betType))
            ->map(function (string $betType) use ($settings, $betStats, $exposureByType, $drawBetSettings): array {
                $setting = $settings->get($betType);
                $drawBetSetting = $drawBetSettings->get($betType);
                $stats = $betStats->get($betType);
                $digits = $this->digitsForBetType($betType);
                $exposures = $exposureByType->get($betType, []);
                $totalBetAmount = (float) ($stats->total_bet_amount ?? 0);
                $totalDiscountAmount = (float) ($stats->total_discount_amount ?? 0);
                $totalReceiveAmount = (float) ($stats->total_net_amount ?? ($totalBetAmount - $totalDiscountAmount));
                $totalPayoutAmount = (float) ($stats->total_win_amount ?? 0);

                return [
                    'bet_type' => $betType,
                    'label' => BetType::label($betType),
                    'digits' => $digits,
                    'range_count' => 10 ** $digits,
                    'payout' => (float) ($setting->payout ?? 0),
                    'discount_percent' => (float) ($setting->discount_percent ?? 0),
                    'max_per_number' => (float) ($drawBetSetting->max_per_number ?? 0),
                    'total_bet_amount' => $totalBetAmount,
                    'total_discount_amount' => $totalDiscountAmount,
                    'total_receive_amount' => $totalReceiveAmount,
                    'total_payout_amount' => $totalPayoutAmount,
                    'total_profit_amount' => $totalReceiveAmount - $totalPayoutAmount,
                    'number_amounts' => $exposures,
                ];
            })
            ->values();

        return [
            'draw' => $this->mapDrawContext($draw),
            'package' => $this->mapPackageContext($package),
            'columns' => $columns->all(),
            'summary_rows' => $this->buildSummaryRows($columns),
            'number_rows' => $this->buildNumberRows($columns),
        ];
    }

    private function mapDrawContext(object $draw): array
    {
        return [
            'id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'market_name' => (string) ($draw->market_name ?? ''),
            'market_logo' => (string) (($draw->market_logo ?: $draw->market_icon) ?? ''),
            'draw_date' => $draw->draw_date ? date('Y-m-d', strtotime((string) $draw->draw_date)) : null,
            'draw_date_display' => $draw->draw_date ? date('d/m/Y', strtotime((string) $draw->draw_date)) : '-',
            'status' => (string) ($draw->status ?? ''),
        ];
    }

    private function mapPackageContext(object $package): array
    {
        return [
            'id' => (int) $package->id,
            'name' => (string) ($package->name ?? ''),
            'image' => (string) ($package->image ?? ''),
        ];
    }

    private function buildSummaryRows(Collection $columns): array
    {
        $rows = [
            'total_bet_amount' => 'ยอดแทง',
            'total_discount_amount' => 'ส่วนลด',
            'total_receive_amount' => 'ยอดรับ',
            'total_payout_amount' => 'ยอดจ่าย',
            'total_profit_amount' => 'ยอดสุทธิ',
        ];

        return collect($rows)->map(function (string $label, string $metric) use ($columns): array {
            return [
                'metric' => $metric,
                'label' => $label,
                'overall' => (float) $columns->sum($metric),
                'values' => $columns->mapWithKeys(static function (array $column) use ($metric): array {
                    return [
                        (string) $column['bet_type'] => (float) ($column[$metric] ?? 0),
                    ];
                })->all(),
            ];
        })->values()->all();
    }

    private function buildNumberRows(Collection $columns): array
    {
        $maxRange = (int) $columns->max('range_count');
        if ($maxRange <= 0) {
            return [];
        }

        $rows = [];
        for ($index = 0; $index < $maxRange; $index++) {
            $cells = [];

            foreach ($columns as $column) {
                $rangeCount = (int) ($column['range_count'] ?? 0);
                $digits = (int) ($column['digits'] ?? 0);

                if ($index >= $rangeCount || $digits <= 0) {
                    $cells[(string) $column['bet_type']] = [
                        'number' => '',
                        'amount' => 0.0,
                    ];
                    continue;
                }

                $number = str_pad((string) $index, $digits, '0', STR_PAD_LEFT);
                $cells[(string) $column['bet_type']] = [
                    'number' => $number,
                    'amount' => (float) (($column['number_amounts'][$number] ?? 0)),
                ];
            }

            $rows[] = [
                'index' => $index + 1,
                'cells' => $cells,
            ];
        }

        return $rows;
    }

    public function flattenNumberAmounts(Collection $columns, bool $onlyPositive = false): array
    {
        $rows = [];

        foreach ($columns as $column) {
            $betType = (string) ($column['bet_type'] ?? '');
            $label = (string) ($column['label'] ?? $betType);
            $numberAmounts = $column['number_amounts'] ?? [];

            foreach ((array) $numberAmounts as $number => $amount) {
                $value = (float) $amount;
                if ($onlyPositive && $value <= 0) {
                    continue;
                }

                $rows[] = [
                    'bet_type' => $betType,
                    'bet_type_label' => $label,
                    'number' => (string) $number,
                    'amount' => $value,
                ];
            }
        }

        usort($rows, static fn ($a, $b) => $a['amount'] <=> $b['amount']);

        return $rows;
    }

    private function digitsForBetType(string $betType): int
    {
        return match ($betType) {
            BetType::TOP_3, BetType::TOD_3 => 3,
            BetType::TOP_2, BetType::BOTTOM_2 => 2,
            BetType::RUN_TOP, BetType::RUN_BOTTOM => 1,
            default => 0,
        };
    }
}
