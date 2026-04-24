<?php

use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_markets')) {
            return;
        }

        if (! Schema::hasColumn('lotto_markets', 'draw_schedule_type')) {
            return;
        }

        $markets = DB::table('lotto_markets')
            ->select(['id', 'draw_mode'])
            ->orderBy('id')
            ->get();

        foreach ($markets as $market) {
            $mapping = $this->mapLegacyDrawMode((string) ($market->draw_mode ?? LotteryMarket::DRAW_MODE_MANUAL));

            DB::table('lotto_markets')
                ->where('id', (int) $market->id)
                ->update([
                    'draw_schedule_type' => $mapping['draw_schedule_type'],
                    'draw_days' => json_encode($mapping['draw_days'], JSON_UNESCAPED_UNICODE),
                    'draw_dates' => json_encode($mapping['draw_dates'], JSON_UNESCAPED_UNICODE),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally no-op to preserve backward-compatible state.
    }

    /**
     * @return array{draw_schedule_type:string,draw_days:array<int,int>,draw_dates:array<int,int>}
     */
    private function mapLegacyDrawMode(string $drawMode): array
    {
        if ($drawMode === LotteryMarket::DRAW_MODE_DAILY) {
            return [
                'draw_schedule_type' => LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY,
                'draw_days' => [1, 2, 3, 4, 5, 6, 7],
                'draw_dates' => [],
            ];
        }

        if ($drawMode === LotteryMarket::DRAW_MODE_WEEKDAYS) {
            return [
                'draw_schedule_type' => LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY,
                'draw_days' => [1, 2, 3, 4, 5],
                'draw_dates' => [],
            ];
        }

        if ($drawMode === LotteryMarket::DRAW_MODE_WED_SAT_SUN) {
            return [
                'draw_schedule_type' => LotteryMarket::DRAW_SCHEDULE_TYPE_WEEKLY,
                'draw_days' => [3, 6, 7],
                'draw_dates' => [],
            ];
        }

        return [
            'draw_schedule_type' => LotteryMarket::DRAW_SCHEDULE_TYPE_MANUAL,
            'draw_days' => [],
            'draw_dates' => [],
        ];
    }
};
