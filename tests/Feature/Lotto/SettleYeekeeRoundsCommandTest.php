<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\AutoResultHardeningService;
use Gametech\Lotto\Services\DrawCancelAllRefundService;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\Relay\LotteryRelayPublisher;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Services\Yeekee\Exceptions\YeekeeFormulaInputException;
use Gametech\Lotto\Services\YeekeeResultEngineService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class SettleYeekeeRoundsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
        $this->seedData();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yeekee_shoots');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('logs');

        parent::tearDown();
    }

    public function test_settle_yeekee_rounds_processes_all_policy_paths(): void
    {
        Queue::fake();
        Log::spy();
        $this->mock(AutoResultHardeningService::class)
            ->shouldReceive('handleExhaustedTransition')
            ->zeroOrMoreTimes()
            ->andReturnNull();
        $this->mock(LotteryRelayPublisher::class)
            ->shouldReceive('publishIfReady')
            ->zeroOrMoreTimes()
            ->andReturnNull();

        $drawService = $this->mock(DrawService::class);
        $drawService->shouldReceive('syncScheduledStatuses')->once();

        $engine = $this->mock(YeekeeResultEngineService::class);
        $engine->shouldReceive('computeFromRound')
            ->once()
            ->with(3)
            ->andReturn([
                'raw_result' => '123',
                'top_3' => '123',
                'bottom_2' => '23',
            ]);
        $engine->shouldReceive('computeFromRound')
            ->once()
            ->with(4)
            ->andThrow(new YeekeeFormulaInputException('FORMULA_INPUT_INSUFFICIENT', 'missing required shoot position'));
        $engine->shouldReceive('computeFromRound')
            ->once()
            ->with(5)
            ->andThrow(new YeekeeFormulaInputException('FORMULA_INPUT_INSUFFICIENT', 'missing required shoot position'));
        $engine->shouldReceive('computeFromRound')
            ->once()
            ->with(6)
            ->andThrow(new InvalidArgumentException('FORMULA_CONFIG_INVALID: subtract_position ต้องมากกว่า 0'));

        $settlement = $this->mock(SettlementService::class);
        $settlement->shouldReceive('settleDraw')
            ->once()
            ->withArgs(function (LottoDraw $draw, array $result, string $mode): bool {
                return (int) $draw->id === 3003
                    && (string) ($result['top_3'] ?? '') === '123'
                    && (string) ($result['bottom_2'] ?? '') === '23'
                    && $mode === 'settlement';
            })
            ->andReturn([
                'draw_id' => 3003,
                'ticket_count' => 1,
            ]);

        $refund = $this->mock(DrawCancelAllRefundService::class);
        $refund->shouldReceive('cancelAllActiveTickets')
            ->twice()
            ->withArgs(function (LottoDraw $draw): bool {
                return in_array((int) $draw->id, [3002, 3004], true);
            })
            ->andReturn([
                'cancelled_tickets' => 1,
                'refunded_amount' => 100.0,
                'group_code' => 'test',
            ]);

        Artisan::call('lotto:settle-yeekee-rounds');
        $outputLines = preg_split('/\r\n|\r|\n/', trim((string) Artisan::output())) ?: [];
        $summaryLine = end($outputLines);
        $summary = is_string($summaryLine) ? json_decode($summaryLine, true) : null;

        $this->assertSame('voided', (string) DB::table('yeekee_rounds')->where('id', 1)->value('status'));
        $this->assertSame('resulted', (string) DB::table('lotto_draws')->where('id', 3001)->value('status'));
        $this->assertSame('NO_ACTIVITY', (string) DB::table('lotto_draws')->where('id', 3001)->value('result_fetch_status'));

        $this->assertSame('voided', (string) DB::table('yeekee_rounds')->where('id', 2)->value('status'));
        $this->assertSame('resulted', (string) DB::table('lotto_draws')->where('id', 3002)->value('status'));
        $this->assertSame('VOID_REFUND', (string) DB::table('lotto_draws')->where('id', 3002)->value('result_fetch_status'));

        $this->assertSame('resulted', (string) DB::table('yeekee_rounds')->where('id', 3)->value('status'));
        $this->assertSame('voided', (string) DB::table('yeekee_rounds')->where('id', 4)->value('status'));
        $this->assertSame('resulted', (string) DB::table('lotto_draws')->where('id', 3004)->value('status'));
        $this->assertSame('VOID_REFUND_FORMULA_INPUT_INSUFFICIENT', (string) DB::table('lotto_draws')->where('id', 3004)->value('result_fetch_status'));
        $this->assertSame('voided', (string) DB::table('yeekee_rounds')->where('id', 5)->value('status'));
        $this->assertSame('resulted', (string) DB::table('lotto_draws')->where('id', 3005)->value('status'));
        $this->assertSame('NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT', (string) DB::table('lotto_draws')->where('id', 3005)->value('result_fetch_status'));
        $this->assertSame('draft', (string) DB::table('yeekee_rounds')->where('id', 6)->value('status'));
        $this->assertSame('closed', (string) DB::table('lotto_draws')->where('id', 3006)->value('status'));

        $this->assertIsArray($summary);
        $this->assertSame(2, (int) ($summary['void_refund'] ?? 0));
        $this->assertSame(2, (int) ($summary['void_no_activity'] ?? 0));
        $this->assertSame(1, (int) ($summary['errors'] ?? 0));
        $this->assertArrayNotHasKey('formula_input_insufficient', $summary);

        Log::shouldHaveReceived('warning')->twice()->with(
            'yeekee.formula_failure_policy.recoverable',
            \Mockery::on(static function (array $context): bool {
                return isset(
                    $context['yeekee_round_id'],
                    $context['lotto_draw_id'],
                    $context['formula_preset'],
                    $context['failure_code'],
                    $context['result_fetch_status'],
                    $context['ticket_count'],
                    $context['shoot_count'],
                    $context['message']
                );
            })
        );
    }

    private function seedData(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 106,
            'name' => 'Yeekee Test',
            'notify_result_telegram' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_draws')->insert([
            [
                'id' => 3001,
                'market_id' => 106,
                'draw_date' => '2026-05-01',
                'open_at' => '2026-05-01 00:00:00',
                'close_at' => '2026-05-01 00:15:00',
                'result_at' => '2026-05-01 00:17:00',
                'status' => 'closed',
                'result_number' => null,
                'result_fetch_status' => null,
                'result_applied_at' => null,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3002,
                'market_id' => 106,
                'draw_date' => '2026-05-01',
                'open_at' => '2026-05-01 00:00:00',
                'close_at' => '2026-05-01 00:30:00',
                'result_at' => '2026-05-01 00:32:00',
                'status' => 'closed',
                'result_number' => null,
                'result_fetch_status' => null,
                'result_applied_at' => null,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3003,
                'market_id' => 106,
                'draw_date' => '2026-05-01',
                'open_at' => '2026-05-01 00:00:00',
                'close_at' => '2026-05-01 00:45:00',
                'result_at' => '2026-05-01 00:47:00',
                'status' => 'closed',
                'result_number' => null,
                'result_fetch_status' => null,
                'result_applied_at' => null,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3004,
                'market_id' => 106,
                'draw_date' => '2026-05-01',
                'open_at' => '2026-05-01 00:00:00',
                'close_at' => '2026-05-01 01:00:00',
                'result_at' => '2026-05-01 01:02:00',
                'status' => 'closed',
                'result_number' => null,
                'result_fetch_status' => null,
                'result_applied_at' => null,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3005,
                'market_id' => 106,
                'draw_date' => '2026-05-01',
                'open_at' => '2026-05-01 00:00:00',
                'close_at' => '2026-05-01 01:15:00',
                'result_at' => '2026-05-01 01:17:00',
                'status' => 'closed',
                'result_number' => null,
                'result_fetch_status' => null,
                'result_applied_at' => null,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3006,
                'market_id' => 106,
                'draw_date' => '2026-05-01',
                'open_at' => '2026-05-01 00:00:00',
                'close_at' => '2026-05-01 01:30:00',
                'result_at' => '2026-05-01 01:32:00',
                'status' => 'closed',
                'result_number' => null,
                'result_fetch_status' => null,
                'result_applied_at' => null,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('yeekee_rounds')->insert([
            [
                'id' => 1,
                'market_id' => 106,
                'lotto_draw_id' => 3001,
                'round_date' => '2026-05-01',
                'round_no' => 1,
                'bet_open_at' => '2026-05-01 00:00:00',
                'bet_close_at' => '2026-05-01 00:15:00',
                'shoot_open_at' => '2026-05-01 00:15:00',
                'shoot_close_at' => '2026-05-01 00:16:00',
                'result_compute_at' => '2026-05-01 00:17:00',
                'expected_settlement_deadline_at' => '2026-05-01 00:22:00',
                'status' => 'draft',
                'config_snapshot_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'market_id' => 106,
                'lotto_draw_id' => 3002,
                'round_date' => '2026-05-01',
                'round_no' => 2,
                'bet_open_at' => '2026-05-01 00:00:00',
                'bet_close_at' => '2026-05-01 00:30:00',
                'shoot_open_at' => '2026-05-01 00:30:00',
                'shoot_close_at' => '2026-05-01 00:31:00',
                'result_compute_at' => '2026-05-01 00:32:00',
                'expected_settlement_deadline_at' => '2026-05-01 00:37:00',
                'status' => 'draft',
                'config_snapshot_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'market_id' => 106,
                'lotto_draw_id' => 3003,
                'round_date' => '2026-05-01',
                'round_no' => 3,
                'bet_open_at' => '2026-05-01 00:00:00',
                'bet_close_at' => '2026-05-01 00:45:00',
                'shoot_open_at' => '2026-05-01 00:45:00',
                'shoot_close_at' => '2026-05-01 00:46:00',
                'result_compute_at' => '2026-05-01 00:47:00',
                'expected_settlement_deadline_at' => '2026-05-01 00:52:00',
                'status' => 'draft',
                'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_MINUS_POSITION']]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'market_id' => 106,
                'lotto_draw_id' => 3004,
                'round_date' => '2026-05-01',
                'round_no' => 4,
                'bet_open_at' => '2026-05-01 00:00:00',
                'bet_close_at' => '2026-05-01 01:00:00',
                'shoot_open_at' => '2026-05-01 01:00:00',
                'shoot_close_at' => '2026-05-01 01:01:00',
                'result_compute_at' => '2026-05-01 01:02:00',
                'expected_settlement_deadline_at' => '2026-05-01 01:07:00',
                'status' => 'draft',
                'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_MINUS_POSITION', 'subtract_position' => 16]]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'market_id' => 106,
                'lotto_draw_id' => 3005,
                'round_date' => '2026-05-01',
                'round_no' => 5,
                'bet_open_at' => '2026-05-01 00:00:00',
                'bet_close_at' => '2026-05-01 01:15:00',
                'shoot_open_at' => '2026-05-01 01:15:00',
                'shoot_close_at' => '2026-05-01 01:16:00',
                'result_compute_at' => '2026-05-01 01:17:00',
                'expected_settlement_deadline_at' => '2026-05-01 01:22:00',
                'status' => 'draft',
                'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_MINUS_POSITION', 'subtract_position' => 16]]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'market_id' => 106,
                'lotto_draw_id' => 3006,
                'round_date' => '2026-05-01',
                'round_no' => 6,
                'bet_open_at' => '2026-05-01 00:00:00',
                'bet_close_at' => '2026-05-01 01:30:00',
                'shoot_open_at' => '2026-05-01 01:30:00',
                'shoot_close_at' => '2026-05-01 01:31:00',
                'result_compute_at' => '2026-05-01 01:32:00',
                'expected_settlement_deadline_at' => '2026-05-01 01:37:00',
                'status' => 'draft',
                'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_MINUS_POSITION', 'subtract_position' => 0]]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('lotto_tickets')->insert([
            [
                'id' => 5001,
                'draw_id' => 3002,
                'member_id' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5002,
                'draw_id' => 3003,
                'member_id' => 2,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5003,
                'draw_id' => 3004,
                'member_id' => 3,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5004,
                'draw_id' => 3006,
                'member_id' => 4,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('yeekee_shoots')->insert([
            [
                'id' => 7001,
                'yeekee_round_id' => 3,
                'lotto_draw_id' => 3003,
                'market_id' => 106,
                'member_id' => 2,
                'position' => 1,
                'number_text' => '123',
                'number_value' => 123,
                'submitted_at' => now(),
                'ip_address' => null,
                'user_agent' => null,
                'metadata_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7002,
                'yeekee_round_id' => 4,
                'lotto_draw_id' => 3004,
                'market_id' => 106,
                'member_id' => 3,
                'position' => 1,
                'number_text' => '456',
                'number_value' => 456,
                'submitted_at' => now(),
                'ip_address' => null,
                'user_agent' => null,
                'metadata_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7003,
                'yeekee_round_id' => 5,
                'lotto_draw_id' => 3005,
                'market_id' => 106,
                'member_id' => 5,
                'position' => 1,
                'number_text' => '789',
                'number_value' => 789,
                'submitted_at' => now(),
                'ip_address' => null,
                'user_agent' => null,
                'metadata_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7004,
                'yeekee_round_id' => 6,
                'lotto_draw_id' => 3006,
                'market_id' => 106,
                'member_id' => 6,
                'position' => 1,
                'number_text' => '111',
                'number_value' => 111,
                'submitted_at' => now(),
                'ip_address' => null,
                'user_agent' => null,
                'metadata_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('notify_result_telegram')->default(false);
            $table->timestamps();
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
            $table->string('result_fetch_status', 64)->nullable();
            $table->dateTime('result_applied_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
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

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('yeekee_shoots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedInteger('position');
            $table->string('number_text', 64);
            $table->unsignedInteger('number_value');
            $table->dateTime('submitted_at');
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('emp_code')->nullable();
            $table->string('mode', 16)->nullable();
            $table->string('menu', 128)->nullable();
            $table->unsignedBigInteger('record')->nullable();
            $table->text('item_before')->nullable();
            $table->text('item')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_create', 64)->nullable();
            $table->dateTime('date_update')->nullable();
            $table->dateTime('date_create')->nullable();
        });
    }
}
