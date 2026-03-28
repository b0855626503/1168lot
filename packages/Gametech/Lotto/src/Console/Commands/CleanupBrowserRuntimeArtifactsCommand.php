<?php

namespace Gametech\Lotto\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupBrowserRuntimeArtifactsCommand extends Command
{
    protected $signature = 'lotto:cleanup-browser-runtime-artifacts
        {--days= : Keep browser-runtime artifacts in last N days (default from config)}
        {--dry-run : Preview only, do not delete}';

    protected $description = 'Cleanup browser-runtime artifacts by retention policy (non hot-path)';

    public function handle(): int
    {
        $baseDir = rtrim((string) config('lotto_auto_result.browser_runtime.artifacts.base_dir', storage_path('app/lotto/browser-runtime')), '/');
        if ($baseDir === '' || ! is_dir($baseDir)) {
            $this->line(sprintf('skip: artifact base directory not found (%s)', $baseDir));
            return 0;
        }

        $days = $this->resolveDays();
        if ($days < 1) {
            $this->error('--days must be greater than 0');
            return 1;
        }

        $cutoff = CarbonImmutable::now()->subDays($days)->startOfDay();
        $targets = $this->collectTargets($baseDir, $cutoff);
        if ($targets === []) {
            $this->line(sprintf('no artifact directories older than %s', $cutoff->toDateString()));
            return 0;
        }

        $totalDateDirs = count($targets);
        $totalRunDirs = array_sum(array_column($targets, 'run_count'));
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->line(sprintf(
                '[dry-run] delete %d date directories (%d run directories) older than %s',
                $totalDateDirs,
                $totalRunDirs,
                $cutoff->toDateString()
            ));

            return 0;
        }

        $deletedDateDirs = 0;
        $deletedRunDirs = 0;

        foreach ($targets as $target) {
            if (File::deleteDirectory($target['path'])) {
                $deletedDateDirs++;
                $deletedRunDirs += (int) $target['run_count'];
            }
        }

        $this->info(sprintf(
            'deleted %d/%d date directories (%d run directories) older than %s',
            $deletedDateDirs,
            $totalDateDirs,
            $deletedRunDirs,
            $cutoff->toDateString()
        ));

        return 0;
    }

    private function resolveDays(): int
    {
        $option = $this->option('days');
        if ($option !== null && $option !== '') {
            return (int) $option;
        }

        return (int) config('lotto_auto_result.browser_runtime.artifacts.retention_days', 7);
    }

    /**
     * @return array<int, array{path:string, run_count:int}>
     */
    private function collectTargets(string $baseDir, CarbonImmutable $cutoff): array
    {
        $targets = [];
        foreach (File::directories($baseDir) as $yearDir) {
            $year = basename($yearDir);
            if (! ctype_digit($year)) {
                continue;
            }

            foreach (File::directories($yearDir) as $monthDir) {
                $month = basename($monthDir);
                if (! ctype_digit($month)) {
                    continue;
                }

                foreach (File::directories($monthDir) as $dayDir) {
                    $day = basename($dayDir);
                    if (! ctype_digit($day)) {
                        continue;
                    }

                    $date = CarbonImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day));
                    if ($date === false || $date->greaterThanOrEqualTo($cutoff)) {
                        continue;
                    }

                    $targets[] = [
                        'path' => $dayDir,
                        'run_count' => $this->countRunDirectories($dayDir),
                    ];
                }
            }
        }

        return $targets;
    }

    private function countRunDirectories(string $dayDir): int
    {
        $count = 0;
        foreach (File::directories($dayDir) as $sourceDir) {
            foreach (File::directories($sourceDir) as $drawDir) {
                foreach (File::directories($drawDir) as $runDir) {
                    if (str_starts_with(basename($runDir), 'run_')) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
