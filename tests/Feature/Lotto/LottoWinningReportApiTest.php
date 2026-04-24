<?php

namespace Tests\Feature\Lotto;

use Gametech\Admin\Bouncer as AdminBouncer;
use Gametech\Lotto\Http\Controllers\Admin\LottoWinningReportController;
use Gametech\Lotto\Http\Requests\Admin\WinningReportBetsRequest;
use Gametech\Lotto\Http\Requests\Admin\WinningReportSummaryRequest;
use Gametech\Lotto\Http\Requests\Admin\WinningReportUsersRequest;
use Gametech\Lotto\Services\WinningReport\WinningReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LottoWinningReportApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
        $this->seedData();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_winnings');
        Schema::dropIfExists('settlement_batches');
        Schema::dropIfExists('lotto_draws');
        parent::tearDown();
    }

    public function test_summary_users_bets_consistency_and_pending_null_semantics(): void
    {
        app()->instance(AdminBouncer::class, new class
        {
            public function hasPermission(string $key): bool
            {
                return true;
            }
        });

        $service = app(WinningReportService::class);
        $summary = $service->summary(['draw_id' => 103])['summary'];

        $users = $service->users(103, [], 100)->items();
        $userTotalPayout = collect($users)->sum(static fn ($row) => (float) ($row->total_payout ?? 0));

        $bets = $service->bets(103, [], 100)->items();
        $betTotalPayout = collect($bets)->sum(static fn ($row) => (float) ($row->payout ?? 0));

        $this->assertEquals($summary['total_payout'], round($userTotalPayout, 2));
        $this->assertEquals(round($userTotalPayout, 2), round($betTotalPayout, 2));

        $controller = new LottoWinningReportController;
        $betsRequest = WinningReportBetsRequest::create('/', 'GET', ['round_id' => 101, 'per_page' => 100]);
        $betsRequest->setContainer(app());
        $betsRequest->setRedirector(app('redirect'));
        $betsRequest->validateResolved();

        $betsResponse = $controller->bets($betsRequest, $service);
        $betsPayload = $betsResponse->getData(true);

        $pendingRow = collect($betsPayload['data'])->first(static fn (array $row): bool => ($row['status'] ?? '') === 'pending');
        $this->assertNotNull($pendingRow);
        $this->assertNull($pendingRow['payout']);
        $this->assertNull($pendingRow['net_profit']);
    }

    public function test_permission_403_when_user_has_no_view_permission(): void
    {
        app()->instance(AdminBouncer::class, new class
        {
            public function hasPermission(string $key): bool
            {
                return false;
            }
        });

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        $controller = new LottoWinningReportController;
        $request = WinningReportSummaryRequest::create('/', 'GET', ['round_id' => 101]);
        $controller->summary($request, app(WinningReportService::class));
    }

    public function test_returns_409_when_settlement_batch_is_pending(): void
    {
        app()->instance(AdminBouncer::class, new class
        {
            public function hasPermission(string $key): bool
            {
                return true;
            }
        });

        $controller = new LottoWinningReportController;
        $request = WinningReportUsersRequest::create('/', 'GET', ['round_id' => 102]);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->validateResolved();

        $response = $controller->users($request, app(WinningReportService::class));

        $this->assertSame(Response::HTTP_CONFLICT, $response->status());
        $this->assertSame('SETTLEMENT_PENDING', $response->getData(true)['message']);
    }

    public function test_bets_status_filter_returns_only_requested_status(): void
    {
        app()->instance(AdminBouncer::class, new class
        {
            public function hasPermission(string $key): bool
            {
                return true;
            }
        });

        $controller = new LottoWinningReportController;
        $request = WinningReportBetsRequest::create('/', 'GET', [
            'round_id' => 101,
            'status' => 'pending',
            'per_page' => 100,
        ]);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->validateResolved();

        $response = $controller->bets($request, app(WinningReportService::class));
        $payload = $response->getData(true);

        $this->assertCount(1, $payload['data']);
        $this->assertSame('pending', $payload['data'][0]['status']);
        $this->assertNull($payload['data'][0]['payout']);
    }

    public function test_summary_without_date_prefers_latest_round_that_has_winning_records_for_detail_tables(): void
    {
        $service = app(WinningReportService::class);

        $payload = $service->summary([]);

        $this->assertSame(103, $payload['latest_round_id']);
        $this->assertSame(4, $payload['summary']['winner_count']);
        $this->assertSame(5, $payload['summary']['winning_ticket_count']);
        $this->assertNull($payload['summary']['total_payout']);
    }

    private function prepareSchema(): void
    {
        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('settlement_batches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->date('draw_date')->nullable();
            $table->string('lottery_type');
            $table->string('market')->nullable();
            $table->string('mode');
            $table->string('status');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('total_bets_processed')->default(0);
            $table->unsignedInteger('total_winning_records')->default(0);
            $table->decimal('total_stake', 14, 2)->default(0);
            $table->decimal('total_payout', 14, 2)->default(0);
            $table->string('idempotency_key')->unique();
            $table->timestamps();
        });

        Schema::create('lotto_winnings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->unsignedBigInteger('bet_id');
            $table->unsignedBigInteger('bet_item_id');
            $table->string('ticket_no')->nullable();
            $table->unsignedInteger('user_id');
            $table->string('username')->nullable();
            $table->string('lottery_type');
            $table->string('market')->nullable();
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('stake', 14, 2);
            $table->decimal('odds', 10, 4);
            $table->decimal('payout', 14, 2)->nullable();
            $table->decimal('net_profit', 14, 2)->nullable();
            $table->string('result_number')->nullable();
            $table->string('matched_rule')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('settlement_batch_id');
            $table->dateTime('settled_at')->nullable();
            $table->dateTime('credited_at')->nullable();
            $table->timestamps();
            $table->unique(['draw_id', 'bet_item_id']);
        });
    }

    private function seedData(): void
    {
        \DB::table('lotto_draws')->insert([
            ['id' => 101, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 103, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 104, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('settlement_batches')->insert([
            [
                'id' => 1,
                'draw_id' => 101,
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'mode' => 'settlement',
                'status' => 'settled',
                'started_at' => now(),
                'finished_at' => now(),
                'total_bets_processed' => 2,
                'total_winning_records' => 3,
                'total_stake' => 350,
                'total_payout' => 350,
                'idempotency_key' => 'settlement:101:abc',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'draw_id' => 102,
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'mode' => 'settlement',
                'status' => 'pending',
                'started_at' => now(),
                'finished_at' => null,
                'total_bets_processed' => 0,
                'total_winning_records' => 0,
                'total_stake' => 0,
                'total_payout' => 0,
                'idempotency_key' => 'settlement:102:def',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'draw_id' => 103,
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'mode' => 'settlement',
                'status' => 'settled',
                'started_at' => now(),
                'finished_at' => now(),
                'total_bets_processed' => 1,
                'total_winning_records' => 2,
                'total_stake' => 200,
                'total_payout' => 220,
                'idempotency_key' => 'settlement:103:ghi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'draw_id' => 104,
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'mode' => 'settlement',
                'status' => 'settled',
                'started_at' => now(),
                'finished_at' => now(),
                'total_bets_processed' => 1,
                'total_winning_records' => 0,
                'total_stake' => 0,
                'total_payout' => 0,
                'idempotency_key' => 'settlement:104:jkl',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        \DB::table('lotto_winnings')->insert([
            [
                'draw_id' => 101,
                'bet_id' => 1001,
                'bet_item_id' => 5001,
                'ticket_no' => '1001',
                'user_id' => 1,
                'username' => 'alpha',
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'bet_type' => 'top_3',
                'number' => '123',
                'stake' => 100,
                'odds' => 2,
                'payout' => 200,
                'net_profit' => -100,
                'result_number' => '123',
                'matched_rule' => 'top_3',
                'status' => 'credited',
                'settlement_batch_id' => 1,
                'settled_at' => now(),
                'credited_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'draw_id' => 101,
                'bet_id' => 1002,
                'bet_item_id' => 5002,
                'ticket_no' => '1002',
                'user_id' => 2,
                'username' => 'beta',
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'bet_type' => 'top_2',
                'number' => '45',
                'stake' => 150,
                'odds' => 1,
                'payout' => 150,
                'net_profit' => 0,
                'result_number' => '45',
                'matched_rule' => 'top_2',
                'status' => 'settled',
                'settlement_batch_id' => 1,
                'settled_at' => now(),
                'credited_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'draw_id' => 101,
                'bet_id' => 1003,
                'bet_item_id' => 5003,
                'ticket_no' => '1003',
                'user_id' => 2,
                'username' => 'beta',
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'bet_type' => 'run_top',
                'number' => '1',
                'stake' => 100,
                'odds' => 0.5,
                'payout' => null,
                'net_profit' => null,
                'result_number' => '123',
                'matched_rule' => 'run_top',
                'status' => 'pending',
                'settlement_batch_id' => 1,
                'settled_at' => null,
                'credited_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'draw_id' => 103,
                'bet_id' => 1004,
                'bet_item_id' => 5004,
                'ticket_no' => '1004',
                'user_id' => 4,
                'username' => 'delta',
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'bet_type' => 'top_3',
                'number' => '999',
                'stake' => 100,
                'odds' => 2,
                'payout' => 200,
                'net_profit' => -100,
                'result_number' => '999',
                'matched_rule' => 'top_3',
                'status' => 'credited',
                'settlement_batch_id' => 3,
                'settled_at' => now(),
                'credited_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'draw_id' => 103,
                'bet_id' => 1005,
                'bet_item_id' => 5005,
                'ticket_no' => '1005',
                'user_id' => 5,
                'username' => 'echo',
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'bet_type' => 'top_2',
                'number' => '11',
                'stake' => 100,
                'odds' => 0.2,
                'payout' => 20,
                'net_profit' => 80,
                'result_number' => '11',
                'matched_rule' => 'top_2',
                'status' => 'settled',
                'settlement_batch_id' => 3,
                'settled_at' => now(),
                'credited_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
