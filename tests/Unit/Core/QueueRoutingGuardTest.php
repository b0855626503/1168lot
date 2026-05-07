<?php

namespace Tests\Unit\Core;

use Tests\TestCase;

class QueueRoutingGuardTest extends TestCase
{
    public function test_dashboard_summary_bucket_routes_to_default_queue(): void
    {
        $contents = file_get_contents(base_path('app/Services/Dashboard/DashboardSummarySyncService.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString(")->onQueue('default');", $contents);
        $this->assertStringNotContainsString(")->onQueue('broadcast')", $contents);
        $this->assertStringNotContainsString(")->onQueue('broadcasts')", $contents);
    }

    public function test_horizon_production_queue_set_isolated_and_single_worker_each(): void
    {
        $config = require base_path('config/horizon.php');
        $production = $config['environments']['production'] ?? [];

        $this->assertSame(['broadcast'], $production['supervisor-broadcast']['queue'] ?? []);
        $this->assertSame(['topup'], $production['supervisor-topup']['queue'] ?? []);
        $this->assertSame(['bank'], $production['supervisor-bank']['queue'] ?? []);
        $this->assertSame(['lotto'], $production['supervisor-lotto']['queue'] ?? []);
        $this->assertSame(['default'], $production['supervisor-default']['queue'] ?? []);
        $this->assertSame(['low'], $production['supervisor-low']['queue'] ?? []);

        $this->assertSame(1, $production['supervisor-broadcast']['minProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-broadcast']['maxProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-topup']['minProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-topup']['maxProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-bank']['minProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-bank']['maxProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-lotto']['minProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-lotto']['maxProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-default']['minProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-default']['maxProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-low']['minProcesses'] ?? null);
        $this->assertSame(1, $production['supervisor-low']['maxProcesses'] ?? null);
    }

    public function test_legacy_queue_names_are_not_used_in_queue_routing_files(): void
    {
        $files = [
            'config/horizon.php',
            'config/queue.php',
            'app/Console/Commands/CashbackBubu.php',
            'app/Console/Commands/CashbackCalculate.php',
            'app/Console/Commands/IcCalculate.php',
            'app/Listeners/LogRequestDuration.php',
            'packages/Gametech/Admin/src/Http/Controllers/CashbackICController.php',
            'packages/Gametech/Auto/src/Console/Commands/GetPaymentAcc.php',
            'packages/Gametech/Lotto/src/Jobs/SendDrawResultSummaryTelegramJob.php',
            'packages/Gametech/Lotto/src/Observers/LottoDrawRealtimeObserver.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents(base_path($file));
            $this->assertNotFalse($contents, $file);

            $this->assertStringNotContainsString("onQueue('kbank')", $contents, $file);
            $this->assertStringNotContainsString("onQueue('cashback')", $contents, $file);
            $this->assertStringNotContainsString("onQueue('ic')", $contents, $file);
            $this->assertStringNotContainsString("'redis:kbank'", $contents, $file);
            $this->assertStringNotContainsString("'redis:cashback'", $contents, $file);
            $this->assertStringNotContainsString("'redis:ic'", $contents, $file);
        }
    }

    public function test_lotto_telegram_jobs_route_to_lotto_queue(): void
    {
        $jobContents = file_get_contents(base_path('packages/Gametech/Lotto/src/Jobs/SendDrawResultSummaryTelegramJob.php'));
        $observerContents = file_get_contents(base_path('packages/Gametech/Lotto/src/Observers/LottoDrawRealtimeObserver.php'));
        $autoResultConfigContents = file_get_contents(base_path('config/lotto_auto_result.php'));

        $this->assertNotFalse($jobContents);
        $this->assertNotFalse($observerContents);
        $this->assertNotFalse($autoResultConfigContents);
        $this->assertStringContainsString("onQueue('lotto')", $jobContents);
        $this->assertStringContainsString("onQueue('lotto')", $observerContents);
        $this->assertStringContainsString(
            "'telegram_queue' => (string) env('LOTTO_AUTO_RESULT_ALERT_QUEUE', 'lotto')",
            $autoResultConfigContents
        );
    }
}
