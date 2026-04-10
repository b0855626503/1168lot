<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoController;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoDrawsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('name_kh')->nullable();
            $table->string('name_laos')->nullable();
            $table->string('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort')->default(0);
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('name_kh')->nullable();
            $table->string('name_laos')->nullable();
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('result_number')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');

        parent::tearDown();
    }

    public function test_draws_returns_latest_non_draft_per_market(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'หวยไทย',
            'code' => 'lotto-thai',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            [
                'id' => 1,
                'group_id' => 1,
                'name' => 'หวยออมสิน',
                'code' => 'gsb',
                'is_enabled' => 1,
            ],
            [
                'id' => 2,
                'group_id' => 1,
                'name' => 'หวยรัฐบาล',
                'code' => 'government',
                'is_enabled' => 1,
            ],
            [
                'id' => 3,
                'group_id' => 1,
                'name' => 'หวยคืนเงิน',
                'code' => 'refunded-market',
                'is_enabled' => 1,
            ],
            [
                'id' => 4,
                'group_id' => 1,
                'name' => 'หวยงดออกผล',
                'code' => 'no-result-market',
                'is_enabled' => 1,
            ],
            [
                'id' => 5,
                'group_id' => 1,
                'name' => 'หวยเปิดก่อนผล',
                'code' => 'prefer-open-market',
                'is_enabled' => 1,
            ],
        ]);

        DB::table('lotto_draws')->insert([
            [
                'id' => 10,
                'market_id' => 1,
                'draw_date' => '2026-04-04',
                'open_at' => '2026-04-04 09:00:00',
                'close_at' => '2026-04-04 15:00:00',
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'market_id' => 1,
                'draw_date' => '2026-04-05',
                'open_at' => '2026-04-05 09:00:00',
                'close_at' => '2026-04-05 15:00:00',
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 20,
                'market_id' => 2,
                'draw_date' => '2026-04-03',
                'open_at' => '2026-04-03 09:00:00',
                'close_at' => '2026-04-03 15:00:00',
                'status' => 'resulted',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 21,
                'market_id' => 2,
                'draw_date' => '2026-04-04',
                'open_at' => '2026-04-04 09:00:00',
                'close_at' => '2026-04-04 15:00:00',
                'status' => 'closed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/v1/lotto/draws', 'GET', ['limit' => 20]);
        $request->attributes->set('frontend_language', 'th');

        $response = TestResponse::fromBaseResponse(
            app(LottoController::class)->draws($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('language', 'th');
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => 10,
            'market_id' => 1,
            'status' => 'open',
        ]);
        $response->assertJsonFragment([
            'id' => 21,
            'market_id' => 2,
            'status' => 'closed',
        ]);

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([10, 21], collect($ids)->sort()->values()->all());
        $this->assertNotContains(11, $ids);
    }

    public function test_markets_latest_uses_latest_non_draft_draw_per_market(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'หวยไทย',
            'code' => 'lotto-thai',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            [
                'id' => 1,
                'group_id' => 1,
                'name' => 'หวยออมสิน',
                'code' => 'gsb',
                'is_enabled' => 1,
            ],
            [
                'id' => 2,
                'group_id' => 1,
                'name' => 'หวยรัฐบาล',
                'code' => 'government',
                'is_enabled' => 1,
            ],
            [
                'id' => 3,
                'group_id' => 1,
                'name' => 'หวยคืนเงิน',
                'code' => 'refunded-market',
                'is_enabled' => 1,
            ],
            [
                'id' => 4,
                'group_id' => 1,
                'name' => 'หวยงดออกผล',
                'code' => 'no-result-market',
                'is_enabled' => 1,
            ],
            [
                'id' => 5,
                'group_id' => 1,
                'name' => 'หวยเปิดก่อนผล',
                'code' => 'prefer-open-market',
                'is_enabled' => 1,
            ],
        ]);

        DB::table('lotto_draws')->insert([
            [
                'id' => 30,
                'market_id' => 1,
                'draw_date' => '2026-04-04',
                'open_at' => '2026-04-04 09:00:00',
                'close_at' => '2026-04-04 15:00:00',
                'status' => 'open',
                'result_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 31,
                'market_id' => 1,
                'draw_date' => '2026-04-05',
                'open_at' => '2026-04-05 09:00:00',
                'close_at' => '2026-04-05 15:00:00',
                'status' => 'draft',
                'result_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 40,
                'market_id' => 2,
                'draw_date' => '2026-04-04',
                'open_at' => '2026-04-04 09:00:00',
                'close_at' => '2026-04-04 12:00:00',
                'status' => 'resulted',
                'result_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 41,
                'market_id' => 2,
                'draw_date' => '2026-04-04',
                'open_at' => '2026-04-04 09:00:00',
                'close_at' => '2026-04-04 15:00:00',
                'status' => 'draft',
                'result_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 50,
                'market_id' => 3,
                'draw_date' => '2026-04-04',
                'open_at' => '2026-04-04 09:00:00',
                'close_at' => '2026-04-04 18:00:00',
                'status' => 'resulted',
                'result_number' => json_encode([
                    'no_result' => true,
                    'status' => 'no_result',
                    'manual_cancelled_all_tickets' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 60,
                'market_id' => 4,
                'draw_date' => '2026-04-04',
                'open_at' => '2026-04-04 09:00:00',
                'close_at' => '2026-04-04 17:00:00',
                'status' => 'resulted',
                'result_number' => json_encode([
                    'no_result' => true,
                    'status' => 'no_result',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 69,
                'market_id' => 5,
                'draw_date' => '2026-04-04',
                'open_at' => '2026-04-04 09:00:00',
                'close_at' => '2026-04-04 11:00:00',
                'status' => 'open',
                'result_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 70,
                'market_id' => 5,
                'draw_date' => '2026-04-05',
                'open_at' => '2026-04-05 09:00:00',
                'close_at' => '2026-04-05 15:00:00',
                'status' => 'resulted',
                'result_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/v1/lotto/markets/latest', 'GET');
        $request->attributes->set('frontend_language', 'th');

        $response = TestResponse::fromBaseResponse(
            app(LottoController::class)->marketsLatestByGroup($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.groups.0.group_id', 1);
        $response->assertJsonCount(5, 'data.groups.0.markets');
        $response->assertJsonFragment(['market_id' => 1]);
        $response->assertJsonFragment(['draw_id' => 30, 'status' => 'open', 'status_label' => 'แทงหวย']);
        $response->assertJsonFragment(['market_id' => 2]);
        $response->assertJsonFragment(['draw_id' => 40, 'status' => 'resulted', 'status_label' => 'ออกผล']);
        $response->assertJsonFragment(['market_id' => 3]);
        $response->assertJsonFragment(['draw_id' => 50, 'status' => 'refunded', 'status_label' => 'ยกเลิก']);
        $response->assertJsonFragment(['market_id' => 4]);
        $response->assertJsonFragment(['draw_id' => 60, 'status' => 'no_result', 'status_label' => 'ยกเลิก']);
        $response->assertJsonFragment(['market_id' => 5]);
        $response->assertJsonFragment(['draw_id' => 69, 'status' => 'open', 'status_label' => 'แทงหวย']);
        $this->assertSame(
            [5, 2, 1, 4, 3],
            collect($response->json('data.groups.0.markets'))
                ->pluck('market_id')
                ->all()
        );
    }
}
