<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\DataTables\LottoDrawDataTable;
use Gametech\Lotto\Models\LottoDraw;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoDrawDataTableYeekeeFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('result_mode', 32)->default('normal');
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('lotto_number_blocks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_number_blocks');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        parent::tearDown();
    }

    public function test_query_excludes_yeekee_by_default_and_includes_when_market_selected(): void
    {
        \DB::table('lotto_markets')->insert([
            ['id' => 1, 'name' => 'Normal', 'result_mode' => 'normal'],
            ['id' => 2, 'name' => 'Yeekee', 'result_mode' => 'yeekee'],
        ]);

        \DB::table('lotto_draws')->insert([
            ['id' => 11, 'market_id' => 1, 'draw_date' => '2026-04-30', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 22, 'market_id' => 2, 'draw_date' => '2026-04-30', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->app->instance('request', Request::create('/admin/lotto/draws', 'GET'));
        $rowsDefault = (new LottoDrawDataTable)->query(new LottoDraw)->pluck('id')->all();
        $this->assertSame([11], array_values($rowsDefault));

        $this->app->instance('request', Request::create('/admin/lotto/draws', 'GET', ['market_id' => 2]));
        $rowsYeekee = (new LottoDrawDataTable)->query(new LottoDraw)->pluck('id')->all();
        $this->assertSame([22], array_values($rowsYeekee));
    }
}
