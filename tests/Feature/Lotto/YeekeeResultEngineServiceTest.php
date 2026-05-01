<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\YeekeeResultEngineService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
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
        Schema::dropIfExists('yeekee_market_settings');
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

    public function test_compute_uses_round_snapshot_not_live_market_setting(): void
    {
        DB::table('yeekee_rounds')->insert([
            'id' => 2,
            'market_id' => 99,
            'lotto_draw_id' => 102,
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
        DB::table('yeekee_market_settings')->insert([
            'market_id' => 99,
            'formula_config' => json_encode(['default_preset' => 'PRECOMMITTED_BASE64_MD5']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->insertBasicShoots(2, 102, 99);

        $result = app(YeekeeResultEngineService::class)->computeFromRound(2);

        $this->assertSame('54321', $result['raw_result']);
    }

    public function test_compute_throws_clear_exception_when_formula_preset_not_supported(): void
    {
        DB::table('yeekee_rounds')->insert([
            'id' => 3,
            'market_id' => 11,
            'lotto_draw_id' => 103,
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
                'formula_config' => ['preset' => 'PRECOMMITTED_BASE64_MD5'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->insertBasicShoots(3, 103, 11);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported yeekee formula preset: PRECOMMITTED_BASE64_MD5');

        app(YeekeeResultEngineService::class)->computeFromRound(3);
    }

    public function test_compute_legacy_round_without_formula_config_falls_back_and_logs_warning(): void
    {
        Log::spy();

        DB::table('yeekee_rounds')->insert([
            'id' => 4,
            'market_id' => 11,
            'lotto_draw_id' => 104,
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
                'round_config' => ['round_duration_minutes' => 15],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->insertBasicShoots(4, 104, 11, 16);

        $result = app(YeekeeResultEngineService::class)->computeFromRound(4);

        $this->assertNotSame('', $result['raw_result']);
        Log::shouldHaveReceived('warning')->once()->with(
            'yeekee.result_engine.legacy_formula_fallback',
            \Mockery::on(static function (array $context): bool {
                return (int) ($context['yeekee_round_id'] ?? 0) === 4
                    && (int) ($context['lotto_draw_id'] ?? 0) === 104
                    && (string) ($context['fallback_preset'] ?? '') === 'SHOOTS_SUM_MINUS_POSITION'
                    && (string) ($context['reason'] ?? '') === 'missing formula_config';
            })
        );
    }

    private function insertBasicShoots(int $roundId, int $drawId, int $marketId, int $count = 2): void
    {
        $rows = [];
        for ($index = 1; $index <= $count; $index++) {
            $numberValue = $index === 1 ? 54321 : ($index === 2 ? 12345 : 10000 + $index);
            $rows[] = [
                'yeekee_round_id' => $roundId,
                'lotto_draw_id' => $drawId,
                'market_id' => $marketId,
                'member_id' => $index,
                'position' => $index,
                'number_text' => str_pad((string) $numberValue, 5, '0', STR_PAD_LEFT),
                'number_value' => $numberValue,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('yeekee_shoots')->insert($rows);
    }

    private function prepareSchema(): void
    {
        Schema::create('yeekee_market_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->json('formula_config')->nullable();
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
