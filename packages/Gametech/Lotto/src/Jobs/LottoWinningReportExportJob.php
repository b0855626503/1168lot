<?php

namespace Gametech\Lotto\Jobs;

use Gametech\Core\Models\Log as CoreLog;
use Gametech\Lotto\Exports\LottoWinningReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class LottoWinningReportExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        private array $rows,
        private int $roundId,
        private string $level,
        private string $format,
        private int $adminId,
        private string $adminName
    ) {}

    public function handle(): void
    {
        $filename = sprintf('winning_report_round_%d_%s_%s.%s', $this->roundId, $this->level, now()->format('Ymd_His'), $this->format);
        $path = 'exports/'.$filename;

        Excel::store(
            new LottoWinningReportExport($this->rows),
            $path,
            'local',
            $this->format === 'xlsx' ? ExcelFormat::XLSX : ExcelFormat::CSV
        );

        $this->writeAuditLog('EXPORT', [
            'round_id' => $this->roundId,
            'level' => $this->level,
            'format' => $this->format,
            'file' => $path,
            'job' => static::class,
        ]);

        Log::info('lotto.winning_report.export.queued.completed', [
            'round_id' => $this->roundId,
            'level' => $this->level,
            'format' => $this->format,
            'file' => $path,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeAuditLog(string $mode, array $payload): void
    {
        $log = new CoreLog;
        $log->emp_code = $this->adminId;
        $log->mode = $mode;
        $log->menu = 'lotto_winning_report_export';
        $log->record = (string) $this->roundId;
        $log->item_before = json_encode([], JSON_UNESCAPED_UNICODE);
        $log->item = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $log->ip = null;
        $log->user_create = $this->adminName;
        $log->save();
    }
}
