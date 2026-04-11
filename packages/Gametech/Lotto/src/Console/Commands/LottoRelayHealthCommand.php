<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Gametech\Lotto\Services\Relay\LotteryRelayStream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LottoRelayHealthCommand extends Command
{
    protected $signature = 'lotto:relay:health
        {--type= : Inspect latest published marker for a specific canonical type}
        {--date= : Inspect published checksum marker for type + business date}';

    protected $description = 'Show minimum operational health for lottery relay runtime, queue, and latest publish markers';

    public function handle(LotteryRelayRuntime $runtime, LotteryRelayStream $stream): int
    {
        $type = strtolower(trim((string) $this->option('type')));
        $date = trim((string) $this->option('date'));

        $this->table(['key', 'value'], [
            ['enabled', $runtime->enabled() ? 'true' : 'false'],
            ['mode', $runtime->mode()],
            ['stream_connection', $runtime->streamConnection()],
            ['stream_key', $runtime->streamKey()],
            ['queue', $runtime->queue()],
            ['consumer_group', $runtime->consumerGroup()],
            ['consumer_name', $runtime->consumerName()],
            ['api_base_url', $runtime->apiBaseUrl()],
            ['failed_jobs_total', (string) DB::table(config('queue.failed.table', 'failed_jobs'))->count()],
        ]);

        if ($type !== '') {
            $latest = $stream->get(
                $runtime->streamConnection(),
                sprintf('%s:%s', $runtime->latestMarkerPrefix(), $type)
            );
            $this->line('latest_marker: '.($latest ?: 'null'));
        }

        if ($type !== '' && $date !== '') {
            $checksum = $stream->get(
                $runtime->streamConnection(),
                sprintf('%s:%s:%s', $runtime->publishedMarkerPrefix(), $type, $date)
            );
            $this->line('published_checksum: '.($checksum ?: 'null'));
        }

        return self::SUCCESS;
    }
}
