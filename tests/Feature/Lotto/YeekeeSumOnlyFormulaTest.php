<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\Yeekee\Exceptions\YeekeeFormulaInputException;
use Gametech\Lotto\Services\Yeekee\Formulas\Presets\ShootsSumMinusPositionFormula;
use Gametech\Lotto\Services\Yeekee\Formulas\Presets\YeekeeSumOnlyFormula;
use Gametech\Lotto\Services\YeekeeResultEngineService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class YeekeeSumOnlyFormulaTest extends TestCase
{
    private YeekeeSumOnlyFormula $formula;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formula = new YeekeeSumOnlyFormula;
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yeekee_market_settings');
        Schema::dropIfExists('yeekee_shoots');
        Schema::dropIfExists('yeekee_rounds');
        parent::tearDown();
    }

    public function test_key_returns_expected_preset_identifier(): void
    {
        $this->assertSame('SHOOTS_SUM_ONLY', $this->formula->key());
    }

    public function test_compute_sums_all_shot_values_and_applies_default_modulo(): void
    {
        // 10001 + 20002 + 30003 = 60006 → 60006 % 100000 = 60006 → "60006"
        $shoots = [
            ['position' => 1, 'number_text' => '10001', 'number_value' => 10001],
            ['position' => 2, 'number_text' => '20002', 'number_value' => 20002],
            ['position' => 3, 'number_text' => '30003', 'number_value' => 30003],
        ];

        $result = $this->formula->compute($shoots, []);

        $this->assertSame('60006', $result['raw_result']);
        $this->assertSame('006', $result['top_3']);
        $this->assertSame('60', $result['bottom_2']);
    }

    public function test_compute_applies_modulo_and_zero_pads_to_five_digits(): void
    {
        // 99999 + 1 = 100000 → 100000 % 100000 = 0 → "00000"
        $shoots = [
            ['position' => 1, 'number_text' => '99999', 'number_value' => 99999],
            ['position' => 2, 'number_text' => '00001', 'number_value' => 1],
        ];

        $result = $this->formula->compute($shoots, []);

        $this->assertSame('00000', $result['raw_result']);
        $this->assertSame('000', $result['top_3']);
        $this->assertSame('00', $result['bottom_2']);
    }

    public function test_compute_uses_custom_modulo_from_config(): void
    {
        // 500 + 600 = 1100 → 1100 % 1000 = 100 → "00100"
        $shoots = [
            ['position' => 1, 'number_text' => '00500', 'number_value' => 500],
            ['position' => 2, 'number_text' => '00600', 'number_value' => 600],
        ];

        $result = $this->formula->compute($shoots, ['modulo' => 1000]);

        $this->assertSame('00100', $result['raw_result']);
        $this->assertSame('100', $result['top_3']);
        $this->assertSame('00', $result['bottom_2']);
    }

    public function test_compute_uses_default_modulo_when_not_in_config(): void
    {
        // 12345 → 12345 % 100000 = 12345 → "12345"
        $shoots = [
            ['position' => 1, 'number_text' => '12345', 'number_value' => 12345],
        ];

        $result = $this->formula->compute($shoots, []);

        $this->assertSame('12345', $result['raw_result']);
    }

    public function test_compute_throws_formula_input_exception_when_no_shoots(): void
    {
        $this->expectException(YeekeeFormulaInputException::class);
        $this->expectExceptionMessage('ไม่มีข้อมูลเลขยิง');

        try {
            $this->formula->compute([], []);
        } catch (YeekeeFormulaInputException $exception) {
            $this->assertSame('FORMULA_INPUT_INSUFFICIENT', $exception->failureCode());
            throw $exception;
        }
    }

    public function test_compute_throws_invalid_argument_exception_when_modulo_is_zero(): void
    {
        $shoots = [
            ['position' => 1, 'number_text' => '12345', 'number_value' => 12345],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FORMULA_CONFIG_INVALID');

        $this->formula->compute($shoots, ['modulo' => 0]);
    }

    public function test_compute_throws_invalid_argument_exception_when_modulo_is_negative(): void
    {
        $shoots = [
            ['position' => 1, 'number_text' => '12345', 'number_value' => 12345],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FORMULA_CONFIG_INVALID');

        $this->formula->compute($shoots, ['modulo' => -1]);
    }

    public function test_engine_resolves_and_computes_shoots_sum_only_from_round(): void
    {
        // 11111 + 22222 = 33333 → 33333 % 100000 = 33333 → "33333"
        DB::table('yeekee_rounds')->insert([
            'id' => 1,
            'market_id' => 10,
            'lotto_draw_id' => 100,
            'round_date' => '2026-05-01',
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
                    'preset' => 'SHOOTS_SUM_ONLY',
                    'version' => 1,
                    'modulo' => 100000,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            [
                'yeekee_round_id' => 1,
                'lotto_draw_id' => 100,
                'market_id' => 10,
                'member_id' => 1,
                'position' => 1,
                'number_text' => '11111',
                'number_value' => 11111,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'yeekee_round_id' => 1,
                'lotto_draw_id' => 100,
                'market_id' => 10,
                'member_id' => 2,
                'position' => 2,
                'number_text' => '22222',
                'number_value' => 22222,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $result = app(YeekeeResultEngineService::class)->computeFromRound(1);

        $this->assertSame('33333', $result['raw_result']);
        $this->assertSame('333', $result['top_3']);
        $this->assertSame('33', $result['bottom_2']);
    }

    public function test_engine_cutoff_boundary_includes_equal_and_excludes_after_boundary(): void
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $shootCloseAt = Carbon::parse('2026-05-01 12:00:00', $timezone);
        $cutoffSeconds = 10;
        $cutoffAt = $shootCloseAt->copy()->subSeconds($cutoffSeconds);

        DB::table('yeekee_rounds')->insert([
            'id' => 2,
            'market_id' => 10,
            'lotto_draw_id' => 101,
            'round_date' => '2026-05-01',
            'round_no' => 2,
            'bet_open_at' => $shootCloseAt->copy()->subMinutes(15)->format('Y-m-d H:i:s'),
            'bet_close_at' => $shootCloseAt->copy()->subMinutes(1)->format('Y-m-d H:i:s'),
            'shoot_open_at' => $shootCloseAt->copy()->subMinutes(1)->format('Y-m-d H:i:s'),
            'shoot_close_at' => $shootCloseAt->format('Y-m-d H:i:s'),
            'result_compute_at' => $shootCloseAt->copy()->addSeconds(60)->format('Y-m-d H:i:s'),
            'expected_settlement_deadline_at' => $shootCloseAt->copy()->addMinutes(5)->format('Y-m-d H:i:s'),
            'status' => 'pending_result',
            'config_snapshot_json' => json_encode([
                'formula_config' => [
                    'preset' => 'SHOOTS_SUM_ONLY',
                    'version' => 1,
                    'modulo' => 100000,
                    'input_rules' => [
                        'cutoff_seconds_before_close' => $cutoffSeconds,
                    ],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            [
                'yeekee_round_id' => 2,
                'lotto_draw_id' => 101,
                'market_id' => 10,
                'member_id' => 1,
                'position' => 1,
                'number_text' => '00010',
                'number_value' => 10,
                'submitted_at' => $cutoffAt->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'yeekee_round_id' => 2,
                'lotto_draw_id' => 101,
                'market_id' => 10,
                'member_id' => 2,
                'position' => 2,
                'number_text' => '00099',
                'number_value' => 99,
                'submitted_at' => $cutoffAt->copy()->addSecond()->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $result = app(YeekeeResultEngineService::class)->computeFromRound(2);

        $this->assertSame('00010', $result['raw_result']);
        $this->assertSame(1, (int) ($result['formula_audit']['input_summary']['included_count'] ?? 0));
    }

    public function test_bottom_2_semantics_stays_consistent_with_existing_formula_contract(): void
    {
        $sumOnlyResult = $this->formula->compute([
            ['position' => 1, 'number_text' => '12034', 'number_value' => 12034],
            ['position' => 2, 'number_text' => '00000', 'number_value' => 0],
        ], ['modulo' => 100000]);

        $legacyFormula = new ShootsSumMinusPositionFormula;
        $legacyResult = $legacyFormula->compute([
            ['position' => 1, 'number_text' => '12034', 'number_value' => 12034],
            ['position' => 2, 'number_text' => '00000', 'number_value' => 0],
        ], ['subtract_position' => 2]);

        $this->assertSame('12034', $sumOnlyResult['raw_result']);
        $this->assertSame($legacyResult['raw_result'], $sumOnlyResult['raw_result']);
        $this->assertSame($legacyResult['bottom_2'], $sumOnlyResult['bottom_2']);
        $this->assertSame(substr($sumOnlyResult['raw_result'], 0, 2), $sumOnlyResult['bottom_2']);
    }

    public function test_engine_rejects_include_status_and_exclude_cancelled_for_v1(): void
    {
        DB::table('yeekee_rounds')->insert([
            'id' => 3,
            'market_id' => 10,
            'lotto_draw_id' => 102,
            'round_date' => '2026-05-01',
            'round_no' => 3,
            'bet_open_at' => now(),
            'bet_close_at' => now(),
            'shoot_open_at' => now(),
            'shoot_close_at' => now(),
            'result_compute_at' => now(),
            'expected_settlement_deadline_at' => now(),
            'status' => 'pending_result',
            'config_snapshot_json' => json_encode([
                'formula_config' => [
                    'preset' => 'SHOOTS_SUM_ONLY',
                    'version' => 1,
                    'modulo' => 100000,
                    'input_rules' => [
                        'include_status' => ['accepted'],
                    ],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FORMULA_CONFIG_INVALID');
        app(YeekeeResultEngineService::class)->computeFromRound(3);
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
            $table->unsignedInteger('last_shoot_position')->default(0);
            $table->unsignedInteger('shoot_count')->default(0);
            $table->json('config_snapshot_json')->nullable();
            $table->json('shoot_snapshot_json')->nullable();
            $table->string('shoot_snapshot_hash', 128)->nullable();
            $table->dateTime('shoot_closed_at')->nullable();
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
