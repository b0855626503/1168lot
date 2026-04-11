<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\Relay\LotteryRelayPublisher;
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Console\Command;

class LottoRelayPublishReadyCommand extends Command
{
    protected $signature = 'lotto:relay:publish-ready
        {--draw-id= : Publish one draw by id}
        {--date= : Publish all eligible draws for one business date (Y-m-d)}
        {--market-id= : Limit by market id}
        {--type= : Limit by canonical relay type}
        {--limit=100 : Maximum eligible draws to scan}';

    protected $description = 'Backfill or republish lottery.ready events from existing APPLIED lotto draws';

    public function handle(
        LotteryRelayRuntime $runtime,
        LotteryRelayTypeRegistry $typeRegistry,
        LotteryRelayPublisher $publisher
    ): int {
        if (! $runtime->shouldPublishReadySignals()) {
            $this->error('Relay publish-ready command requires primary mode.');

            return self::FAILURE;
        }

        $drawId = (int) $this->option('draw-id');
        $date = trim((string) $this->option('date'));
        $marketId = (int) $this->option('market-id');
        $type = strtolower(trim((string) $this->option('type')));
        $limit = max(1, (int) $this->option('limit'));

        $query = LottoDraw::query()
            ->with('market:id,code')
            ->where('result_fetch_status', 'APPLIED')
            ->whereNotNull('result_hash')
            ->where('result_hash', '!=', '')
            ->orderByDesc('result_applied_at')
            ->orderByDesc('id');

        if ($drawId > 0) {
            $query->where('id', $drawId);
        }

        if ($date !== '') {
            $query->whereDate('draw_date', $date);
        }

        if ($marketId > 0) {
            $query->where('market_id', $marketId);
        }

        if ($type !== '') {
            $marketCodes = $typeRegistry->marketCodesForCanonicalType($type);
            if ($marketCodes === []) {
                $this->error('Unknown canonical relay type: '.$type);

                return self::FAILURE;
            }

            $query->whereHas('market', static function ($marketQuery) use ($marketCodes): void {
                $marketQuery->whereIn('code', $marketCodes);
            });
        }

        $draws = $query->limit($limit)->get();

        $published = 0;
        $skipped = 0;
        $rows = [];

        foreach ($draws as $draw) {
            $streamId = $publisher->publishIfReady($draw);
            $status = $streamId !== null ? 'published' : 'skipped';

            if ($streamId !== null) {
                $published++;
            } else {
                $skipped++;
            }

            $rows[] = [
                'draw_id' => (int) $draw->id,
                'market_code' => (string) data_get($draw, 'market.code', '-'),
                'draw_date' => $draw->draw_date ? $draw->draw_date->format('Y-m-d') : '-',
                'status' => $status,
                'stream_id' => $streamId ?? '-',
            ];
        }

        $this->line(sprintf(
            'Scanned=%d Published=%d Skipped=%d',
            $draws->count(),
            $published,
            $skipped
        ));

        if ($rows !== []) {
            $this->table(['draw_id', 'market_code', 'draw_date', 'status', 'stream_id'], $rows);
        }

        return self::SUCCESS;
    }
}
