<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GenerateAutoLottoDrawsScheduleConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('app.timezone', 'Asia/Bangkok');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createBaseSchema();
    }

    public function test_backfill_maps_legacy_draw_modes_and_is_idempotent(): void
    {
        $groupId = $this->insertGroup(true);

        $manualId = $this->insertMarket($groupId, [
            'name' => 'manual',
            'draw_mode' => 'manual',
            'draw_schedule_type' => null,
            'draw_days' => null,
            'draw_dates' => null,
        ]);
        $dailyId = $this->insertMarket($groupId, [
            'name' => 'daily',
            'draw_mode' => 'daily',
            'draw_schedule_type' => null,
            'draw_days' => null,
            'draw_dates' => null,
        ]);
        $weekdaysId = $this->insertMarket($groupId, [
            'name' => 'weekdays',
            'draw_mode' => 'weekdays',
            'draw_schedule_type' => null,
            'draw_days' => null,
            'draw_dates' => null,
        ]);
        $wedSatSunId = $this->insertMarket($groupId, [
            'name' => 'wed_sat_sun',
            'draw_mode' => 'wed_sat_sun',
            'draw_schedule_type' => null,
            'draw_days' => null,
            'draw_dates' => null,
        ]);

        $migration = require base_path('packages/Gametech/Lotto/src/Database/Migrations/2026_04_24_200100_backfill_draw_schedule_config_from_draw_mode.php');
        $migration->up();
        $migration->up();

        $manual = DB::table('lotto_markets')->where('id', $manualId)->first();
        $daily = DB::table('lotto_markets')->where('id', $dailyId)->first();
        $weekdays = DB::table('lotto_markets')->where('id', $weekdaysId)->first();
        $wedSatSun = DB::table('lotto_markets')->where('id', $wedSatSunId)->first();

        $this->assertSame('manual', (string) $manual->draw_schedule_type);
        $this->assertSame([], $this->decodeJsonArray($manual->draw_days));
        $this->assertSame([], $this->decodeJsonArray($manual->draw_dates));

        $this->assertSame('weekly', (string) $daily->draw_schedule_type);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], $this->decodeJsonArray($daily->draw_days));
        $this->assertSame([], $this->decodeJsonArray($daily->draw_dates));

        $this->assertSame('weekly', (string) $weekdays->draw_schedule_type);
        $this->assertSame([1, 2, 3, 4, 5], $this->decodeJsonArray($weekdays->draw_days));
        $this->assertSame([], $this->decodeJsonArray($weekdays->draw_dates));

        $this->assertSame('weekly', (string) $wedSatSun->draw_schedule_type);
        $this->assertSame([3, 6, 7], $this->decodeJsonArray($wedSatSun->draw_days));
        $this->assertSame([], $this->decodeJsonArray($wedSatSun->draw_dates));
    }

    public function test_weekly_schedule_and_duplicate_protection_work(): void
    {
        $groupId = $this->insertGroup(true);
        $marketId = $this->insertMarket($groupId, [
            'name' => 'weekly-mwf',
            'draw_schedule_type' => 'weekly',
            'draw_days' => [1, 3, 5],
            'draw_dates' => [],
            'draw_mode' => 'manual',
        ]);

        $firstRun = $this->runGenerateAuto(['--date' => '2026-04-27', '--days' => 3]);
        $this->assertSame(0, $firstRun['exit_code']);
        $this->assertSame(2, DB::table('lotto_draws')->where('market_id', $marketId)->count());

        $dates = DB::table('lotto_draws')
            ->where('market_id', $marketId)
            ->orderBy('draw_date')
            ->pluck('draw_date')
            ->map(static fn ($date) => substr((string) $date, 0, 10))
            ->values()
            ->all();

        $this->assertSame(['2026-04-27', '2026-04-29'], $dates);

        $secondRun = $this->runGenerateAuto(['--date' => '2026-04-27', '--days' => 3]);
        $this->assertSame(0, $secondRun['exit_code']);
        $this->assertSame(2, DB::table('lotto_draws')->where('market_id', $marketId)->count());
        $this->assertSame(2, (int) ($secondRun['summary']['exists'] ?? 0));
    }

    public function test_monthly_schedule_generates_selected_days_and_skips_invalid_calendar_date(): void
    {
        $groupId = $this->insertGroup(true);
        $marketId = $this->insertMarket($groupId, [
            'name' => 'monthly-1-16-31',
            'draw_schedule_type' => 'monthly',
            'draw_days' => [],
            'draw_dates' => [1, 16, 31],
            'draw_mode' => 'manual',
        ]);

        $result = $this->runGenerateAuto(['--date' => '2026-04-01', '--days' => 30]);
        $this->assertSame(0, $result['exit_code']);

        $dates = DB::table('lotto_draws')
            ->where('market_id', $marketId)
            ->orderBy('draw_date')
            ->pluck('draw_date')
            ->map(static fn ($date) => substr((string) $date, 0, 10))
            ->values()
            ->all();

        $this->assertSame(['2026-04-01', '2026-04-16'], $dates);
        $this->assertSame(2, (int) ($result['summary']['created'] ?? 0));
    }

    public function test_manual_schedule_never_auto_generates(): void
    {
        $groupId = $this->insertGroup(true);
        $marketId = $this->insertMarket($groupId, [
            'name' => 'manual-market',
            'draw_schedule_type' => 'manual',
            'draw_days' => [],
            'draw_dates' => [],
            'draw_mode' => 'manual',
        ]);

        $result = $this->runGenerateAuto(['--date' => '2026-04-27', '--days' => 2]);

        $this->assertSame(0, $result['exit_code']);
        $this->assertSame(0, DB::table('lotto_draws')->where('market_id', $marketId)->count());
        $statuses = collect($result['summary']['items'] ?? [])->pluck('status')->unique()->values()->all();
        $this->assertSame(['skip_manual'], $statuses);
    }

    public function test_runtime_skips_invalid_new_config_without_falling_back_to_draw_mode(): void
    {
        $groupId = $this->insertGroup(true);
        $marketId = $this->insertMarket($groupId, [
            'name' => 'invalid-weekly',
            'draw_schedule_type' => 'weekly',
            'draw_days' => [],
            'draw_dates' => [],
            'draw_mode' => 'daily',
        ]);

        $result = $this->runGenerateAuto(['--date' => '2026-04-27', '--days' => 1]);

        $this->assertSame(0, $result['exit_code']);
        $this->assertSame(0, DB::table('lotto_draws')->where('market_id', $marketId)->count());
        $this->assertSame('skip_invalid_schedule_config', collect($result['summary']['items'] ?? [])->first()['status'] ?? null);
    }

    public function test_fallback_to_legacy_draw_mode_when_new_schedule_is_missing(): void
    {
        $groupId = $this->insertGroup(true);
        $marketId = $this->insertMarket($groupId, [
            'name' => 'legacy-daily',
            'draw_schedule_type' => null,
            'draw_days' => null,
            'draw_dates' => null,
            'draw_mode' => 'daily',
        ]);

        $result = $this->runGenerateAuto(['--date' => '2026-04-27', '--days' => 2]);

        $this->assertSame(0, $result['exit_code']);
        $this->assertSame(2, DB::table('lotto_draws')->where('market_id', $marketId)->count());
    }

    public function test_disabled_market_and_group_are_skipped_and_dry_run_does_not_write(): void
    {
        $enabledGroupId = $this->insertGroup(true);
        $disabledGroupId = $this->insertGroup(false);

        $enabledMarketId = $this->insertMarket($enabledGroupId, [
            'name' => 'enabled-market',
            'draw_schedule_type' => 'weekly',
            'draw_days' => [1],
            'draw_dates' => [],
            'draw_mode' => 'manual',
            'is_enabled' => 1,
        ]);
        $disabledMarketId = $this->insertMarket($enabledGroupId, [
            'name' => 'disabled-market',
            'draw_schedule_type' => 'weekly',
            'draw_days' => [1],
            'draw_dates' => [],
            'draw_mode' => 'manual',
            'is_enabled' => 0,
        ]);
        $disabledGroupMarketId = $this->insertMarket($disabledGroupId, [
            'name' => 'disabled-group-market',
            'draw_schedule_type' => 'weekly',
            'draw_days' => [1],
            'draw_dates' => [],
            'draw_mode' => 'manual',
            'is_enabled' => 1,
        ]);

        $dryRun = $this->runGenerateAuto(['--date' => '2026-04-27', '--days' => 1, '--dry-run' => true]);
        $this->assertSame(0, $dryRun['exit_code']);
        $this->assertSame(0, DB::table('lotto_draws')->count());

        $actualRun = $this->runGenerateAuto(['--date' => '2026-04-27', '--days' => 1]);
        $this->assertSame(0, $actualRun['exit_code']);
        $this->assertSame(1, DB::table('lotto_draws')->where('market_id', $enabledMarketId)->count());
        $this->assertSame(0, DB::table('lotto_draws')->where('market_id', $disabledMarketId)->count());
        $this->assertSame(0, DB::table('lotto_draws')->where('market_id', $disabledGroupMarketId)->count());

        $statuses = collect($actualRun['summary']['items'] ?? [])->pluck('status')->all();
        $this->assertContains('skip_group_disabled', $statuses);
    }

    private function createBaseSchema(): void
    {
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('logs');

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('draw_mode', 20)->default('manual');
            $table->string('draw_schedule_type', 20)->nullable();
            $table->json('draw_days')->nullable();
            $table->json('draw_dates')->nullable();
            $table->time('auto_open_time')->nullable();
            $table->time('auto_close_time')->nullable();
            $table->time('auto_result_time')->nullable();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date');
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('emp_code')->nullable();
            $table->string('mode')->nullable();
            $table->string('menu')->nullable();
            $table->unsignedBigInteger('record')->nullable();
            $table->text('item_before')->nullable();
            $table->text('item')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_create')->nullable();
            $table->dateTime('date_update')->nullable();
            $table->dateTime('date_create')->nullable();
        });
    }

    private function insertGroup(bool $enabled): int
    {
        return (int) DB::table('lotto_groups')->insertGetId([
            'name' => $enabled ? 'enabled-group' : 'disabled-group',
            'is_enabled' => $enabled ? 1 : 0,
        ]);
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function insertMarket(int $groupId, array $overrides = []): int
    {
        $defaults = [
            'group_id' => $groupId,
            'name' => 'market',
            'code' => 'market_'.uniqid('', true),
            'draw_mode' => 'manual',
            'draw_schedule_type' => 'manual',
            'draw_days' => json_encode([], JSON_UNESCAPED_UNICODE),
            'draw_dates' => json_encode([], JSON_UNESCAPED_UNICODE),
            'auto_open_time' => '10:00:00',
            'auto_close_time' => '12:00:00',
            'auto_result_time' => '13:00:00',
            'is_enabled' => 1,
        ];

        $payload = array_merge($defaults, $overrides);

        if (array_key_exists('draw_days', $payload) && is_array($payload['draw_days'])) {
            $payload['draw_days'] = json_encode($payload['draw_days'], JSON_UNESCAPED_UNICODE);
        }

        if (array_key_exists('draw_dates', $payload) && is_array($payload['draw_dates'])) {
            $payload['draw_dates'] = json_encode($payload['draw_dates'], JSON_UNESCAPED_UNICODE);
        }

        return (int) DB::table('lotto_markets')->insertGetId($payload);
    }

    /**
     * @param  array<string,mixed>  $options
     * @return array{exit_code:int,summary:array<string,mixed>}
     */
    private function runGenerateAuto(array $options): array
    {
        $exitCode = Artisan::call('lotto:generate-auto-draws', $options);
        $decoded = json_decode((string) Artisan::output(), true);

        return [
            'exit_code' => $exitCode,
            'summary' => is_array($decoded) ? $decoded : [],
        ];
    }

    /**
     * @return array<int,int>
     */
    private function decodeJsonArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_map('intval', $value));
        }

        $decoded = json_decode((string) $value, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map('intval', $decoded));
    }
}
