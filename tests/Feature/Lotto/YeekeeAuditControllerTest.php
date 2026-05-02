<?php

namespace Tests\Feature\Lotto;

use Gametech\Admin\Bouncer;
use Gametech\Lotto\Http\Controllers\Admin\YeekeeAuditController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class YeekeeAuditControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('result_mode', 32)->default('normal');
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
        });

        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('lotto_draw_id')->default(0);
            $table->date('round_date')->nullable();
            $table->unsignedInteger('round_no')->default(1);
            $table->string('status', 32)->default('open');
            $table->unsignedInteger('shoot_count')->default(0);
            $table->unsignedInteger('last_shoot_position')->default(0);
            $table->dateTime('shoot_open_at')->nullable();
            $table->dateTime('shoot_close_at')->nullable();
            $table->dateTime('shoot_closed_at')->nullable();
            $table->json('shoot_snapshot_json')->nullable();
            $table->string('shoot_snapshot_hash', 64)->nullable();
            $table->json('config_snapshot_json')->nullable();
            $table->timestamps();
        });

        Schema::create('yeekee_shoots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedBigInteger('lotto_draw_id')->default(0);
            $table->unsignedBigInteger('market_id')->default(0);
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->string('number_text', 10);
            $table->unsignedInteger('number_value')->default(0);
            $table->dateTime('submitted_at')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yeekee_shoots');
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('lotto_markets');

        parent::tearDown();
    }

    // --- loadRounds ---

    public function test_load_rounds_returns_403_without_index_permission(): void
    {
        $this->mockBouncer(['lotto_settings.yeekee_audit.index' => false]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        $request = Request::create('/admin/lotto/yeekee-audit/rounds', 'GET');
        app(YeekeeAuditController::class)->loadRounds($request);
    }

    public function test_load_rounds_returns_empty_list_when_no_rounds(): void
    {
        $this->mockBouncer(['lotto_settings.yeekee_audit.index' => true]);

        $request = Request::create('/admin/lotto/yeekee-audit/rounds', 'GET');

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->loadRounds($request)
        );

        $response->assertOk();
        $response->assertJsonPath('rounds', []);
    }

    public function test_load_rounds_returns_rounds_with_has_snapshot_flag(): void
    {
        $this->mockBouncer(['lotto_settings.yeekee_audit.index' => true]);

        DB::table('lotto_markets')->insert([
            'id' => 1, 'name' => 'Yeekee 80', 'result_mode' => 'yeekee',
        ]);

        DB::table('yeekee_rounds')->insert([
            'id' => 10,
            'market_id' => 1,
            'lotto_draw_id' => 5,
            'round_date' => '2026-05-02',
            'round_no' => 1,
            'status' => 'resulted',
            'shoot_count' => 3,
            'last_shoot_position' => 3,
            'shoot_snapshot_hash' => 'abc123',
            'shoot_snapshot_json' => json_encode(['version' => 1, 'shoots' => []]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_rounds')->insert([
            'id' => 11,
            'market_id' => 1,
            'lotto_draw_id' => 6,
            'round_date' => '2026-05-02',
            'round_no' => 2,
            'status' => 'open',
            'shoot_count' => 0,
            'last_shoot_position' => 0,
            'shoot_snapshot_hash' => null,
            'shoot_snapshot_json' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admin/lotto/yeekee-audit/rounds', 'GET');

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->loadRounds($request)
        );

        $response->assertOk();
        $rounds = $response->json('rounds');
        $this->assertCount(2, $rounds);

        $resulted = collect($rounds)->firstWhere('id', 10);
        $this->assertTrue($resulted['has_snapshot']);

        $open = collect($rounds)->firstWhere('id', 11);
        $this->assertFalse($open['has_snapshot']);
    }

    public function test_load_rounds_filters_by_market_id(): void
    {
        $this->mockBouncer(['lotto_settings.yeekee_audit.index' => true]);

        DB::table('lotto_markets')->insert([
            ['id' => 1, 'name' => 'Market A', 'result_mode' => 'yeekee'],
            ['id' => 2, 'name' => 'Market B', 'result_mode' => 'yeekee'],
        ]);

        DB::table('yeekee_rounds')->insert([
            ['id' => 20, 'market_id' => 1, 'lotto_draw_id' => 1, 'round_date' => '2026-05-02', 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'market_id' => 2, 'lotto_draw_id' => 2, 'round_date' => '2026-05-02', 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $request = Request::create('/admin/lotto/yeekee-audit/rounds?market_id=1', 'GET', ['market_id' => '1']);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->loadRounds($request)
        );

        $response->assertOk();
        $rounds = $response->json('rounds');
        $this->assertCount(1, $rounds);
        $this->assertSame(20, $rounds[0]['id']);
    }

    public function test_load_rounds_filters_by_round_date(): void
    {
        $this->mockBouncer(['lotto_settings.yeekee_audit.index' => true]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'M', 'result_mode' => 'yeekee']);

        DB::table('yeekee_rounds')->insert([
            ['id' => 30, 'market_id' => 1, 'lotto_draw_id' => 1, 'round_date' => '2026-05-01', 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'market_id' => 1, 'lotto_draw_id' => 2, 'round_date' => '2026-05-02', 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $request = Request::create('/admin/lotto/yeekee-audit/rounds?round_date=2026-05-02', 'GET', ['round_date' => '2026-05-02']);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->loadRounds($request)
        );

        $response->assertOk();
        $rounds = $response->json('rounds');
        $this->assertCount(1, $rounds);
        $this->assertSame(31, $rounds[0]['id']);
    }

    // --- loadShoots ---

    public function test_load_shoots_returns_403_without_index_permission(): void
    {
        $this->mockBouncer(['lotto_settings.yeekee_audit.index' => false]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        $request = Request::create('/admin/lotto/yeekee-audit/rounds/50/shoots', 'GET');
        app(YeekeeAuditController::class)->loadShoots($request, 50);
    }

    public function test_load_shoots_returns_403_without_view_shoots_permission(): void
    {
        $this->mockBouncer([
            'lotto_settings.yeekee_audit.index' => true,
            'lotto_settings.yeekee_audit.view_shoots' => false,
        ]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'M', 'result_mode' => 'yeekee']);
        DB::table('yeekee_rounds')->insert(['id' => 51, 'market_id' => 1, 'lotto_draw_id' => 1, 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        $request = Request::create('/admin/lotto/yeekee-audit/rounds/51/shoots', 'GET');
        app(YeekeeAuditController::class)->loadShoots($request, 51);
    }

    public function test_load_shoots_returns_shoots_with_permission(): void
    {
        $this->mockBouncer([
            'lotto_settings.yeekee_audit.index' => true,
            'lotto_settings.yeekee_audit.view_shoots' => true,
        ]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'Yeekee 80', 'result_mode' => 'yeekee']);
        DB::table('yeekee_rounds')->insert([
            'id' => 60,
            'market_id' => 1,
            'lotto_draw_id' => 1,
            'round_date' => '2026-05-02',
            'round_no' => 3,
            'status' => 'resulted',
            'shoot_count' => 2,
            'last_shoot_position' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            ['id' => 100, 'yeekee_round_id' => 60, 'lotto_draw_id' => 1, 'market_id' => 1, 'member_id' => 9001, 'position' => 1, 'number_text' => '23', 'number_value' => 23, 'submitted_at' => '2026-05-02 10:00:00', 'ip_address' => '1.2.3.4', 'user_agent' => 'Mozilla', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 101, 'yeekee_round_id' => 60, 'lotto_draw_id' => 1, 'market_id' => 1, 'member_id' => 9002, 'position' => 2, 'number_text' => '77', 'number_value' => 77, 'submitted_at' => '2026-05-02 10:01:00', 'ip_address' => '5.6.7.8', 'user_agent' => 'Chrome', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $request = Request::create('/admin/lotto/yeekee-audit/rounds/60/shoots', 'GET');

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->loadShoots($request, 60)
        );

        $response->assertOk();
        $shoots = $response->json('shoots');
        $this->assertCount(2, $shoots);
        $this->assertSame('23', $shoots[0]['number_text']);
        $this->assertSame(1, $shoots[0]['position']);
        $this->assertSame(9001, $shoots[0]['member_id']);

        // sensitive fields must not be exposed
        $this->assertArrayNotHasKey('ip_address', $shoots[0]);
        $this->assertArrayNotHasKey('user_agent', $shoots[0]);
        $this->assertArrayNotHasKey('metadata_json', $shoots[0]);
    }

    // --- showSnapshot ---

    public function test_show_snapshot_returns_403_without_index_permission(): void
    {
        $this->mockBouncer(['lotto_settings.yeekee_audit.index' => false]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        app(YeekeeAuditController::class)->showSnapshot(70);
    }

    public function test_show_snapshot_returns_403_without_view_snapshot_permission(): void
    {
        $this->mockBouncer([
            'lotto_settings.yeekee_audit.index' => true,
            'lotto_settings.yeekee_audit.view_snapshot' => false,
        ]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'M', 'result_mode' => 'yeekee']);
        DB::table('yeekee_rounds')->insert(['id' => 71, 'market_id' => 1, 'lotto_draw_id' => 1, 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        app(YeekeeAuditController::class)->showSnapshot(71);
    }

    public function test_show_snapshot_returns_has_snapshot_false_when_no_snapshot(): void
    {
        $this->mockBouncer([
            'lotto_settings.yeekee_audit.index' => true,
            'lotto_settings.yeekee_audit.view_snapshot' => true,
        ]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'M', 'result_mode' => 'yeekee']);
        DB::table('yeekee_rounds')->insert([
            'id' => 80,
            'market_id' => 1,
            'lotto_draw_id' => 1,
            'round_no' => 1,
            'status' => 'open',
            'shoot_snapshot_json' => null,
            'shoot_snapshot_hash' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->showSnapshot(80)
        );

        $response->assertOk();
        $response->assertJsonPath('has_snapshot', false);
        $response->assertJsonPath('round_id', 80);
    }

    public function test_show_snapshot_returns_full_snapshot_with_permission(): void
    {
        $this->mockBouncer([
            'lotto_settings.yeekee_audit.index' => true,
            'lotto_settings.yeekee_audit.view_snapshot' => true,
        ]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'Yeekee 80', 'result_mode' => 'yeekee']);

        $snapshotData = [
            'version' => 1,
            'round_id' => 90,
            'lotto_draw_id' => 10,
            'market_id' => 1,
            'round_no' => 5,
            'round_date' => '2026-05-02',
            'shoot_open_at' => '2026-05-02 09:00:00',
            'shoot_close_at' => '2026-05-02 09:05:00',
            'shoot_closed_at' => '2026-05-02 09:05:02',
            'shoot_count' => 2,
            'last_shoot_position' => 2,
            'shoots' => [
                ['position' => 1, 'number_value' => 23],
                ['position' => 2, 'number_value' => 77],
            ],
        ];

        DB::table('yeekee_rounds')->insert([
            'id' => 90,
            'market_id' => 1,
            'lotto_draw_id' => 10,
            'round_date' => '2026-05-02',
            'round_no' => 5,
            'status' => 'resulted',
            'shoot_count' => 2,
            'last_shoot_position' => 2,
            'shoot_snapshot_json' => json_encode($snapshotData),
            'shoot_snapshot_hash' => hash('sha256', json_encode($snapshotData)),
            'shoot_closed_at' => '2026-05-02 09:05:02',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->showSnapshot(90)
        );

        $response->assertOk();
        $response->assertJsonPath('has_snapshot', true);
        $this->assertNotEmpty($response->json('snapshot_hash'));
        $this->assertNotEmpty($response->json('snapshot'));
        $this->assertSame(2, $response->json('snapshot.shoot_count'));
    }

    /**
     * @param  array<string, bool>  $permissions
     */
    private function mockBouncer(array $permissions): void
    {
        $bouncer = new class($permissions)
        {
            /** @param array<string, bool> $permissions */
            public function __construct(private array $permissions) {}

            public function hasPermission(string $key): bool
            {
                return $this->permissions[$key] ?? false;
            }
        };

        $this->app->instance(Bouncer::class, $bouncer);
    }
}
