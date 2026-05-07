<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GenerateYeekeeRoundsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('yeekee_market_settings');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('logs');

        parent::tearDown();
    }

    public function test_generate_yeekee_rounds_creates_draws_and_rounds_from_config(): void
    {
        $this->seedYeekeeMarket(11, 1, 60);

        Artisan::call('lotto:generate-yeekee-draws', [
            '--date' => '2026-05-01',
        ]);

        $this->assertSame(23, DB::table('lotto_draws')->where('market_id', 11)->count());
        $this->assertSame(23, DB::table('yeekee_rounds')->where('market_id', 11)->count());
        $firstRound = DB::table('yeekee_rounds')->where('market_id', 11)->orderBy('id')->first();
        $this->assertNotNull($firstRound);
        $this->assertSame((string) $firstRound->bet_open_at, (string) $firstRound->shoot_open_at);
        $this->assertSame(
            Carbon::parse((string) $firstRound->bet_close_at)->addSeconds(60)->format('Y-m-d H:i:s'),
            (string) $firstRound->shoot_close_at
        );

        $snapshot = DB::table('yeekee_rounds')
            ->where('market_id', 11)
            ->orderBy('id')
            ->value('config_snapshot_json');
        $decoded = is_string($snapshot) ? json_decode($snapshot, true) : [];

        $this->assertIsArray($decoded);
        $this->assertSame('SHOOTS_SUM_MINUS_POSITION', (string) ($decoded['formula_config']['preset'] ?? ''));
        $this->assertSame(1, (int) ($decoded['formula_config']['version'] ?? 0));
        $this->assertSame(16, (int) ($decoded['formula_config']['subtract_position'] ?? 0));
        $this->assertArrayNotHasKey('default_preset', (array) ($decoded['formula_config'] ?? []));
        $this->assertSame(60, (int) ($decoded['round_config']['round_duration_minutes'] ?? 0));
        $this->assertArrayHasKey('reward_enabled', $decoded);
        $this->assertSame(0, (int) ($decoded['reward_enabled'] ?? 1));
        $this->assertIsArray($decoded['reward_config'] ?? null);
        $this->assertIsArray($decoded['refund_config'] ?? null);
        $this->assertIsArray($decoded['external_seed_config'] ?? null);
    }

    public function test_generate_yeekee_rounds_is_idempotent_on_rerun(): void
    {
        $this->seedYeekeeMarket(11, 1, 60);

        Artisan::call('lotto:generate-yeekee-draws', [
            '--date' => '2026-05-01',
        ]);
        Artisan::call('lotto:generate-yeekee-draws', [
            '--date' => '2026-05-01',
        ]);

        $this->assertSame(23, DB::table('lotto_draws')->where('market_id', 11)->count());
        $this->assertSame(23, DB::table('yeekee_rounds')->where('market_id', 11)->count());
    }

    public function test_generate_yeekee_rounds_respects_market_filter_and_ignores_non_yeekee_market(): void
    {
        $this->seedYeekeeMarket(11, 1, 60);
        $this->seedYeekeeMarket(12, 1, 30);

        DB::table('lotto_markets')->insert([
            'id' => 13,
            'group_id' => 1,
            'name' => 'Normal Market',
            'code' => 'normal_market',
            'result_mode' => 'normal',
            'draw_mode' => 'manual',
            'draw_schedule_type' => 'manual',
            'is_enabled' => 1,
        ]);

        Artisan::call('lotto:generate-yeekee-draws', [
            '--date' => '2026-05-01',
            '--market_id' => 11,
        ]);

        $this->assertSame(23, DB::table('lotto_draws')->where('market_id', 11)->count());
        $this->assertSame(23, DB::table('yeekee_rounds')->where('market_id', 11)->count());

        $this->assertSame(0, DB::table('lotto_draws')->where('market_id', 12)->count());
        $this->assertSame(0, DB::table('yeekee_rounds')->where('market_id', 12)->count());
        $this->assertSame(0, DB::table('lotto_draws')->where('market_id', 13)->count());
    }

    public function test_generate_yeekee_rounds_supports_window_top_up_mode(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 20:00:00'));
        $this->seedYeekeeMarket(11, 1, 60);

        Artisan::call('lotto:generate-yeekee-draws', [
            '--window' => '+6h',
        ]);

        $this->assertSame(46, DB::table('lotto_draws')->where('market_id', 11)->count());
        $this->assertSame(46, DB::table('yeekee_rounds')->where('market_id', 11)->count());

        Carbon::setTestNow();
    }

    public function test_generate_yeekee_rounds_supports_tomorrow_alias(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 00:05:00'));
        $this->seedYeekeeMarket(11, 1, 60);
        $expectedDate = now((string) config('app.timezone', 'UTC'))->addDay()->format('Y-m-d');

        Artisan::call('lotto:generate-yeekee-draws', [
            '--date' => 'tomorrow',
        ]);
        $output = json_decode((string) Artisan::output(), true);

        $this->assertIsArray($output);
        $this->assertSame([$expectedDate], $output['dates'] ?? []);
        $this->assertGreaterThan(0, (int) ($output['draw_created'] ?? 0));
        $this->assertSame(
            23,
            DB::table('lotto_draws')->where('market_id', 11)->where('status', 'draft')->count()
        );

        Carbon::setTestNow();
    }

    public function test_generate_yeekee_rounds_skips_cross_day_boundary_rows(): void
    {
        $this->seedYeekeeMarket(11, 1, 15);

        Artisan::call('lotto:generate-yeekee-draws', [
            '--date' => '2026-05-01',
        ]);

        $this->assertSame(95, DB::table('lotto_draws')->where('market_id', 11)->count());
        $this->assertSame(95, DB::table('yeekee_rounds')->where('market_id', 11)->count());

        $crossDayCloseAtRows = DB::table('lotto_draws')
            ->where('market_id', 11)
            ->whereRaw('date(close_at) <> date(draw_date)')
            ->count();
        $crossDayResultAtRows = DB::table('lotto_draws')
            ->where('market_id', 11)
            ->whereRaw('date(result_at) <> date(draw_date)')
            ->count();

        $this->assertSame(0, $crossDayCloseAtRows);
        $this->assertSame(0, $crossDayResultAtRows);
    }

    public function test_generate_yeekee_rounds_use_same_open_at_for_all_rounds_and_draft_status_for_future_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-30 23:50:00'));
        $this->seedYeekeeMarket(11, 1, 15);

        Artisan::call('lotto:generate-yeekee-draws', [
            '--date' => '2026-05-01',
        ]);

        $openAtDistinctCount = DB::table('lotto_draws')
            ->where('market_id', 11)
            ->distinct()
            ->count('open_at');

        $this->assertSame(1, $openAtDistinctCount);
        $this->assertSame(
            95,
            DB::table('lotto_draws')->where('market_id', 11)->where('status', 'draft')->count()
        );
        $this->assertSame(
            95,
            DB::table('yeekee_rounds')->where('market_id', 11)->where('status', 'open')->count()
        );

        Carbon::setTestNow();
    }

    public function test_generate_yeekee_rounds_uses_zero_seconds_when_shoot_window_setting_is_zero(): void
    {
        $this->seedYeekeeMarket(14, 1, 60, 0);

        Artisan::call('lotto:generate-yeekee-draws', [
            '--date' => '2026-05-01',
            '--market_id' => 14,
        ]);

        $firstRound = DB::table('yeekee_rounds')->where('market_id', 14)->orderBy('id')->first();
        $this->assertNotNull($firstRound);
        $this->assertSame((string) $firstRound->bet_open_at, (string) $firstRound->shoot_open_at);
        $this->assertSame((string) $firstRound->bet_close_at, (string) $firstRound->shoot_close_at);
    }

    public function test_generate_yeekee_rounds_uses_zero_seconds_when_shoot_window_setting_is_null(): void
    {
        $this->seedYeekeeMarket(15, 1, 60, null);

        Artisan::call('lotto:generate-yeekee-draws', [
            '--date' => '2026-05-01',
            '--market_id' => 15,
        ]);

        $firstRound = DB::table('yeekee_rounds')->where('market_id', 15)->orderBy('id')->first();
        $this->assertNotNull($firstRound);
        $this->assertSame((string) $firstRound->bet_open_at, (string) $firstRound->shoot_open_at);
        $this->assertSame((string) $firstRound->bet_close_at, (string) $firstRound->shoot_close_at);
    }

    public function test_sync_yeekee_round_config_snapshots_updates_only_draft_and_open_rounds(): void
    {
        $this->seedYeekeeMarket(11, 1, 60);

        DB::table('yeekee_market_settings')->where('market_id', 11)->update([
            'reward_enabled' => 1,
            'reward_config' => json_encode([
                'reward_enabled' => true,
                'reward_positions' => [
                    ['position' => 1, 'credit_amount' => 20],
                    ['position' => 16, 'credit_amount' => 50],
                ],
                'min_bet_amount' => 100,
            ], JSON_UNESCAPED_UNICODE),
            'formula_config' => json_encode([
                'default_preset' => 'SHOOTS_SUM_MINUS_POSITION',
                'subtract_position' => 16,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        DB::table('lotto_draws')->insert([
            ['id' => 9001, 'market_id' => 11, 'draw_date' => '2026-05-01', 'open_at' => '2026-05-01 00:00:00', 'close_at' => '2026-05-01 00:15:00', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9002, 'market_id' => 11, 'draw_date' => '2026-05-01', 'open_at' => '2026-05-01 00:00:00', 'close_at' => '2026-05-01 00:30:00', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9003, 'market_id' => 11, 'draw_date' => '2026-05-01', 'open_at' => '2026-05-01 00:00:00', 'close_at' => '2026-05-01 00:45:00', 'status' => 'closed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('yeekee_rounds')->insert([
            ['id' => 9101, 'market_id' => 11, 'lotto_draw_id' => 9001, 'round_date' => '2026-05-01', 'round_no' => 1, 'bet_open_at' => now(), 'bet_close_at' => now(), 'shoot_open_at' => now(), 'shoot_close_at' => now(), 'result_compute_at' => now(), 'expected_settlement_deadline_at' => now(), 'status' => 'draft', 'config_snapshot_json' => json_encode(['reward_enabled' => false]), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9102, 'market_id' => 11, 'lotto_draw_id' => 9002, 'round_date' => '2026-05-01', 'round_no' => 2, 'bet_open_at' => now(), 'bet_close_at' => now(), 'shoot_open_at' => now(), 'shoot_close_at' => now(), 'result_compute_at' => now(), 'expected_settlement_deadline_at' => now(), 'status' => 'open', 'config_snapshot_json' => json_encode(['reward_enabled' => false]), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9103, 'market_id' => 11, 'lotto_draw_id' => 9003, 'round_date' => '2026-05-01', 'round_no' => 3, 'bet_open_at' => now(), 'bet_close_at' => now(), 'shoot_open_at' => now(), 'shoot_close_at' => now(), 'result_compute_at' => now(), 'expected_settlement_deadline_at' => now(), 'status' => 'draft', 'config_snapshot_json' => json_encode(['reward_enabled' => false]), 'created_at' => now(), 'updated_at' => now()],
        ]);

        Artisan::call('lotto:sync-yeekee-round-config-snapshots', [
            '--market_id' => 11,
        ]);
        $outputLines = preg_split('/\r\n|\r|\n/', trim((string) Artisan::output())) ?: [];
        $summaryLine = end($outputLines);
        $summary = is_string($summaryLine) ? json_decode($summaryLine, true) : null;

        $this->assertIsArray($summary);
        $this->assertSame(2, (int) ($summary['rounds_updated'] ?? 0));

        $draftSnapshot = json_decode((string) DB::table('yeekee_rounds')->where('id', 9101)->value('config_snapshot_json'), true);
        $openSnapshot = json_decode((string) DB::table('yeekee_rounds')->where('id', 9102)->value('config_snapshot_json'), true);
        $closedSnapshot = json_decode((string) DB::table('yeekee_rounds')->where('id', 9103)->value('config_snapshot_json'), true);

        $this->assertSame(true, (bool) ($draftSnapshot['reward_enabled'] ?? false));
        $this->assertSame(16, (int) ($openSnapshot['reward_config']['reward_positions'][1]['position'] ?? 0));
        $this->assertSame(false, (bool) ($closedSnapshot['reward_enabled'] ?? true));
    }

    private function seedYeekeeMarket(int $marketId, int $groupId, int $durationMinutes, ?int $shootWindowSeconds = 60): void
    {
        if (! DB::table('lotto_groups')->where('id', $groupId)->exists()) {
            DB::table('lotto_groups')->insert([
                'id' => $groupId,
                'name' => 'Main',
                'code' => 'main_'.$groupId,
                'is_enabled' => 1,
                'sort' => 1,
            ]);
        }

        DB::table('lotto_markets')->insert([
            'id' => $marketId,
            'group_id' => $groupId,
            'name' => 'Yeekee '.$marketId,
            'code' => 'yeekee_'.$marketId,
            'result_mode' => 'yeekee',
            'draw_mode' => 'manual',
            'draw_schedule_type' => 'manual',
            'is_enabled' => 1,
        ]);

        DB::table('yeekee_market_settings')->insert([
            'market_id' => $marketId,
            'round_config' => json_encode([
                'round_duration_minutes' => $durationMinutes,
                'shoot_window_after_bet_close_seconds' => $shootWindowSeconds,
                'settlement_delay_after_shoot_close_seconds' => 60,
                'expected_payout_sla_minutes' => 5,
            ]),
            'formula_config' => null,
            'reward_config' => null,
            'refund_config' => null,
            'ui_config' => null,
            'reward_enabled' => 0,
            'refund_if_bet_entries_below_min' => 0,
            'created_at' => now(),
            'updated_at' => now(),
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
            $table->string('result_mode', 32)->default('normal');
            $table->string('draw_mode')->nullable();
            $table->string('draw_schedule_type')->nullable();
            $table->boolean('is_enabled')->default(true);
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
