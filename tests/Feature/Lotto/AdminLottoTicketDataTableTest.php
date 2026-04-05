<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\DataTables\LottoTicketDataTable;
use Gametech\Lotto\Models\LottoTicket;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLottoTicketDataTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');

        parent::tearDown();
    }

    public function test_query_returns_only_active_tickets_for_admin_menu(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 1,
            'name' => 'หวยออมสิน',
        ]);

        DB::table('lotto_draws')->insert([
            [
                'id' => 101,
                'market_id' => 1,
                'draw_date' => '2026-04-05',
                'status' => 'open',
            ],
            [
                'id' => 102,
                'market_id' => 1,
                'draw_date' => '2026-04-06',
                'status' => 'closed',
            ],
        ]);

        DB::table('lotto_tickets')->insert([
            [
                'id' => 1001,
                'member_id' => 1,
                'draw_id' => 101,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1002,
                'member_id' => 1,
                'draw_id' => 101,
                'status' => 'cancelled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1003,
                'member_id' => 1,
                'draw_id' => 102,
                'status' => 'resulted',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $query = app(LottoTicketDataTable::class)->query(new LottoTicket());
        $ids = $query->pluck('lotto_tickets.id')->all();

        $this->assertSame([1001], $ids);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->string('status')->default('draft');
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->decimal('total_bet_amount', 12, 2)->default(0);
            $table->decimal('total_discount_amount', 12, 2)->default(0);
            $table->decimal('total_net_amount', 12, 2)->default(0);
            $table->decimal('total_win_amount', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('lotto_ticket_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('result_status')->nullable();
        });
    }
}
