<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoController;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Services\Yeekee\Exceptions\YeekeeShootCooldownException;
use Gametech\Lotto\Services\YeekeeShootService;
use Gametech\Member\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LottoDrawsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('yeekee_shoot_reward_logs');
        Schema::dropIfExists('yeekee_shoots');
        Schema::dropIfExists('yeekee_rounds');
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
            $table->string('result_mode')->default('normal');
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('result_number')->nullable();
            $table->timestamps();
        });

        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->date('round_date');
            $table->unsignedInteger('round_no');
            $table->dateTime('bet_open_at');
            $table->dateTime('bet_close_at');
            $table->dateTime('shoot_open_at');
            $table->dateTime('shoot_close_at');
            $table->dateTime('result_compute_at');
            $table->dateTime('expected_settlement_deadline_at');
            $table->string('status')->default('open_bet');
            $table->json('config_snapshot_json')->nullable();
            $table->unsignedInteger('shoot_count')->default(0);
            $table->unsignedInteger('last_shoot_position')->default(0);
            $table->json('shoot_snapshot_json')->nullable();
            $table->string('shoot_snapshot_hash', 128)->nullable();
            $table->dateTime('shoot_closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('yeekee_shoots', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->string('number_text', 5);
            $table->unsignedInteger('number_value');
            $table->dateTime('submitted_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('yeekee_shoot_reward_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->decimal('credit_amount', 16, 2);
            $table->string('reward_ref_type', 64);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('yeekee_shoot_reward_logs');
        Schema::dropIfExists('yeekee_shoots');
        Schema::dropIfExists('yeekee_rounds');
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
                'result_mode' => 'normal',
            ],
            [
                'id' => 2,
                'group_id' => 1,
                'name' => 'หวยรัฐบาล',
                'code' => 'government',
                'is_enabled' => 1,
                'result_mode' => 'normal',
            ],
            [
                'id' => 3,
                'group_id' => 1,
                'name' => 'หวยคืนเงิน',
                'code' => 'refunded-market',
                'is_enabled' => 1,
                'result_mode' => 'normal',
            ],
            [
                'id' => 4,
                'group_id' => 1,
                'name' => 'หวยงดออกผล',
                'code' => 'no-result-market',
                'is_enabled' => 1,
                'result_mode' => 'normal',
            ],
            [
                'id' => 5,
                'group_id' => 1,
                'name' => 'หวยเปิดก่อนผล',
                'code' => 'prefer-open-market',
                'is_enabled' => 1,
                'result_mode' => 'normal',
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
            'result_mode' => 'normal',
            'is_yeekee' => false,
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
        Carbon::setTestNow('2026-04-04 11:30:00');

        try {
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
                    'result_mode' => 'normal',
                ],
                [
                    'id' => 2,
                    'group_id' => 1,
                    'name' => 'หวยรัฐบาล',
                    'code' => 'government',
                    'is_enabled' => 1,
                    'result_mode' => 'normal',
                ],
                [
                    'id' => 3,
                    'group_id' => 1,
                    'name' => 'หวยคืนเงิน',
                    'code' => 'refunded-market',
                    'is_enabled' => 1,
                    'result_mode' => 'normal',
                ],
                [
                    'id' => 4,
                    'group_id' => 1,
                    'name' => 'หวยงดออกผล',
                    'code' => 'no-result-market',
                    'is_enabled' => 1,
                    'result_mode' => 'normal',
                ],
                [
                    'id' => 5,
                    'group_id' => 1,
                    'name' => 'หวยเปิดก่อนผล',
                    'code' => 'prefer-open-market',
                    'is_enabled' => 1,
                    'result_mode' => 'normal',
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
                [2, 1, 4, 3, 5],
                collect($response->json('data.groups.0.markets'))
                    ->pluck('market_id')
                    ->all()
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_markets_latest_includes_yeekee_additive_fields(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Yeekee Group',
            'code' => 'yeekee',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 9,
            'group_id' => 1,
            'name' => 'Yeekee Market',
            'code' => 'yeekee-market',
            'is_enabled' => 1,
            'result_mode' => 'yeekee',
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 99,
            'market_id' => 9,
            'draw_date' => '2026-04-29',
            'open_at' => '2026-04-29 23:30:00',
            'close_at' => '2026-04-29 23:45:00',
            'result_at' => null,
            'status' => 'closed',
            'result_number' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_rounds')->insert([
            'id' => 501,
            'market_id' => 9,
            'lotto_draw_id' => 99,
            'round_date' => '2026-04-29',
            'round_no' => 95,
            'bet_open_at' => '2026-04-29 23:30:00',
            'bet_close_at' => '2026-04-29 23:45:00',
            'shoot_open_at' => '2026-04-29 23:45:00',
            'shoot_close_at' => '2026-04-29 23:46:00',
            'result_compute_at' => '2026-04-29 23:47:00',
            'expected_settlement_deadline_at' => '2026-04-29 23:52:00',
            'status' => 'shoot_open',
            'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_MINUS_POSITION']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/lotto/markets/latest', 'GET');
        $request->attributes->set('frontend_language', 'th');

        $response = TestResponse::fromBaseResponse(app(LottoController::class)->marketsLatestByGroup($request));

        $response->assertOk();
        $response->assertJsonFragment([
            'market_id' => 9,
            'result_mode' => 'yeekee',
            'market_type' => 'yeekee',
            'is_yeekee' => true,
        ]);
        $response->assertJsonPath('data.groups.0.markets.0.latest_draw.round_status', 'shoot_open');
    }

    public function test_yeekee_current_round_and_proof_visibility_follow_reveal_timing(): void
    {
        $member = new Member;
        $member->code = 6001;
        $member->exists = true;

        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Yeekee Group',
            'code' => 'yeekee',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 9,
            'group_id' => 1,
            'name' => 'Yeekee Market',
            'code' => 'yeekee-market',
            'is_enabled' => 1,
            'result_mode' => 'yeekee',
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 100,
            'market_id' => 9,
            'draw_date' => '2026-04-29',
            'open_at' => '2026-04-29 23:30:00',
            'close_at' => '2026-04-29 23:45:00',
            'result_at' => null,
            'status' => 'closed',
            'result_number' => json_encode([
                'precommit_signature' => 'pre-signature',
                'proof_signature' => 'proof-signature',
                'external_seed_reference' => 'seed-ref',
                'raw_result' => '12345',
                'top_3' => '345',
                'bottom_2' => '45',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_rounds')->insert([
            'id' => 502,
            'market_id' => 9,
            'lotto_draw_id' => 100,
            'round_date' => '2026-04-29',
            'round_no' => 96,
            'bet_open_at' => '2026-04-29 23:30:00',
            'bet_close_at' => '2026-04-29 23:45:00',
            'shoot_open_at' => '2026-04-29 23:45:00',
            'shoot_close_at' => '2026-04-29 23:46:00',
            'result_compute_at' => '2026-04-29 23:47:00',
            'expected_settlement_deadline_at' => '2026-04-29 23:52:00',
            'status' => 'shoot_open',
            'config_snapshot_json' => json_encode([
                'formula_config' => ['preset' => 'SHOOTS_SUM_MINUS_POSITION'],
                'round_duration_minutes' => 15,
                'shoot_window_after_bet_close_seconds' => 60,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentRequest = Request::create('/api/v1/lotto/yeekee/markets/9/current-round', 'GET');
        $currentRequest->setUserResolver(static fn (?string $guard = null) => $guard === 'customer' ? $member : null);
        $currentResponse = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeCurrentRound($currentRequest, 9));
        $currentResponse->assertOk();
        $currentResponse->assertJsonPath('data.server_time', now()->format('Y-m-d H:i:s'));
        $currentResponse->assertJsonPath('data.result_mode', 'yeekee');

        $proofRequest = Request::create('/api/v1/lotto/yeekee/rounds/502/result-proof', 'GET');
        $proofResponse = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeResultProof($proofRequest, 502));
        $proofResponse->assertOk();
        $proofResponse->assertJsonPath('data.is_revealed', false);
        $proofResponse->assertJsonPath('data.proof.precommit_signature', 'pre-signature');
        $proofResponse->assertJsonPath('data.proof.proof_signature', '');
        $proofResponse->assertJsonPath('data.proof.result_payload', null);

        DB::table('yeekee_rounds')->where('id', 502)->update(['status' => 'resulted']);
        DB::table('lotto_draws')->where('id', 100)->update(['status' => 'resulted']);

        $revealedResponse = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeResultProof($proofRequest, 502));
        $revealedResponse->assertOk();
        $revealedResponse->assertJsonPath('data.is_revealed', true);
        $revealedResponse->assertJsonPath('data.proof.proof_signature', 'proof-signature');
        $revealedResponse->assertJsonPath('data.proof.result_payload.raw_result', '12345');
    }

    public function test_yeekee_market_rounds_returns_all_rounds_for_given_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 10:05:00'));

        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Yeekee Group',
            'code' => 'yeekee',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 9,
            'group_id' => 1,
            'name' => 'Yeekee Market',
            'code' => 'yeekee-market',
            'is_enabled' => 1,
            'result_mode' => 'yeekee',
        ]);

        DB::table('yeekee_rounds')->insert([
            [
                'id' => 601,
                'market_id' => 9,
                'lotto_draw_id' => 201,
                'round_date' => '2026-04-29',
                'round_no' => 1,
                'bet_open_at' => '2026-04-29 10:00:00',
                'bet_close_at' => '2026-04-29 10:15:00',
                'shoot_open_at' => '2026-04-29 10:15:00',
                'shoot_close_at' => '2026-04-29 10:16:00',
                'result_compute_at' => '2026-04-29 10:17:00',
                'expected_settlement_deadline_at' => '2026-04-29 10:22:00',
                'status' => 'open_bet',
                'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_ONLY']]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 602,
                'market_id' => 9,
                'lotto_draw_id' => 202,
                'round_date' => '2026-04-29',
                'round_no' => 2,
                'bet_open_at' => '2026-04-29 10:15:00',
                'bet_close_at' => '2026-04-29 10:30:00',
                'shoot_open_at' => '2026-04-29 10:30:00',
                'shoot_close_at' => '2026-04-29 10:31:00',
                'result_compute_at' => '2026-04-29 10:32:00',
                'expected_settlement_deadline_at' => '2026-04-29 10:37:00',
                'status' => 'open_bet',
                'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_ONLY']]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 603,
                'market_id' => 9,
                'lotto_draw_id' => 203,
                'round_date' => '2026-04-30',
                'round_no' => 1,
                'bet_open_at' => '2026-04-30 10:00:00',
                'bet_close_at' => '2026-04-30 10:15:00',
                'shoot_open_at' => '2026-04-30 10:15:00',
                'shoot_close_at' => '2026-04-30 10:16:00',
                'result_compute_at' => '2026-04-30 10:17:00',
                'expected_settlement_deadline_at' => '2026-04-30 10:22:00',
                'status' => 'open_bet',
                'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_ONLY']]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/v1/lotto/yeekee/markets/9/rounds', 'GET', [
            'draw_date' => '2026-04-29',
        ]);
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeMarketRounds($request, 9));

        $response->assertOk();
        $response->assertJsonPath('data.draw_date', '2026-04-29');
        $response->assertJsonPath('data.count', 2);
        $response->assertJsonPath('data.items.0.round_id', 601);
        $response->assertJsonPath('data.items.1.round_id', 602);
        $response->assertJsonPath('data.items.0.is_open_for_play', true);
        $response->assertJsonPath('data.items.1.is_open_for_play', false);

        Carbon::setTestNow();
    }

    public function test_yeekee_rounds_returns_all_markets_for_given_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 10:05:00'));

        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Yeekee Group',
            'code' => 'yeekee',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            ['id' => 9, 'group_id' => 1, 'name' => 'Yeekee 1', 'code' => 'yeekee-1', 'is_enabled' => 1, 'result_mode' => 'yeekee'],
            ['id' => 10, 'group_id' => 1, 'name' => 'Yeekee 2', 'code' => 'yeekee-2', 'is_enabled' => 1, 'result_mode' => 'yeekee'],
            ['id' => 11, 'group_id' => 1, 'name' => 'Normal', 'code' => 'normal-1', 'is_enabled' => 1, 'result_mode' => 'normal'],
        ]);

        DB::table('yeekee_rounds')->insert([
            [
                'id' => 701,
                'market_id' => 9,
                'lotto_draw_id' => 301,
                'round_date' => '2026-04-29',
                'round_no' => 1,
                'bet_open_at' => '2026-04-29 10:00:00',
                'bet_close_at' => '2026-04-29 10:15:00',
                'shoot_open_at' => '2026-04-29 10:15:00',
                'shoot_close_at' => '2026-04-29 10:16:00',
                'result_compute_at' => '2026-04-29 10:17:00',
                'expected_settlement_deadline_at' => '2026-04-29 10:22:00',
                'status' => 'open_bet',
                'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_ONLY']]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 702,
                'market_id' => 10,
                'lotto_draw_id' => 302,
                'round_date' => '2026-04-29',
                'round_no' => 2,
                'bet_open_at' => '2026-04-29 10:20:00',
                'bet_close_at' => '2026-04-29 10:35:00',
                'shoot_open_at' => '2026-04-29 10:35:00',
                'shoot_close_at' => '2026-04-29 10:36:00',
                'result_compute_at' => '2026-04-29 10:37:00',
                'expected_settlement_deadline_at' => '2026-04-29 10:42:00',
                'status' => 'open_bet',
                'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_ONLY']]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/v1/lotto/yeekee/rounds', 'GET', ['draw_date' => '2026-04-29']);
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeRounds($request));

        $response->assertOk();
        $response->assertJsonPath('data.count', 2);
        $response->assertJsonPath('data.items.0.round_id', 701);
        $response->assertJsonPath('data.items.1.round_id', 702);

        Carbon::setTestNow();
    }

    public function test_yeekee_round_returns_detail_by_round_id(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-29 10:05:00'));

        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Yeekee Group',
            'code' => 'yeekee',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 9,
            'group_id' => 1,
            'name' => 'Yeekee 1',
            'code' => 'yeekee-1',
            'is_enabled' => 1,
            'result_mode' => 'yeekee',
        ]);

        DB::table('yeekee_rounds')->insert([
            'id' => 711,
            'market_id' => 9,
            'lotto_draw_id' => 311,
            'round_date' => '2026-04-29',
            'round_no' => 11,
            'bet_open_at' => '2026-04-29 10:00:00',
            'bet_close_at' => '2026-04-29 10:15:00',
            'shoot_open_at' => '2026-04-29 10:15:00',
            'shoot_close_at' => '2026-04-29 10:16:00',
            'result_compute_at' => '2026-04-29 10:17:00',
            'expected_settlement_deadline_at' => '2026-04-29 10:22:00',
            'status' => 'open_bet',
            'config_snapshot_json' => json_encode(['formula_config' => ['preset' => 'SHOOTS_SUM_ONLY']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/lotto/yeekee/rounds/711', 'GET');
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeRound($request, 711));

        $response->assertOk();
        $response->assertJsonPath('data.round_id', 711);
        $response->assertJsonPath('data.market_id', 9);
        $response->assertJsonPath('data.is_open_for_play', true);
        $response->assertJsonPath('data.is_final', false);
        $response->assertJsonPath('data.server_time', now()->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_yeekee_shoots_before_reveal_masks_numbers_and_uses_pagination_contract(): void
    {
        $member = new Member;
        $member->code = 7788;

        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Yeekee Group',
            'code' => 'yeekee',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 9,
            'group_id' => 1,
            'name' => 'Yeekee Market',
            'code' => 'yeekee-market',
            'is_enabled' => 1,
            'result_mode' => 'yeekee',
        ]);

        DB::table('yeekee_rounds')->insert([
            'id' => 720,
            'market_id' => 9,
            'lotto_draw_id' => 320,
            'round_date' => '2026-04-29',
            'round_no' => 20,
            'bet_open_at' => '2026-04-29 10:00:00',
            'bet_close_at' => '2026-04-29 10:15:00',
            'shoot_open_at' => '2026-04-29 10:15:00',
            'shoot_close_at' => '2026-04-29 10:16:00',
            'result_compute_at' => '2026-04-29 10:17:00',
            'expected_settlement_deadline_at' => '2026-04-29 10:22:00',
            'status' => 'shoot_open',
            'config_snapshot_json' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            [
                'yeekee_round_id' => 720,
                'lotto_draw_id' => 320,
                'market_id' => 9,
                'member_id' => 1,
                'position' => 1,
                'number_text' => '12345',
                'number_value' => 12345,
                'submitted_at' => now()->subSeconds(30)->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'yeekee_round_id' => 720,
                'lotto_draw_id' => 320,
                'market_id' => 9,
                'member_id' => 2,
                'position' => 2,
                'number_text' => '99999',
                'number_value' => 99999,
                'submitted_at' => now()->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/v1/lotto/yeekee/rounds/720/shoots', 'GET', ['limit' => 1, 'page' => 1]);
        $request->setUserResolver(static fn (?string $guard = null) => $guard === 'customer' ? $member : null);
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeShoots($request, 720));

        $response->assertOk();
        $response->assertJsonPath('data.display_mode', 'live_masked');
        $response->assertJsonPath('data.is_number_revealed', false);
        $response->assertJsonPath('data.limit', 1);
        $response->assertJsonPath('data.count', 1);
        $response->assertJsonPath('data.shoot_count', 2);
        $response->assertJsonPath('data.shoot_sum', '112344');
        $response->assertJsonPath('data.shoot_source', 'live');
        $response->assertJsonPath('data.items.0.number_text', '999**');
        $response->assertJsonPath('data.items.0.number_text_masked', '999**');
        $response->assertJsonPath('data.items.0.number_text_revealed', null);
        $response->assertJsonPath('data.items.0.is_number_revealed', false);
        $response->assertJsonMissingPath('data.items.0.member_id');
        $response->assertJsonMissingPath('data.items.0.member_code');
        $response->assertJsonMissingPath('data.items.0.customer_id');
        $response->assertJsonMissingPath('data.items.0.ip_address');
        $response->assertJsonMissingPath('data.items.0.user_agent');
        $response->assertJsonPath('data.pagination.page', 1);
        $response->assertJsonPath('data.pagination.limit', 1);
        $response->assertJsonPath('data.pagination.total', 2);
        $response->assertJsonPath('data.pagination.has_more', true);
        $this->assertStringNotContainsString('12345', $response->getContent());
        $this->assertStringNotContainsString('99999', $response->getContent());
    }

    public function test_yeekee_shoots_after_reveal_uses_snapshot_and_can_reveal_number(): void
    {
        $member = new Member;
        $member->code = 7788;

        DB::table('lotto_groups')->insert(['id' => 1, 'name' => 'Yeekee Group', 'code' => 'yeekee', 'is_enabled' => 1, 'sort' => 1]);
        DB::table('lotto_markets')->insert(['id' => 9, 'group_id' => 1, 'name' => 'Yeekee Market', 'code' => 'yeekee-market', 'is_enabled' => 1, 'result_mode' => 'yeekee']);
        DB::table('lotto_draws')->insert(['id' => 321, 'market_id' => 9, 'draw_date' => '2026-04-29', 'status' => 'resulted', 'result_number' => json_encode(['raw_result' => '00123']), 'created_at' => now(), 'updated_at' => now()]);

        $snapshot = [
            'shoots' => [
                ['position' => 16, 'number_text' => '55555', 'number_value' => 55555, 'submitted_at' => now()->subSeconds(3)->format('Y-m-d H:i:s'), 'member_name' => 'boat123'],
                ['position' => 1, 'number_text' => '12345', 'number_value' => 12345, 'submitted_at' => now()->subSeconds(5)->format('Y-m-d H:i:s'), 'member_name' => 'ab'],
            ],
        ];

        DB::table('yeekee_rounds')->insert([
            'id' => 721,
            'market_id' => 9,
            'lotto_draw_id' => 321,
            'round_date' => '2026-04-29',
            'round_no' => 21,
            'bet_open_at' => '2026-04-29 10:00:00',
            'bet_close_at' => '2026-04-29 10:15:00',
            'shoot_open_at' => '2026-04-29 10:15:00',
            'shoot_close_at' => '2026-04-29 10:16:00',
            'result_compute_at' => '2026-04-29 10:17:00',
            'expected_settlement_deadline_at' => '2026-04-29 10:22:00',
            'status' => 'resulted',
            'shoot_snapshot_json' => json_encode($snapshot),
            'shoot_snapshot_hash' => 'snap-721',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/lotto/yeekee/rounds/721/shoots', 'GET', ['limit' => 20, 'page' => 1]);
        $request->setUserResolver(static fn (?string $guard = null) => $guard === 'customer' ? $member : null);
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeShoots($request, 721));

        $response->assertOk();
        $response->assertJsonPath('data.display_mode', 'result_revealed');
        $response->assertJsonPath('data.is_number_revealed', true);
        $response->assertJsonPath('data.shoot_source', 'snapshot');
        $response->assertJsonPath('data.items.0.number_text', '55555');
        $response->assertJsonPath('data.items.0.number_text_masked', '555**');
        $response->assertJsonPath('data.items.0.number_text_revealed', '55555');
        $response->assertJsonPath('data.items.0.member_name_prefix_masked', 'bo*****');
        $response->assertJsonPath('data.items.0.member_name_masked', '**at123');
        $response->assertJsonMissingPath('data.items.0.member_id');
        $response->assertJsonMissingPath('data.items.0.ip_address');
    }

    public function test_yeekee_shoots_snapshot_source_remains_stable_when_live_changes(): void
    {
        $member = new Member;
        $member->code = 7788;

        DB::table('lotto_groups')->insert(['id' => 1, 'name' => 'Yeekee Group', 'code' => 'yeekee', 'is_enabled' => 1, 'sort' => 1]);
        DB::table('lotto_markets')->insert(['id' => 9, 'group_id' => 1, 'name' => 'Yeekee Market', 'code' => 'yeekee-market', 'is_enabled' => 1, 'result_mode' => 'yeekee']);
        DB::table('lotto_draws')->insert(['id' => 322, 'market_id' => 9, 'draw_date' => '2026-04-29', 'status' => 'resulted', 'created_at' => now(), 'updated_at' => now()]);

        DB::table('yeekee_rounds')->insert([
            'id' => 722,
            'market_id' => 9,
            'lotto_draw_id' => 322,
            'round_date' => '2026-04-29',
            'round_no' => 22,
            'bet_open_at' => '2026-04-29 10:00:00',
            'bet_close_at' => '2026-04-29 10:15:00',
            'shoot_open_at' => '2026-04-29 10:15:00',
            'shoot_close_at' => '2026-04-29 10:16:00',
            'result_compute_at' => '2026-04-29 10:17:00',
            'expected_settlement_deadline_at' => '2026-04-29 10:22:00',
            'status' => 'resulted',
            'shoot_snapshot_json' => json_encode(['shoots' => [['position' => 1, 'number_text' => '12345', 'number_value' => 12345, 'submitted_at' => now()->format('Y-m-d H:i:s')]]]),
            'shoot_snapshot_hash' => 'snap-722',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            'yeekee_round_id' => 722,
            'lotto_draw_id' => 322,
            'market_id' => 9,
            'member_id' => 1,
            'position' => 99,
            'number_text' => '99999',
            'number_value' => 99999,
            'submitted_at' => now()->format('Y-m-d H:i:s'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/lotto/yeekee/rounds/722/shoots', 'GET');
        $request->setUserResolver(static fn (?string $guard = null) => $guard === 'customer' ? $member : null);
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeShoots($request, 722));

        $response->assertOk();
        $response->assertJsonPath('data.shoot_source', 'snapshot');
        $response->assertJsonPath('data.shoot_count', 1);
        $response->assertJsonPath('data.shoot_sum', '12345');
        $response->assertJsonPath('data.items.0.position', 1);
    }

    public function test_yeekee_shoots_hash_without_snapshot_json_falls_back_to_live_source(): void
    {
        $member = new Member;
        $member->code = 7788;

        DB::table('lotto_groups')->insert(['id' => 1, 'name' => 'Yeekee Group', 'code' => 'yeekee', 'is_enabled' => 1, 'sort' => 1]);
        DB::table('lotto_markets')->insert(['id' => 9, 'group_id' => 1, 'name' => 'Yeekee Market', 'code' => 'yeekee-market', 'is_enabled' => 1, 'result_mode' => 'yeekee']);
        DB::table('lotto_draws')->insert(['id' => 324, 'market_id' => 9, 'draw_date' => '2026-04-29', 'status' => 'shoot_open', 'created_at' => now(), 'updated_at' => now()]);

        DB::table('yeekee_rounds')->insert([
            'id' => 724,
            'market_id' => 9,
            'lotto_draw_id' => 324,
            'round_date' => '2026-04-29',
            'round_no' => 24,
            'bet_open_at' => '2026-04-29 10:00:00',
            'bet_close_at' => '2026-04-29 10:15:00',
            'shoot_open_at' => '2026-04-29 10:15:00',
            'shoot_close_at' => '2026-04-29 10:16:00',
            'result_compute_at' => '2026-04-29 10:17:00',
            'expected_settlement_deadline_at' => '2026-04-29 10:22:00',
            'status' => 'shoot_open',
            'shoot_snapshot_json' => null,
            'shoot_snapshot_hash' => 'hash-only',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            'yeekee_round_id' => 724,
            'lotto_draw_id' => 324,
            'market_id' => 9,
            'member_id' => 1,
            'position' => 1,
            'number_text' => '12345',
            'number_value' => 12345,
            'submitted_at' => now()->format('Y-m-d H:i:s'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/lotto/yeekee/rounds/724/shoots', 'GET');
        $request->setUserResolver(static fn (?string $guard = null) => $guard === 'customer' ? $member : null);
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeShoots($request, 724));

        $response->assertOk();
        $response->assertJsonPath('data.shoot_source', 'live');
        $response->assertJsonPath('data.shoot_count', 1);
        $response->assertJsonPath('data.items.0.number_text', '123**');
    }

    public function test_yeekee_result_proof_contains_shoot_summary_and_winners_without_full_list(): void
    {
        DB::table('lotto_groups')->insert(['id' => 1, 'name' => 'Yeekee Group', 'code' => 'yeekee', 'is_enabled' => 1, 'sort' => 1]);
        DB::table('lotto_markets')->insert(['id' => 9, 'group_id' => 1, 'name' => 'Yeekee Market', 'code' => 'yeekee-market', 'is_enabled' => 1, 'result_mode' => 'yeekee']);
        DB::table('lotto_draws')->insert([
            'id' => 323,
            'market_id' => 9,
            'draw_date' => '2026-04-29',
            'status' => 'closed',
            'result_number' => json_encode(['raw_result' => '45678', 'top_3' => '678', 'bottom_2' => '78']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_rounds')->insert([
            'id' => 723,
            'market_id' => 9,
            'lotto_draw_id' => 323,
            'round_date' => '2026-04-29',
            'round_no' => 23,
            'bet_open_at' => '2026-04-29 10:00:00',
            'bet_close_at' => '2026-04-29 10:15:00',
            'shoot_open_at' => '2026-04-29 10:15:00',
            'shoot_close_at' => '2026-04-29 10:16:00',
            'result_compute_at' => '2026-04-29 10:17:00',
            'expected_settlement_deadline_at' => '2026-04-29 10:22:00',
            'status' => 'shoot_open',
            'shoot_snapshot_json' => json_encode(['shoots' => [
                ['position' => 1, 'number_text' => '12345', 'number_value' => 12345, 'submitted_at' => now()->format('Y-m-d H:i:s')],
                ['position' => 16, 'number_text' => '54321', 'number_value' => 54321, 'submitted_at' => now()->format('Y-m-d H:i:s')],
            ]]),
            'shoot_snapshot_hash' => 'snap-723',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/lotto/yeekee/rounds/723/result-proof', 'GET');
        $response = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeResultProof($request, 723));
        $response->assertOk();
        $response->assertJsonPath('data.is_revealed', false);
        $response->assertJsonPath('data.shoot_summary.shoot_source', 'snapshot');
        $response->assertJsonPath('data.winning_shoots.first.position', 1);
        $response->assertJsonPath('data.winning_shoots.first.number_text', '123**');
        $response->assertJsonPath('data.winning_shoots.first.number_text_revealed', null);
        $response->assertJsonPath('data.winning_shoots.first.is_number_revealed', false);
        $response->assertJsonPath('data.proof.result_payload', null);
        $response->assertJsonMissingPath('data.shoots');
        $response->assertJsonMissingPath('data.winning_shoots.first.member_id');
        $response->assertJsonMissingPath('data.winning_shoots.first.ip_address');
        $response->assertJsonMissingPath('data.winning_shoots.first.user_agent');
        $this->assertStringNotContainsString('12345', $response->getContent());
        $this->assertStringNotContainsString('54321', $response->getContent());

        DB::table('lotto_draws')->where('id', 323)->update(['status' => 'resulted']);
        DB::table('yeekee_rounds')->where('id', 723)->update(['status' => 'resulted']);
        $revealed = TestResponse::fromBaseResponse(app(LottoController::class)->yeekeeResultProof($request, 723));
        $revealed->assertOk();
        $revealed->assertJsonPath('data.is_revealed', true);
        $revealed->assertJsonPath('data.winning_shoots.first.number_text_revealed', '12345');
        $revealed->assertJsonPath('data.proof.result_payload.shoot_sum', '66666');
    }

    public function test_submit_shoot_returns_http_429_on_cooldown_exception(): void
    {
        $member = new Member;
        $member->code = 1001;

        $request = Request::create('/api/v1/lotto/yeekee/rounds/500/shoot', 'POST', [
            'number' => '12345',
        ]);
        $request->setUserResolver(static fn (?string $guard = null) => $guard === 'customer' ? $member : null);

        $service = new class extends YeekeeShootService
        {
            public function submitShoot(
                int $memberId,
                int $roundId,
                string $numberText,
                ?string $ipAddress = null,
                ?string $userAgent = null
            ): YeekeeShoot {
                throw new YeekeeShootCooldownException(
                    cooldownSeconds: 6,
                    remainingCooldownSeconds: 4,
                    nextAllowedAt: '2026-05-02 12:00:06'
                );
            }
        };

        $response = TestResponse::fromBaseResponse(app(LottoController::class)->submitShoot($request, 500, $service));

        $response->assertStatus(429);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error_code', 'YEEKEE_SHOOT_COOLDOWN');
        $response->assertJsonPath('cooldown_seconds', 6);
        $response->assertJsonPath('remaining_cooldown_seconds', 4);
        $response->assertJsonPath('next_allowed_at', '2026-05-02 12:00:06');
    }
}
