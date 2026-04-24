<?php

namespace Tests\Feature\Lotto;

use Gametech\Admin\Bouncer as AdminBouncer;
use Gametech\Lotto\Http\Controllers\Admin\LottoWinningReportController;
use Gametech\Lotto\Http\Requests\Admin\WinningReportExportRequest;
use Gametech\Lotto\Services\WinningReport\WinningReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class LottoWinningReportExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
        $this->seedData();

        app()->instance(AdminBouncer::class, new class
        {
            public function hasPermission(string $key): bool
            {
                return true;
            }
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logs');
        Schema::dropIfExists('lotto_winnings');
        Schema::dropIfExists('settlement_batches');
        Schema::dropIfExists('lotto_draws');
        parent::tearDown();
    }

    public function test_export_totals_match_summary_and_returns_download_response(): void
    {
        $service = app(WinningReportService::class);
        $summary = $service->summary(['draw_id' => 201])['summary'];
        $exportRows = $service->exportRows(201, []);

        $this->assertEquals((float) $summary['total_payout'], (float) $exportRows['total_payout']);

        $controller = new LottoWinningReportController;
        $request = WinningReportExportRequest::create('/', 'GET', [
            'round_id' => 201,
            'level' => 'bets',
            'format' => 'csv',
        ]);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->validateResolved();

        $response = $controller->export($request, $service);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseCount('logs', 1);
    }

    private function prepareSchema(): void
    {
        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('emp_code')->nullable();
            $table->string('mode')->nullable();
            $table->string('menu')->nullable();
            $table->string('record')->nullable();
            $table->longText('item_before')->nullable();
            $table->longText('item')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_create')->nullable();
            $table->dateTime('date_create')->nullable();
            $table->dateTime('date_update')->nullable();
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
        });
    }

    private function seedData(): void
    {
        \DB::table('lotto_draws')->insert([
            'id' => 201,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('settlement_batches')->insert([
            'id' => 21,
            'draw_id' => 201,
            'lottery_type' => 'thai',
            'market' => 'gsb',
            'mode' => 'settlement',
            'status' => 'settled',
            'started_at' => now(),
            'finished_at' => now(),
            'total_bets_processed' => 2,
            'total_winning_records' => 2,
            'total_stake' => 100,
            'total_payout' => 120,
            'idempotency_key' => 'settlement:201:aaa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('lotto_winnings')->insert([
            [
                'draw_id' => 201,
                'bet_id' => 1,
                'bet_item_id' => 1,
                'ticket_no' => '1',
                'user_id' => 7,
                'username' => 'alpha',
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'bet_type' => 'top_3',
                'number' => '123',
                'stake' => 50,
                'odds' => 2,
                'payout' => 100,
                'net_profit' => -50,
                'result_number' => '123',
                'matched_rule' => 'top_3',
                'status' => 'credited',
                'settlement_batch_id' => 21,
                'settled_at' => now(),
                'credited_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'draw_id' => 201,
                'bet_id' => 2,
                'bet_item_id' => 2,
                'ticket_no' => '2',
                'user_id' => 7,
                'username' => 'alpha',
                'lottery_type' => 'thai',
                'market' => 'gsb',
                'bet_type' => 'top_2',
                'number' => '45',
                'stake' => 50,
                'odds' => 0.4,
                'payout' => 20,
                'net_profit' => 30,
                'result_number' => '45',
                'matched_rule' => 'top_2',
                'status' => 'settled',
                'settlement_batch_id' => 21,
                'settled_at' => now(),
                'credited_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
