<?php

namespace Gametech\Lotto\Jobs;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\AutoResult\AutoResultPipelineService;
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FetchRelayLotteryResultJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $uniqueFor = 300;

    /**
     * @var int[]
     */
    public array $backoff = [120, 300];

    public function __construct(
        public string $type,
        public string $date,
        public string $eventId,
        public string $checksum
    ) {
        $this->onQueue((string) config('lottery_result_relay.queue', 'lotto'));
    }

    public function uniqueId(): string
    {
        return sprintf('relay:%s:%s:%s', $this->type, $this->date, $this->checksum);
    }

    public function handle(
        LotteryRelayRuntime $runtime,
        LotteryRelayTypeRegistry $typeRegistry,
        AutoResultPipelineService $pipeline
    ): void {
        if (! $runtime->shouldConsumeRelayStream()) {
            Log::channel($runtime->logChannel())->info('LOTTERY_RELAY_FETCH_SKIPPED_MODE', [
                'event_id' => $this->eventId,
                'type' => $this->type,
                'date' => $this->date,
                'mode' => $runtime->mode(),
            ]);

            return;
        }

        $draw = $this->resolveDraw($typeRegistry);
        if (! $draw instanceof LottoDraw) {
            throw new RuntimeException(sprintf('Relay draw not found for type=%s date=%s', $this->type, $this->date));
        }

        $result = $pipeline->processDraw($draw, false, false, 'relay_'.$this->eventId, $this->date);

        Log::channel($runtime->logChannel())->info('LOTTERY_RELAY_FETCH_PROCESSED', [
            'event_id' => $this->eventId,
            'type' => $this->type,
            'date' => $this->date,
            'draw_id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'status' => $result['status'] ?? null,
            'attempt_no' => $result['attempt_no'] ?? null,
            'error_message' => $result['error_message'] ?? null,
        ]);
    }

    private function resolveDraw(LotteryRelayTypeRegistry $typeRegistry): ?LottoDraw
    {
        $marketCodes = $typeRegistry->marketCodesForCanonicalType($this->type);
        if ($marketCodes === []) {
            return null;
        }

        $marketIds = LotteryMarket::query()
            ->whereIn('code', $marketCodes)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($marketIds === []) {
            return null;
        }

        return LottoDraw::query()
            ->whereIn('market_id', $marketIds)
            ->whereDate('draw_date', $this->date)
            ->whereIn('status', ['closed', 'resulted'])
            ->orderByDesc('id')
            ->first();
    }
}
