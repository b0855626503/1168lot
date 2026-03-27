<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Services\AutoResultHardeningService;
use Illuminate\Console\Command;

class LottoAutoResultMetricsCommand extends Command
{
    protected $signature = 'lotto:auto-result-metrics {--hours= : Metrics window in hours (default from config)}';

    protected $description = 'Show Lotto auto-result hardening metrics (success rate and retry count)';

    public function handle(AutoResultHardeningService $hardeningService): int
    {
        $hoursOption = $this->option('hours');
        $hours = null;

        if ($hoursOption !== null && $hoursOption !== '') {
            if (! is_numeric($hoursOption) || (int) $hoursOption < 1) {
                $this->error('Invalid --hours. Use integer >= 1');

                return self::FAILURE;
            }

            $hours = (int) $hoursOption;
        }

        $to = now((string) config('lotto_auto_result.timezone', (string) config('app.timezone', 'Asia/Bangkok')));
        $from = $hours !== null ? $to->copy()->subHours($hours) : null;

        $metrics = $hardeningService->metrics($from, $to);

        $this->line(json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
