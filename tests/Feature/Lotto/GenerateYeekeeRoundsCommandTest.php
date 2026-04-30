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

    private function seedYeekeeMarket(int $marketId, int $groupId, int $durationMinutes): void
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
                'shoot_window_after_bet_close_seconds' => 60,
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
