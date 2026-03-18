<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Foundation\Composer;

// ⬅️ ใช้คลาส Composer ของ Laravel
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class RunGlobalTask implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    // กันซ้ำ 5 นาทีต่อเว็บ/แอ็กชัน
    public int $uniqueFor = 300;

    // ปรับตามต้องการ
    public int $tries = 1;
    public int $backoff = 10;

    // public int $timeout = 120;

    public function __construct(public array $payload = [])
    {
        // ❌ อย่าตั้ง onQueue/onConnection ที่นี่
        // ปล่อยให้ผู้ dispatch ระบุ (หรือใช้ค่า default ของ connection=fanout ที่ตั้งใน queue.php)
    }

    /** key กันซ้ำ: site + action (+ ตัวแปรเพิ่มถ้าต้องให้รันซ้ำได้) */
    public function uniqueId(): string
    {
        $site = Str::slug((string)($this->payload['site'] ?? config('app.name', 'app')), '_');
        $action = (string)($this->payload['action'] ?? 'default');
        $uniq = $this->payload['uniq'] ?? null; // ใส่มาเมื่ออยาก force ให้รันซ้ำ
        return $site . '|' . $action . ($uniq ? '|' . $uniq : '');
    }

    public function handle(): void
    {
        $site = Str::slug((string)($this->payload['site'] ?? config('app.name', 'app')), '_');
        $action = $this->payload['action'] ?? null;

        Log::info('RunGlobalTask start', [
            'site' => $site,
            'base' => base_path(),
            'conn' => $this->connection, // ควรเป็น 'fanout'
            'queue' => $this->queue,      // เช่น broadcasts:{APP}
            'action' => $action,
            'payload' => $this->payload,
        ]);

        try {
            $exit = 0;

            switch ($action) {
                case 'optimize:clear':
                    $exit = Artisan::call('optimize:clear');
                    break;

                case 'cache:clear':
                    $exit = Artisan::call('cache:clear');
                    break;

                case 'config:cache':
                    $exit = Artisan::call('config:cache');
                    break;

                case 'event:cache':
                    $exit = Artisan::call('event:cache');
                    break;

                case 'migrate':
                    $exit = Artisan::call('migrate', ['--force' => true]);
                    break;

                case 'queue:restart':
                    $exit = Artisan::call('queue:restart');
                    break;

                case 'horizon:terminate':
                    $exit = Artisan::call('horizon:terminate');
                    break;

                case 'composer:dump':                              // ⬅️ เพิ่ม action
                    $optimized = (bool)($this->payload['optimize'] ?? true); // true = -o
                    $exit = $this->runComposerDump($optimized);
                    break;

                default:
                    // เผื่อยิง Artisan อะไรก็ได้แบบระบุเอง
                    if (isset($this->payload['command'])) {
                        $params = (array)($this->payload['params'] ?? []);
                        $exit = Artisan::call($this->payload['command'], $params);
                    } else {
                        Log::warning('RunGlobalTask: unknown action', ['action' => $action]);
                    }
            }

            Log::info('RunGlobalTask done', [
                'site' => $site,
                'action' => $action,
                'exit' => $exit,
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            Log::error('RunGlobalTask failed', [
                'site' => $site,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function runComposerDump(bool $optimized = true): int
    {
        // หา php binary
        $php = (new PhpExecutableFinder())->find(false) ?: 'php';

        // ตัวเลือกตำแหน่ง composer (เรียงลำดับความสำคัญ)
        $candidates = array_filter([
            env('COMPOSER_BINARY'),                 // eg. /usr/bin/composer หรือ php /path/to/composer.phar
            base_path('composer.phar'),             // ถ้ามีไฟล์ phar ในโปรเจกต์
            '/usr/local/bin/composer',
            '/usr/bin/composer',
        ]);

        $cmd = null;

        foreach ($candidates as $bin) {
            if (is_file($bin)) {
                // ถ้าเป็นไฟล์ .phar ให้เรียกผ่าน php
                if (str_ends_with($bin, '.phar')) {
                    $cmd = sprintf('%s %s dump-autoload%s', $php, escapeshellarg($bin), $optimized ? ' -o' : '');
                } else {
                    // เป็นไบนารี composer ปกติ
                    $cmd = sprintf('%s dump-autoload%s', escapeshellcmd($bin), $optimized ? ' -o' : '');
                }
                break;
            }
        }

        // ถ้ายังไม่เจออะไร ลองพึ่ง PATH: "composer ..."
        if (!$cmd) {
            $cmd = 'composer dump-autoload' . ($optimized ? ' -o' : '');
        }

        // เผื่อเครื่องล็อกสิทธิ์ composer เป็น root/sudo (ตามจริงควรตั้งผ่านระบบ deploy)
        $env = [
            'COMPOSER_ALLOW_SUPERUSER' => env('COMPOSER_ALLOW_SUPERUSER', '1'),
        ];

        $process = Process::fromShellCommandline($cmd, base_path(), $env, null, 300);
        $process->run();

        Log::info('Composer dump executed', [
            'cmd' => $cmd,
            'exit' => $process->getExitCode(),
            'out' => trim($process->getOutput()),
            'err' => trim($process->getErrorOutput()),
        ]);

        return (int)$process->getExitCode();
    }
}
