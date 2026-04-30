<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GenerateYeekeeRoundsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('yeekee_market_settings');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');

        parent::tearDown();
    }

    public function test_generate_yeekee_rounds_creates_rows_once_and_is_idempotent(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Main',
            'code' => 'main',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 11,
            'group_id' => 1,
            'name' => 'Yeekee Market',
            'code' => 'yeekee_market',
            'result_mode' => 'yeekee',
            'draw_mode' => 'manual',
            'draw_schedule_type' => 'manual',
            'is_enabled' => 1,
        ]);

        DB::table('yeekee_market_settings')->insert([
            'market_id' => 11,
            'round_config' => json_encode([
                'shoot_window_after_bet_close_seconds' => 60,
                'settlement_delay_after_shoot_close_seconds' => 60,
                'expected_payout_sla_minutes' => 5,
            ]),
            'formula_config' => null,
            'reward_config' => null,
            'refund_config' => null,
            'ui_config' => null,
            'reward_enabled' => 0,
            'refund_if_bet_entries_below_min' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 101,
            'market_id' => 11,
            'draw_date' => '2026-04-30',
            'open_at' => '2026-04-30 10:00:00',
            'close_at' => '2026-04-30 10:15:00',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('lotto:generate-yeekee-rounds', [
            '--date' => '2026-04-30',
        ]);

        $this->assertSame(1, DB::table('yeekee_rounds')->count());

        Artisan::call('lotto:generate-yeekee-rounds', [
            '--date' => '2026-04-30',
        ]);

        $this->assertSame(1, DB::table('yeekee_rounds')->count());
    }

    private function prepareSchema(): void
    {
        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort')->default(0);
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('result_mode', 32)->default('normal');
            $table->string('draw_mode')->nullable();
            $table->string('draw_schedule_type')->nullable();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date');
            $table->dateTime('open_at');
            $table->dateTime('close_at');
            $table->dateTime('result_at')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'resulted'])->default('draft');
            $table->json('result_number')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('yeekee_market_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->json('round_config')->nullable();
            $table->json('formula_config')->nullable();
            $table->json('reward_config')->nullable();
            $table->json('refund_config')->nullable();
            $table->json('ui_config')->nullable();
            $table->boolean('reward_enabled')->default(false);
            $table->boolean('refund_if_bet_entries_below_min')->default(false);
            $table->timestamps();
        });

        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->date('round_date');
            $table->unsignedInteger('round_no')->default(1);
            $table->dateTime('bet_open_at');
            $table->dateTime('bet_close_at');
            $table->dateTime('shoot_open_at');
            $table->dateTime('shoot_close_at');
            $table->dateTime('result_compute_at');
            $table->dateTime('expected_settlement_deadline_at');
            $table->string('status', 32)->default('draft');
            $table->json('config_snapshot_json')->nullable();
            $table->timestamps();
        });
    }
}
