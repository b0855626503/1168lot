<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\YeekeeShootService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class YeekeeShootServiceTest extends TestCase
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
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('yeekee_market_settings');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        parent::tearDown();
    }

    public function test_submit_shoot_creates_position_sequence(): void
    {
        config()->set('yeekee.shoot_enabled', true);
        config()->set('yeekee.shooting_enabled', true);
        config()->set('yeekee.shoot_cooldown_seconds', 0);
        $this->seedBasicYeekeeRound();
        $service = app(YeekeeShootService::class);

        $shootA = $service->submitShoot(1001, 201, '00001', '127.0.0.1', 'test');
        $shootB = $service->submitShoot(1002, 201, '99999', '127.0.0.1', 'test');

        $this->assertSame(1, (int) $shootA->position);
        $this->assertSame(2, (int) $shootB->position);
        $this->assertSame('00001', (string) $shootA->number_text);
        $this->assertSame(1, (int) $shootA->number_value);
        $this->assertSame(2, DB::table('yeekee_shoots')->count());
        $round = DB::table('yeekee_rounds')->where('id', 201)->first();
        $this->assertSame(2, (int) $round->last_shoot_position);
        $this->assertSame(2, (int) $round->shoot_count);
    }

    public function test_submit_shoot_rejects_invalid_number(): void
    {
        config()->set('yeekee.shoot_enabled', true);
        config()->set('yeekee.shooting_enabled', true);
        config()->set('yeekee.shoot_cooldown_seconds', 0);
        $this->seedBasicYeekeeRound();
        $service = app(YeekeeShootService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->submitShoot(1001, 201, '1234');
    }

    public function test_submit_shoot_rejects_member_cooldown(): void
    {
        config()->set('yeekee.shoot_enabled', true);
        config()->set('yeekee.shooting_enabled', true);
        config()->set('yeekee.shoot_cooldown_seconds', 6);
        $this->seedBasicYeekeeRound();
        $service = app(YeekeeShootService::class);

        $service->submitShoot(1001, 201, '12345', '127.0.0.1', 'test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('กรุณารอก่อนยิงเลขครั้งถัดไป');
        $service->submitShoot(1001, 201, '54321', '127.0.0.1', 'test');
    }

    public function test_submit_shoot_rejects_when_round_shoot_closed(): void
    {
        config()->set('yeekee.shoot_enabled', true);
        config()->set('yeekee.shooting_enabled', true);
        config()->set('yeekee.shoot_cooldown_seconds', 0);
        $this->seedBasicYeekeeRound([
            'shoot_closed_at' => now()->subSecond()->format('Y-m-d H:i:s'),
        ]);
        $service = app(YeekeeShootService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('รอบนี้ปิดรับยิงเลขแล้ว');
        $service->submitShoot(1001, 201, '12345', '127.0.0.1', 'test');
    }

    public function test_submit_shoot_rejects_when_legacy_flag_is_disabled(): void
    {
        config()->set('yeekee.shoot_enabled', false);
        config()->set('yeekee.shooting_enabled', true);
        $this->seedBasicYeekeeRound();
        $service = app(YeekeeShootService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ระบบยิงเลขถูกปิดใช้งานชั่วคราว');
        $service->submitShoot(1001, 201, '12345', '127.0.0.1', 'test');
    }

    public function test_submit_shoot_rejects_when_hardening_flag_is_disabled(): void
    {
        config()->set('yeekee.shoot_enabled', true);
        config()->set('yeekee.shooting_enabled', false);
        $this->seedBasicYeekeeRound();
        $service = app(YeekeeShootService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ระบบยิงเลขถูกปิดใช้งานชั่วคราว');
        $service->submitShoot(1001, 201, '12345', '127.0.0.1', 'test');
    }

    private function seedBasicYeekeeRound(array $overrides = []): void
    {
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'Main',
            'code' => 'main',
            'is_enabled' => 1,
            'sort' => 1,
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 11,
            'group_id' => 1,
            'name' => 'Yeekee Market',
            'code' => 'yeekee_market',
            'result_mode' => 'yeekee',
            'is_enabled' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 101,
            'market_id' => 11,
            'draw_date' => now()->toDateString(),
            'open_at' => now()->subMinutes(10)->format('Y-m-d H:i:s'),
            'close_at' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roundPayload = array_merge([
            'id' => 201,
            'market_id' => 11,
            'lotto_draw_id' => 101,
            'round_date' => now()->toDateString(),
            'round_no' => 1,
            'bet_open_at' => now()->subMinutes(20)->format('Y-m-d H:i:s'),
            'bet_close_at' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'shoot_open_at' => now()->subMinutes(1)->format('Y-m-d H:i:s'),
            'shoot_close_at' => now()->addMinutes(2)->format('Y-m-d H:i:s'),
            'result_compute_at' => now()->addMinutes(3)->format('Y-m-d H:i:s'),
            'expected_settlement_deadline_at' => now()->addMinutes(8)->format('Y-m-d H:i:s'),
            'status' => 'open',
            'last_shoot_position' => 0,
            'shoot_count' => 0,
            'config_snapshot_json' => null,
            'shoot_snapshot_json' => null,
            'shoot_snapshot_hash' => null,
            'shoot_closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        DB::table('yeekee_rounds')->insert($roundPayload);
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
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('yeekee_market_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->json('round_config')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date');
            $table->dateTime('open_at');
            $table->dateTime('close_at');
            $table->enum('status', ['draft', 'open', 'closed', 'resulted'])->default('draft');
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
            $table->unsignedInteger('last_shoot_position')->default(0);
            $table->unsignedInteger('shoot_count')->default(0);
            $table->json('config_snapshot_json')->nullable();
            $table->json('shoot_snapshot_json')->nullable();
            $table->string('shoot_snapshot_hash', 128)->nullable();
            $table->dateTime('shoot_closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('yeekee_shoots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->string('number_text', 5);
            $table->unsignedInteger('number_value');
            $table->dateTime('submitted_at');
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });
    }
}
