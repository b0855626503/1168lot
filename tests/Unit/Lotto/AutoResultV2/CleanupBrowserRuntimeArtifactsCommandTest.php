<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Console\Commands\CleanupBrowserRuntimeArtifactsCommand;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class CleanupBrowserRuntimeArtifactsCommandTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = storage_path('framework/testing/browser-runtime-artifacts-' . uniqid('', true));
        config()->set('lotto_auto_result.browser_runtime.artifacts.base_dir', $this->baseDir);
        config()->set('lotto_auto_result.browser_runtime.artifacts.retention_days', 7);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->baseDir)) {
            File::deleteDirectory($this->baseDir);
        }

        parent::tearDown();
    }

    public function test_dry_run_does_not_delete_old_directories(): void
    {
        $oldDate = now()->subDays(10);
        $oldPath = $this->createRunDirectory($oldDate, 11, 22, 'dry-run-check');

        [$status, $output] = $this->runCommand(['--dry-run' => true]);

        $this->assertDirectoryExists($oldPath);
        $this->assertSame(0, $status);
        $this->assertStringContainsString('[dry-run] delete 1 date directories', $output);
    }

    public function test_command_deletes_only_directories_older_than_cutoff(): void
    {
        $oldDate = now()->subDays(10);
        $keepDate = now()->subDays(2);

        $oldRun = $this->createRunDirectory($oldDate, 10, 20, 'old');
        $keepRun = $this->createRunDirectory($keepDate, 10, 20, 'keep');

        [$status, $output] = $this->runCommand();

        $this->assertDirectoryDoesNotExist($oldRun);
        $this->assertDirectoryExists($keepRun);
        $this->assertSame(0, $status);
        $this->assertStringContainsString('deleted 1/1 date directories', $output);
    }

    private function createRunDirectory(\DateTimeInterface $date, int $sourceId, int $drawId, string $suffix): string
    {
        $path = sprintf(
            '%s/%s/source_%d/draw_%d/run_%s',
            $this->baseDir,
            $date->format('Y/m/d'),
            $sourceId,
            $drawId,
            $suffix
        );

        File::makeDirectory($path, 0775, true, true);
        File::put($path . '/meta.json', '{"ok":true}');

        return $path;
    }

    /**
     * @param array<string,mixed> $options
     * @return array{int,string}
     */
    private function runCommand(array $options = []): array
    {
        $command = new CleanupBrowserRuntimeArtifactsCommand();
        $command->setLaravel($this->app);
        $output = new BufferedOutput();
        $status = $command->run(new ArrayInput($options), $output);

        return [$status, $output->fetch()];
    }
}
