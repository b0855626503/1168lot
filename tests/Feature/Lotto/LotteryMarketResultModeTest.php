<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Http\Controllers\Admin\LotteryMarketController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LotteryMarketResultModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('yeekee_market_settings');
        Schema::dropIfExists('lotto_market_contents');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('logs');

        parent::tearDown();
    }

    public function test_create_yeekee_market_creates_default_setting_record(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Main',
            'code' => 'main',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        $request = Request::create('/admin/lotto/markets/create', 'POST', [
            'data' => [
                'group_id' => 1,
                'name' => 'Yeekee Market',
                'code' => 'yeekee_market',
                'result_mode' => 'yeekee',
                'draw_mode' => 'manual',
                'draw_schedule_type' => 'manual',
                'is_enabled' => 1,
            ],
        ]);

        $response = $this->createTestResponse(
            app(LotteryMarketController::class)->create($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $marketId = (int) DB::table('lotto_markets')->where('code', 'yeekee_market')->value('id');
        $this->assertGreaterThan(0, $marketId);

        $setting = DB::table('yeekee_market_settings')->where('market_id', $marketId)->first();
        $this->assertNotNull($setting);
        $this->assertSame(0, (int) $setting->reward_enabled);
        $this->assertSame(0, (int) $setting->refund_if_bet_entries_below_min);
    }

    public function test_update_market_result_mode_is_blocked_when_draw_or_ticket_exists(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Main',
            'code' => 'main',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 10,
            'group_id' => 1,
            'name' => 'Normal Market',
            'code' => 'normal_market',
            'result_mode' => 'normal',
            'draw_mode' => 'manual',
            'draw_schedule_type' => 'manual',
            'is_enabled' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 100,
            'market_id' => 10,
            'draw_date' => '2026-04-30',
            'open_at' => '2026-04-30 09:00:00',
            'close_at' => '2026-04-30 10:00:00',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admin/lotto/markets/update', 'POST', [
            'id' => 10,
            'data' => [
                'group_id' => 1,
                'name' => 'Normal Market',
                'code' => 'normal_market',
                'result_mode' => 'yeekee',
                'draw_mode' => 'manual',
                'draw_schedule_type' => 'manual',
                'is_enabled' => 1,
            ],
        ]);

        $this->expectException(ValidationException::class);
        app(LotteryMarketController::class)->update($request);
    }

    public function test_create_normal_market_does_not_create_yeekee_setting_record(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Main',
            'code' => 'main',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        $request = Request::create('/admin/lotto/markets/create', 'POST', [
            'data' => [
                'group_id' => 1,
                'name' => 'Normal Market',
                'code' => 'normal_market',
                'result_mode' => 'normal',
                'draw_mode' => 'manual',
                'draw_schedule_type' => 'manual',
                'is_enabled' => 1,
            ],
        ]);

        $response = $this->createTestResponse(
            app(LotteryMarketController::class)->create($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $marketId = (int) DB::table('lotto_markets')->where('code', 'normal_market')->value('id');
        $this->assertGreaterThan(0, $marketId);
        $this->assertSame(0, DB::table('yeekee_market_settings')->where('market_id', $marketId)->count());
    }

    public function test_update_yeekee_market_rejects_unsupported_formula_preset(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Main',
            'code' => 'main',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 20,
            'group_id' => 1,
            'name' => 'Yeekee Market',
            'code' => 'yeekee_market_2',
            'result_mode' => 'yeekee',
            'draw_mode' => 'manual',
            'draw_schedule_type' => 'manual',
            'is_enabled' => 1,
        ]);

        DB::table('yeekee_market_settings')->insert([
            'market_id' => 20,
            'round_config' => json_encode(['round_duration_minutes' => 15]),
            'formula_config' => json_encode(['default_preset' => 'SHOOTS_SUM_MINUS_POSITION']),
            'reward_config' => json_encode(['reward_enabled' => false]),
            'refund_config' => json_encode(['refund_if_bet_entries_below_min' => false]),
            'reward_enabled' => 0,
            'refund_if_bet_entries_below_min' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admin/lotto/markets/update', 'POST', [
            'id' => 20,
            'data' => [
                'group_id' => 1,
                'name' => 'Yeekee Market',
                'code' => 'yeekee_market_2',
                'result_mode' => 'yeekee',
                'draw_mode' => 'manual',
                'draw_schedule_type' => 'manual',
                'is_enabled' => 1,
                'yeekee_settings' => [
                    'round_duration_minutes' => 10,
                    'shoot_window_after_bet_close_seconds' => 30,
                    'settlement_delay_after_shoot_close_seconds' => 45,
                    'expected_payout_sla_minutes' => 3,
                    'formula_preset' => 'PRECOMMITTED_BASE64_MD5',
                    'reward_enabled' => true,
                    'refund_if_bet_entries_below_min' => true,
                    'min_bet_entries_required' => 5,
                    'refund_count_mode' => 'count_unique_members',
                    'refund_action' => 'VOID_AND_REFUND',
                ],
            ],
        ]);

        $this->expectException(ValidationException::class);
        app(LotteryMarketController::class)->update($request);
    }

    public function test_update_shoots_sum_only_rejects_include_status_rule_in_v1(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Main',
            'code' => 'main',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 21,
            'group_id' => 1,
            'name' => 'Yeekee Market',
            'code' => 'yeekee_market_21',
            'result_mode' => 'yeekee',
            'draw_mode' => 'manual',
            'draw_schedule_type' => 'manual',
            'is_enabled' => 1,
        ]);

        DB::table('yeekee_market_settings')->insert([
            'market_id' => 21,
            'round_config' => json_encode(['round_duration_minutes' => 15]),
            'formula_config' => json_encode(['default_preset' => 'SHOOTS_SUM_ONLY', 'modulo' => 100000]),
            'reward_config' => json_encode(['reward_enabled' => false]),
            'refund_config' => json_encode(['refund_if_bet_entries_below_min' => false]),
            'reward_enabled' => 0,
            'refund_if_bet_entries_below_min' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admin/lotto/markets/update', 'POST', [
            'id' => 21,
            'data' => [
                'group_id' => 1,
                'name' => 'Yeekee Market',
                'code' => 'yeekee_market_21',
                'result_mode' => 'yeekee',
                'draw_mode' => 'manual',
                'draw_schedule_type' => 'manual',
                'is_enabled' => 1,
                'yeekee_settings' => [
                    'formula_preset' => 'SHOOTS_SUM_ONLY',
                    'modulo' => 100000,
                    'input_rules' => [
                        'include_status' => ['accepted'],
                    ],
                ],
            ],
        ]);

        $this->expectException(ValidationException::class);
        app(LotteryMarketController::class)->update($request);
    }

    public function test_update_yeekee_market_persists_false_toggle_flags_from_form_data_strings(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Main',
            'code' => 'main',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 31,
            'group_id' => 1,
            'name' => 'Yeekee Toggle Market',
            'code' => 'yeekee_toggle_market',
            'result_mode' => 'yeekee',
            'draw_mode' => 'manual',
            'draw_schedule_type' => 'manual',
            'is_enabled' => 1,
        ]);

        DB::table('yeekee_market_settings')->insert([
            'market_id' => 31,
            'round_config' => json_encode(['round_duration_minutes' => 15]),
            'formula_config' => json_encode(['default_preset' => 'SHOOTS_SUM_MINUS_POSITION', 'subtract_position' => 16]),
            'reward_config' => json_encode(['reward_enabled' => true]),
            'refund_config' => json_encode(['refund_if_bet_entries_below_min' => true]),
            'reward_enabled' => 1,
            'refund_if_bet_entries_below_min' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admin/lotto/markets/update', 'POST', [
            'id' => 31,
            'data' => [
                'group_id' => 1,
                'name' => 'Yeekee Toggle Market',
                'code' => 'yeekee_toggle_market',
                'result_mode' => 'yeekee',
                'draw_mode' => 'manual',
                'draw_schedule_type' => 'manual',
                'is_enabled' => 1,
                'yeekee_settings' => [
                    'round_duration_minutes' => 15,
                    'shoot_window_after_bet_close_seconds' => 60,
                    'settlement_delay_after_shoot_close_seconds' => 60,
                    'expected_payout_sla_minutes' => 5,
                    'formula_preset' => 'SHOOTS_SUM_ONLY',
                    'modulo' => 100000,
                    'reward_enabled' => 'false',
                    'refund_if_bet_entries_below_min' => '0',
                    'refund_count_mode' => 'count_bet_entries',
                    'refund_action' => 'VOID_AND_REFUND',
                ],
            ],
        ]);

        $response = $this->createTestResponse(
            app(LotteryMarketController::class)->update($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $setting = DB::table('yeekee_market_settings')->where('market_id', 31)->first();
        $this->assertNotNull($setting);
        $this->assertSame(0, (int) $setting->reward_enabled);
        $this->assertSame(0, (int) $setting->refund_if_bet_entries_below_min);
    }

    public function test_load_data_returns_yeekee_reward_positions(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Main',
            'code' => 'main',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 30,
            'group_id' => 1,
            'name' => 'Yeekee Market',
            'code' => 'yeekee_market_3',
            'result_mode' => 'yeekee',
            'draw_mode' => 'manual',
            'draw_schedule_type' => 'manual',
            'is_enabled' => 1,
        ]);

        DB::table('yeekee_market_settings')->insert([
            'market_id' => 30,
            'round_config' => json_encode(['round_duration_minutes' => 15]),
            'formula_config' => json_encode(['default_preset' => 'SHOOTS_SUM_MINUS_POSITION', 'subtract_position' => 16]),
            'reward_config' => json_encode([
                'reward_enabled' => true,
                'reward_positions' => [
                    ['position' => 1, 'credit_amount' => 20],
                    ['position' => 16, 'credit_amount' => 50],
                ],
                'min_bet_amount' => 100,
            ]),
            'refund_config' => json_encode(['refund_if_bet_entries_below_min' => false]),
            'reward_enabled' => 1,
            'refund_if_bet_entries_below_min' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admin/lotto/markets/loaddata', 'POST', [
            'id' => 30,
        ]);

        $response = $this->createTestResponse(
            app(LotteryMarketController::class)->loadData($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.yeekee_settings.reward_positions.0.position', 1);
        $response->assertJsonPath('data.yeekee_settings.reward_positions.0.credit_amount', 20);
        $response->assertJsonPath('data.yeekee_settings.reward_positions.1.position', 16);
        $response->assertJsonPath('data.yeekee_settings.reward_positions.1.credit_amount', 50);
    }

    public function test_create_market_with_contents_stores_normalized_locale_and_sanitized_html(): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 9,
            'name' => 'Main',
            'code' => 'main9',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        $request = Request::create('/admin/lotto/markets/create', 'POST', [
            'data' => [
                'group_id' => 9,
                'name' => 'Content Market',
                'code' => 'content_market',
                'result_mode' => 'normal',
                'draw_mode' => 'manual',
                'draw_schedule_type' => 'manual',
                'is_enabled' => 1,
                'contents' => [
                    'laos' => [
                        'title' => 'Lao title',
                        'rules_content' => '<script>alert(1)</script><p onclick="alert(2)">safe</p>',
                        'is_enabled' => 1,
                    ],
                    'khmer' => [
                        'title' => 'Khmer title',
                        'summary' => 'summary',
                        'is_enabled' => 1,
                    ],
                ],
            ],
        ]);

        $response = $this->createTestResponse(app(LotteryMarketController::class)->create($request));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $marketId = (int) DB::table('lotto_markets')->where('code', 'content_market')->value('id');
        $this->assertGreaterThan(0, $marketId);

        $this->assertSame(1, DB::table('lotto_market_contents')->where('market_id', $marketId)->where('locale', 'lo')->count());
        $this->assertSame(1, DB::table('lotto_market_contents')->where('market_id', $marketId)->where('locale', 'km')->count());

        $loRules = (string) DB::table('lotto_market_contents')->where('market_id', $marketId)->where('locale', 'lo')->value('rules_content');
        $this->assertStringNotContainsString('<script', $loRules);
        $this->assertStringNotContainsString('onclick', $loRules);

        $loadResponse = $this->createTestResponse(
            app(LotteryMarketController::class)->loadData(Request::create('/admin/lotto/markets/loaddata', 'POST', ['id' => $marketId]))
        );

        $loadResponse->assertStatus(200);
        $loadResponse->assertJsonPath('success', true);
        $loadResponse->assertJsonPath('data.contents.lo.title', 'Lao title');
        $loadResponse->assertJsonPath('data.contents.km.title', 'Khmer title');
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
            $table->string('name_en')->nullable();
            $table->string('name_kh')->nullable();
            $table->string('name_laos')->nullable();
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->string('code')->unique();
            $table->string('result_mode', 32)->default('normal');
            $table->string('draw_mode')->nullable();
            $table->string('draw_schedule_type')->nullable();
            $table->json('draw_days')->nullable();
            $table->json('draw_dates')->nullable();
            $table->time('auto_open_time')->nullable();
            $table->time('auto_close_time')->nullable();
            $table->time('auto_result_time')->nullable();
            $table->string('result_url')->nullable();
            $table->boolean('auto_settle_on_result')->default(true);
            $table->boolean('auto_refund_on_no_result')->default(false);
            $table->boolean('notify_result_telegram')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('affect_existing_members')->default(false);
            $table->unsignedInteger('policy_version')->default(0);
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

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['active', 'cancelled', 'resulted'])->default('active');
            $table->timestamps();
        });

        Schema::create('lotto_market_contents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->text('summary')->nullable();
            $table->longText('rules_content')->nullable();
            $table->longText('schedule_content')->nullable();
            $table->longText('prize_content')->nullable();
            $table->longText('formula_content')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['market_id', 'locale']);
        });

        Schema::create('yeekee_market_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->json('round_config')->nullable();
            $table->json('formula_config')->nullable();
            $table->json('reward_config')->nullable();
            $table->json('refund_config')->nullable();
            $table->json('ui_config')->nullable();
            $table->boolean('reward_enabled')->default(false);
            $table->boolean('refund_if_bet_entries_below_min')->default(false);
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
