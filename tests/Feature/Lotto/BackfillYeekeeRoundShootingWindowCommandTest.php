<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillYeekeeRoundShootingWindowCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yeekee_shoots');
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('yeekee_market_settings');
        Schema::dropIfExists('lotto_markets');

        parent::tearDown();
    }

    public function test_dry_run_does_not_update_database(): void
    {
        $this->seedMarket(11, 'yeekee');
        $this->seedMarketSetting(11, 60);
        $this->seedRound([
            'id' => 1001,
            'market_id' => 11,
            'lotto_draw_id' => 5001,
            'status' => 'open',
            'bet_open_at' => '2026-05-02 10:00:00',
            'bet_close_at' => '2026-05-02 10:15:00',
            'shoot_open_at' => '2026-05-02 10:15:00',
            'shoot_close_at' => '2026-05-02 10:16:00',
        ]);

        DB::table('yeekee_shoots')->insert([
            ['yeekee_round_id' => 1001, 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['yeekee_round_id' => 1001, 'position' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $before = DB::table('yeekee_rounds')->where('id', 1001)->first();
        $exit = Artisan::call('lotto:yeekee:backfill-shooting-window');
        $after = DB::table('yeekee_rounds')->where('id', 1001)->first();

        $this->assertSame(0, $exit);
        $this->assertSame((string) $before->shoot_open_at, (string) $after->shoot_open_at);
        $this->assertSame((string) $before->shoot_close_at, (string) $after->shoot_close_at);
        $this->assertSame((int) $before->shoot_count, (int) $after->shoot_count);
        $this->assertSame((int) $before->last_shoot_position, (int) $after->last_shoot_position);
    }

    public function test_apply_updates_shoot_open_at_and_counters(): void
    {
        $this->seedMarket(11, 'yeekee');
        $this->seedMarketSetting(11, 45);
        $this->seedRound([
            'id' => 1002,
            'market_id' => 11,
            'lotto_draw_id' => 5002,
            'status' => 'open',
            'bet_open_at' => '2026-05-02 11:00:00',
            'bet_close_at' => '2026-05-02 11:15:00',
            'shoot_open_at' => '2026-05-02 11:15:00',
            'shoot_close_at' => '2026-05-02 11:16:00',
        ]);

        DB::table('yeekee_shoots')->insert([
            ['yeekee_round_id' => 1002, 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['yeekee_round_id' => 1002, 'position' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $exit = Artisan::call('lotto:yeekee:backfill-shooting-window', ['--apply' => true]);
        $round = DB::table('yeekee_rounds')->where('id', 1002)->first();

        $this->assertSame(0, $exit);
        $this->assertSame((string) $round->bet_open_at, (string) $round->shoot_open_at);
        $this->assertSame('2026-05-02 11:15:45', (string) $round->shoot_close_at);
        $this->assertSame(2, (int) $round->shoot_count);
        $this->assertSame(4, (int) $round->last_shoot_position);
    }

    public function test_apply_skips_frozen_final_and_snapshot_rounds(): void
    {
        $this->seedMarket(11, 'yeekee');
        $this->seedMarketSetting(11, 30);
        $base = [
            'market_id' => 11,
            'bet_open_at' => '2026-05-02 12:00:00',
            'bet_close_at' => '2026-05-02 12:15:00',
            'shoot_open_at' => '2026-05-02 12:15:00',
            'shoot_close_at' => '2026-05-02 12:16:00',
        ];

        $this->seedRound(array_merge($base, ['id' => 1003, 'lotto_draw_id' => 5003, 'status' => 'resulted']));
        $this->seedRound(array_merge($base, ['id' => 1004, 'lotto_draw_id' => 5004, 'status' => 'open', 'shoot_closed_at' => '2026-05-02 12:20:00']));
        $this->seedRound(array_merge($base, ['id' => 1005, 'lotto_draw_id' => 5005, 'status' => 'open', 'shoot_snapshot_hash' => 'abc123']));

        $before1003 = DB::table('yeekee_rounds')->where('id', 1003)->first();
        $before1004 = DB::table('yeekee_rounds')->where('id', 1004)->first();
        $before1005 = DB::table('yeekee_rounds')->where('id', 1005)->first();

        $exit = Artisan::call('lotto:yeekee:backfill-shooting-window', ['--apply' => true]);

        $after1003 = DB::table('yeekee_rounds')->where('id', 1003)->first();
        $after1004 = DB::table('yeekee_rounds')->where('id', 1004)->first();
        $after1005 = DB::table('yeekee_rounds')->where('id', 1005)->first();

        $this->assertSame(0, $exit);
        $this->assertSame((string) $before1003->shoot_open_at, (string) $after1003->shoot_open_at);
        $this->assertSame((string) $before1004->shoot_open_at, (string) $after1004->shoot_open_at);
        $this->assertSame((string) $before1005->shoot_open_at, (string) $after1005->shoot_open_at);
    }

    public function test_apply_is_idempotent(): void
    {
        $this->seedMarket(11, 'yeekee');
        $this->seedMarketSetting(11, 15);
        $this->seedRound([
            'id' => 1006,
            'market_id' => 11,
            'lotto_draw_id' => 5006,
            'status' => 'open',
            'bet_open_at' => '2026-05-02 13:00:00',
            'bet_close_at' => '2026-05-02 13:15:00',
            'shoot_open_at' => '2026-05-02 13:15:00',
            'shoot_close_at' => '2026-05-02 13:16:00',
        ]);

        DB::table('yeekee_shoots')->insert([
            ['yeekee_round_id' => 1006, 'position' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $firstExit = Artisan::call('lotto:yeekee:backfill-shooting-window', ['--apply' => true]);
        $first = DB::table('yeekee_rounds')->where('id', 1006)->first();

        $secondExit = Artisan::call('lotto:yeekee:backfill-shooting-window', ['--apply' => true]);
        $second = DB::table('yeekee_rounds')->where('id', 1006)->first();

        $this->assertSame(0, $firstExit);
        $this->assertSame(0, $secondExit);
        $this->assertSame((string) $first->shoot_open_at, (string) $second->shoot_open_at);
        $this->assertSame((string) $first->shoot_close_at, (string) $second->shoot_close_at);
        $this->assertSame((int) $first->shoot_count, (int) $second->shoot_count);
        $this->assertSame((int) $first->last_shoot_position, (int) $second->last_shoot_position);
    }

    public function test_apply_uses_zero_when_setting_is_null_or_missing(): void
    {
        $this->seedMarket(21, 'yeekee');
        $this->seedMarket(22, 'yeekee');
        $this->seedMarketSetting(21, null);
        // market 22 intentionally has no yeekee_market_settings row

        $this->seedRound([
            'id' => 2001,
            'market_id' => 21,
            'lotto_draw_id' => 6001,
            'status' => 'open',
            'bet_open_at' => '2026-05-02 14:00:00',
            'bet_close_at' => '2026-05-02 14:15:00',
            'shoot_open_at' => '2026-05-02 14:15:00',
            'shoot_close_at' => '2026-05-02 14:16:00',
        ]);
        $this->seedRound([
            'id' => 2002,
            'market_id' => 22,
            'lotto_draw_id' => 6002,
            'status' => 'open',
            'bet_open_at' => '2026-05-02 15:00:00',
            'bet_close_at' => '2026-05-02 15:15:00',
            'shoot_open_at' => '2026-05-02 15:15:00',
            'shoot_close_at' => '2026-05-02 15:16:00',
        ]);

        $exit = Artisan::call('lotto:yeekee:backfill-shooting-window', ['--apply' => true]);
        $roundA = DB::table('yeekee_rounds')->where('id', 2001)->first();
        $roundB = DB::table('yeekee_rounds')->where('id', 2002)->first();

        $this->assertSame(0, $exit);
        $this->assertSame((string) $roundA->bet_close_at, (string) $roundA->shoot_close_at);
        $this->assertSame((string) $roundB->bet_close_at, (string) $roundB->shoot_close_at);
    }

    private function seedMarket(int $id, string $resultMode): void
    {
        DB::table('lotto_markets')->insert([
            'id' => $id,
            'result_mode' => $resultMode,
        ]);
    }

    private function seedMarketSetting(int $marketId, ?int $shootWindowSeconds): void
    {
        DB::table('yeekee_market_settings')->insert([
            'market_id' => $marketId,
            'round_config' => json_encode([
                'shoot_window_after_bet_close_seconds' => $shootWindowSeconds,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedRound(array $payload): void
    {
        DB::table('yeekee_rounds')->insert(array_merge([
            'status' => 'open',
            'shoot_closed_at' => null,
            'shoot_snapshot_json' => null,
            'shoot_snapshot_hash' => null,
            'shoot_count' => 0,
            'last_shoot_position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $payload));
    }

    private function prepareSchema(): void
    {
        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->string('result_mode', 32)->default('normal');
        });

        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->string('status', 32)->default('open');
            $table->dateTime('bet_open_at')->nullable();
            $table->dateTime('bet_close_at')->nullable();
            $table->dateTime('shoot_open_at')->nullable();
            $table->dateTime('shoot_close_at')->nullable();
            $table->dateTime('shoot_closed_at')->nullable();
            $table->json('shoot_snapshot_json')->nullable();
            $table->string('shoot_snapshot_hash', 64)->nullable();
            $table->unsignedInteger('shoot_count')->default(0);
            $table->unsignedInteger('last_shoot_position')->default(0);
            $table->timestamps();
        });

        Schema::create('yeekee_market_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->json('round_config')->nullable();
            $table->timestamps();
        });

        Schema::create('yeekee_shoots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedInteger('position');
            $table->timestamps();
        });
    }
}
