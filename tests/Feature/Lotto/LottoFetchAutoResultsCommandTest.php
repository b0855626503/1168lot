<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\AutoResult\AutoResultPipelineService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class LottoFetchAutoResultsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_result_sources');
        Schema::dropIfExists('configs');

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->dateTime('result_at')->nullable();
            $table->string('status', 32);
            $table->string('result_fetch_status', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_result_sources', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('configs', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->string('seamless', 1)->nullable();
        });

        DB::table('configs')->insert([
            'code' => 1,
            'seamless' => 'N',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_command_continues_processing_other_draws_after_unhandled_exception(): void
    {
        DB::table('lotto_result_sources')->insert([
            ['id' => 1, 'market_id' => 1, 'is_active' => 1],
            ['id' => 2, 'market_id' => 2, 'is_active' => 1],
            ['id' => 3, 'market_id' => 3, 'is_active' => 1],
            ['id' => 4, 'market_id' => 4, 'is_active' => 1],
        ]);

        DB::table('lotto_draws')->insert([
            ['id' => 1, 'market_id' => 1, 'result_at' => '2026-04-04 10:00:00', 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'market_id' => 2, 'result_at' => '2026-04-04 10:01:00', 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'market_id' => 3, 'result_at' => '2026-04-04 10:02:00', 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'market_id' => 4, 'result_at' => '2026-04-04 10:03:00', 'status' => 'closed', 'result_fetch_status' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $pipeline = Mockery::mock(AutoResultPipelineService::class);
        $pipeline->shouldReceive('processDraw')->once()->andReturn(['status' => 'APPLIED']);
        $pipeline->shouldReceive('processDraw')->once()->andReturn(['status' => 'VALIDATION_ERROR']);
        $pipeline->shouldReceive('processDraw')->once()->andThrow(new \RuntimeException('boom'));
        $pipeline->shouldReceive('processDraw')->once()->andReturn(['status' => 'APPLIED']);

        $this->app->instance(AutoResultPipelineService::class, $pipeline);

        $exitCode = Artisan::call('lotto:fetch-auto-results', ['--limit' => 10]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('processed=4', $output);
        $this->assertStringContainsString('unhandled_draw_exceptions=1', $output);
        $this->assertStringContainsString('- APPLIED: 2', $output);
        $this->assertStringContainsString('- UNHANDLED_EXCEPTION: 1', $output);
        $this->assertStringContainsString('- VALIDATION_ERROR: 1', $output);
    }
}
