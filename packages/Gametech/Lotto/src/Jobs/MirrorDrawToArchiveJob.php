<?php

namespace Gametech\Lotto\Jobs;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\ArchiveNormalizerService;
use Gametech\Lotto\Services\ArchiveWriterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MirrorDrawToArchiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $drawId,
        public string $runId,
    ) {
        $this->onQueue('lotto');
    }

    public function handle(
        ArchiveNormalizerService $normalizer,
        ArchiveWriterService $writer,
    ): void {
        $draw = LottoDraw::with(['market', 'betSettings'])->find($this->drawId);

        if (! $draw) {
            Log::warning('MirrorDrawToArchiveJob: draw not found', [
                'draw_id' => $this->drawId,
            ]);

            return;
        }

        if ((string) $draw->status !== 'resulted') {
            Log::info('MirrorDrawToArchiveJob: draw not resulted, skipping', [
                'draw_id' => $this->drawId,
                'status' => (string) $draw->status,
            ]);

            return;
        }

        $rows = $normalizer->normalizeDraw($draw);

        if (empty($rows)) {
            Log::info('MirrorDrawToArchiveJob: no rows to archive', [
                'draw_id' => $this->drawId,
            ]);

            return;
        }

        $result = $writer->writeArchive($rows, 'internal_mirror', $draw->id, $this->runId);

        Log::info('MirrorDrawToArchiveJob: complete', [
            'draw_id' => $this->drawId,
            'run_id' => $this->runId,
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'corrected' => $result['corrected'],
        ]);
    }

    // No uniqueId() — uniqueness enforced at Writer level via lockForUpdate + same-hash skip.
}
