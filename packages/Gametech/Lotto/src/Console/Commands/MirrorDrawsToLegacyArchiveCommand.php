<?php

namespace Gametech\Lotto\Console\Commands;

use Carbon\Carbon;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MirrorDrawsToLegacyArchiveCommand extends Command
{
    protected $signature = 'lotto:mirror-draws-to-legacy-archive
        {--window=5 : Minutes to look back for recently resulted draws}
        {--market= : Filter by market code}';

    protected $description = 'Mirror recently resulted draws from lotto_draws into lotto_result_archive_legacy_results';

    public function handle(LotteryRelayTypeRegistry $typeRegistry): int
    {
        $window = max(1, (int) $this->option('window'));
        $since = Carbon::now()->subMinutes($window);

        $query = LottoDraw::with('market')
            ->where('status', 'resulted')
            ->whereNotNull('result_number')
            ->where('updated_at', '>=', $since);

        if ($marketCode = $this->option('market')) {
            $query->whereHas('market', fn ($q) => $q->where('code', $marketCode));
        }

        $draws = $query->get();

        if ($draws->isEmpty()) {
            return self::SUCCESS;
        }

        $upserted = 0;

        foreach ($draws as $draw) {
            $market = $draw->market;
            if (! $market || $market->result_mode === LotteryMarket::RESULT_MODE_YEEKEE) {
                continue;
            }

            $canonicalType = $typeRegistry->canonicalTypeForMarketCode((string) $market->code)
                ?? (string) $market->code;

            $resultNumber = is_array($draw->result_number) ? $draw->result_number : [];
            $firstPrize = (string) ($resultNumber['first_prize'] ?? '');
            $last2 = (string) ($resultNumber['last_2_digits'] ?? ($resultNumber['bottom_2'] ?? ''));
            $top3 = (string) ($resultNumber['top_3'] ?? '');
            $top2 = (string) ($resultNumber['top_2'] ?? '');
            $bottom2 = (string) ($resultNumber['bottom_2'] ?? $last2);

            $lottosNumber = $firstPrize !== '' ? $firstPrize : (
                $top3 !== '' ? $top3 : ''
            );
            $lottosUnder = $last2 !== '' ? $last2 : $bottom2;

            if ($lottosNumber === '') {
                continue;
            }

            $requestDate = $draw->draw_date instanceof \DateTimeInterface
                ? $draw->draw_date->format('Y-m-d')
                : (string) $draw->draw_date;

            $uniqueKey = hash('sha256', implode('|', [
                $canonicalType,
                $requestDate,
                'draw_'.$draw->id,
            ]));

            LottoResultArchiveLegacyResult::updateOrCreate(
                ['unique_key' => $uniqueKey],
                [
                    'type' => $canonicalType,
                    'name_th' => $market->name,
                    'request_date' => $requestDate,
                    'page' => 1,
                    'source_result_id' => $draw->id,
                    'lottos_name' => $canonicalType,
                    'lottos_th' => $market->name,
                    'lottos_date' => $draw->result_at ?? $draw->draw_date,
                    'lottos_date_raw' => $requestDate,
                    'lottos_time' => $this->resolveLottosTime($draw, $resultNumber),
                    'lottos_number' => $lottosNumber,
                    'lottos_under' => $lottosUnder,
                    'market_code' => $market->code,
                    'market_id' => $market->id,
                    'fetched_at' => $draw->result_at ?? now(),
                    'fetch_status' => 'success',
                    'last_error' => null,
                ]
            );

            Cache::increment('lotto:archive:'.$canonicalType.':version');
            $upserted++;
        }

        $this->info("Mirrored {$upserted} draws to legacy archive (window={$window}m).");

        return self::SUCCESS;
    }

    /**
     * @param array<string,mixed> $resultNumber
     */
    private function resolveLottosTime(LottoDraw $draw, array $resultNumber): string
    {
        // Use time from result source (expalert/203 API) if available
        $time = (string) ($resultNumber['time'] ?? '');
        if ($time !== '' && preg_match('/^\d{2}:\d{2}$/', trim($time))) {
            return trim($time);
        }

        // Fallback: draw's expected result_at time
        if ($draw->result_at instanceof \DateTimeInterface) {
            return $draw->result_at->format('H:i');
        }

        return '';
    }
}
