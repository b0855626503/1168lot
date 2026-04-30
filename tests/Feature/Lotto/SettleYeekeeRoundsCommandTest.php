<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\DrawCancelAllRefundService;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Services\YeekeeResultEngineService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        Schema::dropIfExists('logs');

        parent::tearDown();
    }

    public function test_settle_yeekee_rounds_processes_all_policy_paths(): void
    {
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
            ->once()
            ->withArgs(function (LottoDraw $draw): bool {
                return (int) $draw->id === 3002;
            })
            ->andReturn([
                'cancelled_tickets' => 1,
                'refunded_amount' => 100.0,
                'group_code' => 'test',
            ]);

        Artisan::call('lotto:settle-yeekee-rounds');

        $this->assertSame('voided', (string) DB::table('yeekee_rounds')->where('id', 1)->value('status'));
        $this->assertSame('resulted', (string) DB::table('lotto_draws')->where('id', 3001)->value('status'));
        $this->assertSame('NO_ACTIVITY', (string) DB::table('lotto_draws')->where('id', 3001)->value('result_fetch_status'));

        $this->assertSame('voided', (string) DB::table('yeekee_rounds')->where('id', 2)->value('status'));
        $this->assertSame('resulted', (string) DB::table('lotto_draws')->where('id', 3002)->value('status'));
        $this->assertSame('VOID_REFUND', (string) DB::table('lotto_draws')->where('id', 3002)->value('result_fetch_status'));

        $this->assertSame('resulted', (string) DB::table('yeekee_rounds')->where('id', 3)->value('status'));
    }

    private function seedData(): void
    {
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
        ]);

        DB::table('yeekee_shoots')->insert([
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
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date');
            $table->dateTime('open_at');
            $table->dateTime('close_at');
            $table->dateTime('result_at')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'resulted'])->default('draft');
            $table->json('result_number')->nullable();
            $table->string('result_fetch_status', 32)->nullable();
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
