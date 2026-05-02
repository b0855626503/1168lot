<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\DataTables\LottoDrawDataTable;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoDrawDataTableFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
        $this->seedDraws();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_number_blocks');
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');

        parent::tearDown();
    }

    public function test_default_query_excludes_yeekee_market_rows(): void
    {
        request()->replace([]);

        $query = (new LottoDrawDataTable)->query(new LottoDraw);

        $this->assertSame(1, $query->count());
    }

    public function test_group_filter_keeps_yeekee_rows_when_market_is_all(): void
    {
        request()->replace([
            'group_id' => 2,
            'market_id' => '',
            'include_yeekee' => 0,
        ]);

        $query = (new LottoDrawDataTable)->query(new LottoDraw);
        $rows = $query->get();

        $this->assertCount(1, $rows);
        $this->assertSame(LotteryMarket::RESULT_MODE_YEEKEE, (string) optional($rows->first()->market)->result_mode);
    }

    private function seedDraws(): void
    {
        DB::table('lotto_groups')->insert([
            ['id' => 1, 'name' => 'Normal Group', 'code' => 'normal_group', 'is_enabled' => 1, 'sort' => 1],
            ['id' => 2, 'name' => 'Yeekee Group', 'code' => 'yeekee_group', 'is_enabled' => 1, 'sort' => 2],
        ]);

        DB::table('lotto_markets')->insert([
            ['id' => 11, 'group_id' => 1, 'name' => 'Normal Market', 'code' => 'normal_market', 'result_mode' => LotteryMarket::RESULT_MODE_NORMAL, 'is_enabled' => 1],
            ['id' => 12, 'group_id' => 2, 'name' => 'Yeekee Market', 'code' => 'yeekee_market', 'result_mode' => LotteryMarket::RESULT_MODE_YEEKEE, 'is_enabled' => 1],
        ]);

        DB::table('lotto_draws')->insert([
            [
                'id' => 101,
                'market_id' => 11,
                'draw_date' => '2026-05-01',
                'open_at' => '2026-05-01 10:00:00',
                'close_at' => '2026-05-01 11:00:00',
                'result_at' => '2026-05-01 11:02:00',
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 102,
                'market_id' => 12,
                'draw_date' => '2026-05-01',
                'open_at' => '2026-05-01 10:00:00',
                'close_at' => '2026-05-01 10:15:00',
                'result_at' => '2026-05-01 10:17:00',
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
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
            $table->string('result_mode', 32)->default(LotteryMarket::RESULT_MODE_NORMAL);
            $table->boolean('is_enabled')->default(true);
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
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_number_blocks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->string('bet_type', 32)->nullable();
            $table->string('number', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lotto_draw_id');
            $table->unsignedInteger('round_no')->default(1);
            $table->timestamps();
        });
    }
}
