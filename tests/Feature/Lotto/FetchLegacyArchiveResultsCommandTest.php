<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Console\Commands\FetchLegacyArchiveResultsCommand;
use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

/**
 * Testable subclass that overrides fetchForDate() to return controlled stub data.
 */
class TestableFetchLegacyArchiveResultsCommand extends FetchLegacyArchiveResultsCommand
{
    /** @var array<int, array<string, mixed>> */
    public array $stubRows = [];

    protected function fetchForDate(string $type, string $date): array
    {
        return $this->stubRows;
    }
}

class FetchLegacyArchiveResultsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('lotto_result_archive_legacy_results');

        Schema::create('lotto_result_archive_legacy_results', function (Blueprint $table): void {
            $table->id();

            $table->string('type', 100);
            $table->string('name_th')->nullable();
            $table->date('request_date')->nullable();
            $table->unsignedInteger('page')->nullable();

            $table->unsignedBigInteger('source_result_id')->nullable();
            $table->string('lottos_name', 100);
            $table->string('lottos_th')->nullable();
            $table->dateTime('lottos_date')->nullable();
            $table->string('lottos_date_raw', 50)->nullable();
            $table->string('lottos_time', 20)->nullable();
            $table->string('lottos_number', 50)->nullable();
            $table->string('lottos_under', 50)->nullable();

            $table->string('market_code', 100)->nullable();
            $table->unsignedBigInteger('market_id')->nullable();

            $table->text('source_url')->nullable();
            $table->dateTime('fetched_at')->nullable();
            $table->enum('fetch_status', ['success', 'not_found', 'failed'])->default('success');
            $table->text('last_error')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->json('payload_json')->nullable();

            $table->string('unique_key', 64);

            $table->timestamps();

            $table->unique('unique_key');
            $table->index(['type', 'request_date'], 'lrlar_type_date');
            $table->index('request_date', 'lrlar_request_date');
            $table->index(['lottos_name', 'request_date'], 'lrlar_name_date');
            $table->index('fetched_at', 'lrlar_fetched_at');
            $table->index('fetch_status', 'lrlar_fetch_status');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_result_archive_legacy_results');

        parent::tearDown();
    }

    public function test_command_requires_date_option(): void
    {
        $this->artisan('lotto:legacy-results:fetch')
            ->assertExitCode(1);
    }

    public function test_inverted_date_range_returns_failure(): void
    {
        $this->artisan('lotto:legacy-results:fetch', [
            '--from' => '2026-05-10',
            '--to' => '2026-05-01',
        ])->assertExitCode(1);
    }

    public function test_today_option_runs_without_error(): void
    {
        $this->artisan('lotto:legacy-results:fetch', ['--today' => true])
            ->assertExitCode(0);
    }

    public function test_command_does_not_duplicate_rows(): void
    {
        $stubRows = [
            [
                'type' => 'egx30',
                'lottos_name' => 'EGX30',
                'request_date' => '2026-05-01',
                'source_result_id' => 11111,
                'fetch_status' => 'success',
                'lottos_number' => '123',
            ],
        ];

        $command = new TestableFetchLegacyArchiveResultsCommand;
        $command->stubRows = $stubRows;
        $command->setLaravel($this->app);

        $this->app->instance(FetchLegacyArchiveResultsCommand::class, $command);

        $input = new ArrayInput([
            '--from' => '2026-05-01',
            '--to' => '2026-05-01',
            '--type' => 'egx30',
        ], $command->getDefinition());

        $output = new NullOutput;

        $command->run($input, $output);
        $this->assertSame(1, LottoResultArchiveLegacyResult::count());

        $command->run($input, $output);
        $this->assertSame(1, LottoResultArchiveLegacyResult::count());
    }

    public function test_force_flag_overwrites_existing_success_row(): void
    {
        $key = hash('sha256', 'egx30|2026-05-01|99999');

        LottoResultArchiveLegacyResult::create([
            'type' => 'egx30',
            'lottos_name' => 'EGX30',
            'request_date' => '2026-05-01',
            'source_result_id' => 99999,
            'fetch_status' => 'success',
            'lottos_number' => 'original_number',
            'unique_key' => $key,
        ]);

        $this->assertSame(1, LottoResultArchiveLegacyResult::count());

        $stubRows = [
            [
                'type' => 'egx30',
                'lottos_name' => 'EGX30',
                'request_date' => '2026-05-01',
                'source_result_id' => 99999,
                'fetch_status' => 'failed',
                'last_error' => 'forced overwrite test',
                'lottos_number' => null,
            ],
        ];

        $command = new TestableFetchLegacyArchiveResultsCommand;
        $command->stubRows = $stubRows;
        $command->setLaravel($this->app);

        $command->run(
            new ArrayInput([
                '--from' => '2026-05-01',
                '--to' => '2026-05-01',
                '--type' => 'egx30',
                '--force' => true,
            ], $command->getDefinition()),
            new NullOutput
        );

        $this->assertSame(1, LottoResultArchiveLegacyResult::count());

        $row = LottoResultArchiveLegacyResult::where('unique_key', $key)->firstOrFail();
        $this->assertSame('failed', $row->fetch_status);
        $this->assertNull($row->lottos_number);
    }
}
