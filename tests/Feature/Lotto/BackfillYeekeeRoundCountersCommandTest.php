<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillYeekeeRoundCountersCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yeekee_shoots');
        Schema::dropIfExists('yeekee_rounds');

        parent::tearDown();
    }

    public function test_backfill_updates_round_counters_from_existing_shoots(): void
    {
        DB::table('yeekee_rounds')->insert([
            ['id' => 11, 'market_id' => 1, 'last_shoot_position' => 0, 'shoot_count' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'market_id' => 1, 'last_shoot_position' => 0, 'shoot_count' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'market_id' => 2, 'last_shoot_position' => 0, 'shoot_count' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('yeekee_shoots')->insert([
            ['yeekee_round_id' => 11, 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['yeekee_round_id' => 11, 'position' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['yeekee_round_id' => 11, 'position' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['yeekee_round_id' => 13, 'position' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $exit = Artisan::call('lotto:yeekee:backfill-shoot-counters');
        $this->assertSame(0, $exit);

        $round11 = DB::table('yeekee_rounds')->where('id', 11)->first();
        $round12 = DB::table('yeekee_rounds')->where('id', 12)->first();
        $round13 = DB::table('yeekee_rounds')->where('id', 13)->first();

        $this->assertSame(5, (int) $round11->last_shoot_position);
        $this->assertSame(3, (int) $round11->shoot_count);
        $this->assertSame(0, (int) $round12->last_shoot_position);
        $this->assertSame(0, (int) $round12->shoot_count);
        $this->assertSame(3, (int) $round13->last_shoot_position);
        $this->assertSame(1, (int) $round13->shoot_count);
    }

    public function test_backfill_is_idempotent_for_same_dataset(): void
    {
        DB::table('yeekee_rounds')->insert([
            'id' => 21,
            'market_id' => 5,
            'last_shoot_position' => 0,
            'shoot_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            ['yeekee_round_id' => 21, 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['yeekee_round_id' => 21, 'position' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $firstExit = Artisan::call('lotto:yeekee:backfill-shoot-counters', ['--market_id' => 5]);
        $firstAfter = DB::table('yeekee_rounds')->where('id', 21)->first();

        $secondExit = Artisan::call('lotto:yeekee:backfill-shoot-counters', ['--market_id' => 5]);
        $secondAfter = DB::table('yeekee_rounds')->where('id', 21)->first();

        $this->assertSame(0, $firstExit);
        $this->assertSame(0, $secondExit);
        $this->assertSame((int) $firstAfter->last_shoot_position, (int) $secondAfter->last_shoot_position);
        $this->assertSame((int) $firstAfter->shoot_count, (int) $secondAfter->shoot_count);
        $this->assertSame(4, (int) $secondAfter->last_shoot_position);
        $this->assertSame(2, (int) $secondAfter->shoot_count);
    }

    private function prepareSchema(): void
    {
        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->unsignedInteger('last_shoot_position')->default(0);
            $table->unsignedInteger('shoot_count')->default(0);
            $table->timestamps();
        });

        Schema::create('yeekee_shoots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedInteger('position');
            $table->timestamps();
        });
    }
}
