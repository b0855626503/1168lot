<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\AutoResult\ResultApplier;
use Gametech\Lotto\Services\DrawCancelAllRefundService;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Support\ResultHash;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ResultApplierDuplicatePreviousDrawTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Queue::fake();

        Schema::create('logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('emp_code', 32)->nullable();
            $table->string('mode', 32)->nullable();
            $table->string('menu', 128)->nullable();
            $table->unsignedBigInteger('record')->nullable();
            $table->json('item_before')->nullable();
            $table->json('item')->nullable();
            $table->string('ip', 64)->nullable();
            $table->unsignedBigInteger('user_create')->nullable();
            $table->dateTime('date_create')->nullable();
            $table->dateTime('date_update')->nullable();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('result_mode', 32)->default('normal');
            $table->boolean('auto_settle_on_result')->default(true);
            $table->boolean('auto_refund_on_no_result')->default(false);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->dateTime('result_fetched_at')->nullable();
            $table->dateTime('result_applied_at')->nullable();
            $table->dateTime('result_conflicted_at')->nullable();
            $table->string('status', 32);
            $table->string('result_fetch_status', 32)->nullable();
            $table->unsignedInteger('result_fetch_attempts')->default(0);
            $table->text('result_fetch_error')->nullable();
            $table->json('result_number')->nullable();
            $table->string('result_hash', 128)->nullable();
            $table->json('result_raw_payload_json')->nullable();
            $table->json('result_normalized_payload_json')->nullable();
            $table->json('result_conflict_payload_json')->nullable();
            $table->json('result_source_snapshot_json')->nullable();
            $table->unsignedBigInteger('result_source_id')->nullable();
            $table->string('result_source_version', 64)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('draw_id');
            $table->string('status', 32);
            $table->timestamps();
        });

        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->unsignedInteger('round_no');
        });
    }

    private function insertDraw(array $data): void
    {
        DB::table('lotto_draws')->insert(array_merge([
            'result_hash' => null,
            'result_number' => null,
            'result_at' => null,
            'result_fetched_at' => null,
            'result_applied_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $data));
    }

    public function test_duplicate_previous_draw_result_is_skipped_for_normal_lotto(): void
    {
        $resultPayload = ['first_prize' => '123456', 'last_2_digits' => '89'];
        $resultHash = ResultHash::fromPayload($resultPayload);

        DB::table('lotto_markets')->insert([
            'id' => 1,
            'name' => 'หวยปกติ',
            'result_mode' => LotteryMarket::RESULT_MODE_NORMAL,
            'auto_settle_on_result' => false,
        ]);

        $this->insertDraw([
            'id' => 1,
            'market_id' => 1,
            'draw_date' => '2026-05-01',
            'status' => 'resulted',
            'result_hash' => $resultHash,
            'result_number' => json_encode($resultPayload),
        ]);

        $this->insertDraw([
            'id' => 2,
            'market_id' => 1,
            'draw_date' => '2026-05-16',
            'status' => 'closed',
            'result_at' => '2026-05-16 16:00:00',
        ]);

        $applier = new ResultApplier(
            Mockery::mock(SettlementService::class),
            Mockery::mock(DrawCancelAllRefundService::class)
        );

        $draw = LottoDraw::query()->findOrFail(2);

        $result = $applier->apply($draw, $resultPayload, []);

        $this->assertSame('SKIPPED_DUPLICATE_PREVIOUS', $result['status']);

        $draw->refresh();
        $this->assertSame('closed', $draw->status);
        $this->assertSame('SKIPPED_DUPLICATE_PREVIOUS', $draw->result_fetch_status);
        $this->assertSame('ผลรางวัลตรงกับงวดก่อนหน้า ข้ามการออกผล', $draw->result_fetch_error);
    }

    public function test_different_result_proceeds_normally_for_normal_lotto(): void
    {
        $previousResult = ['first_prize' => '111111', 'last_2_digits' => '00'];
        $newResult = ['first_prize' => '123456', 'last_2_digits' => '89'];

        DB::table('lotto_markets')->insert([
            'id' => 2,
            'name' => 'หวยปกติ2',
            'result_mode' => LotteryMarket::RESULT_MODE_NORMAL,
            'auto_settle_on_result' => false,
        ]);

        $this->insertDraw([
            'id' => 3,
            'market_id' => 2,
            'draw_date' => '2026-05-01',
            'status' => 'resulted',
            'result_hash' => ResultHash::fromPayload($previousResult),
            'result_number' => json_encode($previousResult),
        ]);

        $this->insertDraw([
            'id' => 4,
            'market_id' => 2,
            'draw_date' => '2026-05-16',
            'status' => 'closed',
            'result_at' => '2026-05-16 16:00:00',
        ]);

        $applier = new ResultApplier(
            Mockery::mock(SettlementService::class),
            Mockery::mock(DrawCancelAllRefundService::class)
        );

        $draw = LottoDraw::query()->findOrFail(4);

        $result = $applier->apply($draw, $newResult, []);

        $this->assertSame('APPLIED', $result['status']);
        $this->assertTrue($result['deferred_settlement']);

        $draw->refresh();
        $this->assertSame('closed', $draw->status);
        $this->assertSame(ResultHash::fromPayload($newResult), $draw->result_hash);
    }

    public function test_first_draw_for_market_proceeds_normally(): void
    {
        $resultPayload = ['first_prize' => '123456', 'last_2_digits' => '89'];

        DB::table('lotto_markets')->insert([
            'id' => 3,
            'name' => 'หวยปกติ3',
            'result_mode' => LotteryMarket::RESULT_MODE_NORMAL,
            'auto_settle_on_result' => false,
        ]);

        $this->insertDraw([
            'id' => 5,
            'market_id' => 3,
            'draw_date' => '2026-05-16',
            'status' => 'closed',
            'result_at' => '2026-05-16 16:00:00',
        ]);

        $applier = new ResultApplier(
            Mockery::mock(SettlementService::class),
            Mockery::mock(DrawCancelAllRefundService::class)
        );

        $draw = LottoDraw::query()->findOrFail(5);

        $result = $applier->apply($draw, $resultPayload, []);

        $this->assertSame('APPLIED', $result['status']);
    }

    public function test_yeekee_market_skips_duplicate_check(): void
    {
        $resultPayload = ['first_prize' => '123456', 'last_2_digits' => '89'];

        DB::table('lotto_markets')->insert([
            'id' => 4,
            'name' => 'หวยยี่กี',
            'result_mode' => LotteryMarket::RESULT_MODE_YEEKEE,
            'auto_settle_on_result' => false,
        ]);

        // Previous draw with same result
        $this->insertDraw([
            'id' => 6,
            'market_id' => 4,
            'draw_date' => '2026-05-01',
            'status' => 'resulted',
            'result_hash' => ResultHash::fromPayload($resultPayload),
            'result_number' => json_encode($resultPayload),
        ]);

        // Current draw with same result
        $this->insertDraw([
            'id' => 7,
            'market_id' => 4,
            'draw_date' => '2026-05-16',
            'status' => 'closed',
            'result_at' => '2026-05-16 16:00:00',
        ]);

        $applier = new ResultApplier(
            Mockery::mock(SettlementService::class),
            Mockery::mock(DrawCancelAllRefundService::class)
        );

        $draw = LottoDraw::query()->findOrFail(7);

        $result = $applier->apply($draw, $resultPayload, []);

        // Yeekee should NOT skip — duplicate check only for normal lottery
        $this->assertSame('APPLIED', $result['status']);
    }

    public function test_no_result_payload_skips_duplicate_check(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 5,
            'name' => 'หวยปกติ5',
            'result_mode' => LotteryMarket::RESULT_MODE_NORMAL,
            'auto_settle_on_result' => true,
        ]);

        $this->insertDraw([
            'id' => 8,
            'market_id' => 5,
            'draw_date' => '2026-05-16',
            'status' => 'closed',
            'result_at' => '2026-05-16 16:00:00',
        ]);

        $noResultPayload = [
            'no_result' => true,
            'no_result_reason' => 'งดออกผล',
        ];

        $applier = new ResultApplier(
            Mockery::mock(SettlementService::class),
            Mockery::mock(DrawCancelAllRefundService::class)
        );

        $draw = LottoDraw::query()->findOrFail(8);

        $result = $applier->apply($draw, $noResultPayload, []);

        $this->assertSame('APPLIED', $result['status']);
        $this->assertTrue($result['no_result']);
    }

    public function test_duplicate_check_only_compares_with_earlier_draw_date(): void
    {
        $resultPayload = ['first_prize' => '123456', 'last_2_digits' => '89'];
        $resultHash = ResultHash::fromPayload($resultPayload);

        DB::table('lotto_markets')->insert([
            'id' => 6,
            'name' => 'หวยปกติ6',
            'result_mode' => LotteryMarket::RESULT_MODE_NORMAL,
            'auto_settle_on_result' => false,
        ]);

        // Previous draw (earlier date) with the same result
        $this->insertDraw([
            'id' => 9,
            'market_id' => 6,
            'draw_date' => '2026-04-16',
            'status' => 'resulted',
            'result_hash' => $resultHash,
            'result_number' => json_encode($resultPayload),
        ]);

        // Current draw (later date) with the same result
        $this->insertDraw([
            'id' => 10,
            'market_id' => 6,
            'draw_date' => '2026-05-01',
            'status' => 'closed',
            'result_at' => '2026-05-01 16:00:00',
        ]);

        $applier = new ResultApplier(
            Mockery::mock(SettlementService::class),
            Mockery::mock(DrawCancelAllRefundService::class)
        );

        $draw = LottoDraw::query()->findOrFail(10);

        $result = $applier->apply($draw, $resultPayload, []);

        // draw 9 (2026-04-16) is earlier than draw 10 (2026-05-01), and has same result_hash → skipped
        $this->assertSame('SKIPPED_DUPLICATE_PREVIOUS', $result['status']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
