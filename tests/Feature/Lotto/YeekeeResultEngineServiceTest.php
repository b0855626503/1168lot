<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\YeekeeResultEngineService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class YeekeeResultEngineServiceTest extends TestCase
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

    public function test_compute_shoots_sum_minus_position_formula_matches_expected_mapping(): void
    {
        DB::table('yeekee_rounds')->insert([
            'id' => 1,
            'market_id' => 11,
            'lotto_draw_id' => 101,
            'round_date' => '2026-04-30',
            'round_no' => 1,
            'bet_open_at' => now(),
            'bet_close_at' => now(),
            'shoot_open_at' => now(),
            'shoot_close_at' => now(),
            'result_compute_at' => now(),
            'expected_settlement_deadline_at' => now(),
            'status' => 'pending_result',
            'config_snapshot_json' => json_encode([
                'formula_config' => [
                    'preset' => 'SHOOTS_SUM_MINUS_POSITION',
                    'subtract_position' => 2,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            [
                'yeekee_round_id' => 1,
                'lotto_draw_id' => 101,
                'market_id' => 11,
                'member_id' => 1,
                'position' => 1,
                'number_text' => '54321',
                'number_value' => 54321,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'yeekee_round_id' => 1,
                'lotto_draw_id' => 101,
                'market_id' => 11,
                'member_id' => 2,
                'position' => 2,
                'number_text' => '12345',
                'number_value' => 12345,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $result = app(YeekeeResultEngineService::class)->computeFromRound(1);

        $this->assertSame('54321', $result['raw_result']);
        $this->assertSame('321', $result['top_3']);
        $this->assertSame('54', $result['bottom_2']);
    }

    private function prepareSchema(): void
    {
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

        Schema::create('yeekee_shoots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->string('number_text', 5);
            $table->unsignedInteger('number_value');
            $table->dateTime('submitted_at');
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });
    }
}
