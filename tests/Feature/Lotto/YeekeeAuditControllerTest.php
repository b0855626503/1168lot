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

    public function test_load_rounds_returns_403_without_view_permission(): void
    {
        $this->mockBouncer(['lotto.yeekee.audit.view' => false]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        $request = Request::create('/admin/lotto/yeekee/audit/rounds', 'GET');
        app(YeekeeAuditController::class)->loadRounds($request);
    }

    public function test_load_rounds_returns_empty_when_no_rounds(): void
    {
        $this->mockBouncer(['lotto.yeekee.audit.view' => true]);

        $request = Request::create('/admin/lotto/yeekee/audit/rounds', 'GET');

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->loadRounds($request)
        );

        $response->assertOk();
        $response->assertJsonPath('rounds', []);
    }

    public function test_load_rounds_returns_rounds_with_has_snapshot_flag(): void
    {
        $this->mockBouncer(['lotto.yeekee.audit.view' => true]);

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

        $request = Request::create('/admin/lotto/yeekee/audit/rounds', 'GET');

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

    public function test_load_rounds_filters_non_yeekee_markets_out(): void
    {
        $this->mockBouncer(['lotto.yeekee.audit.view' => true]);

        DB::table('lotto_markets')->insert([
            ['id' => 1, 'name' => 'Yeekee Market', 'result_mode' => 'yeekee'],
            ['id' => 2, 'name' => 'Normal Market', 'result_mode' => 'normal'],
        ]);

        DB::table('yeekee_rounds')->insert([
            ['id' => 31, 'market_id' => 1, 'lotto_draw_id' => 300, 'round_date' => '2026-05-02', 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 32, 'market_id' => 2, 'lotto_draw_id' => 301, 'round_date' => '2026-05-02', 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $request = Request::create('/admin/lotto/yeekee/audit/rounds', 'GET');
        $response = $this->createTestResponse(app(YeekeeAuditController::class)->loadRounds($request));

        $response->assertOk();
        $roundIds = collect($response->json('rounds'))->pluck('id')->all();
        $this->assertSame([31], array_values($roundIds));
    }

    public function test_load_rounds_filters_by_lotto_draw_id(): void
    {
        $this->mockBouncer(['lotto.yeekee.audit.view' => true]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'M', 'result_mode' => 'yeekee']);

        DB::table('yeekee_rounds')->insert([
            ['id' => 20, 'market_id' => 1, 'lotto_draw_id' => 100, 'round_date' => '2026-05-02', 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'market_id' => 1, 'lotto_draw_id' => 200, 'round_date' => '2026-05-02', 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $request = Request::create('/admin/lotto/yeekee/audit/rounds?lotto_draw_id=100', 'GET', ['lotto_draw_id' => '100']);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->loadRounds($request)
        );

        $response->assertOk();
        $rounds = $response->json('rounds');
        $this->assertCount(1, $rounds);
        $this->assertSame(20, $rounds[0]['id']);
    }

    public function test_load_rounds_returns_empty_for_zero_draw_id(): void
    {
        $this->mockBouncer(['lotto.yeekee.audit.view' => true]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'M', 'result_mode' => 'yeekee']);
        DB::table('yeekee_rounds')->insert([
            'id' => 40, 'market_id' => 1, 'lotto_draw_id' => 0, 'round_date' => '2026-05-02', 'round_no' => 1, 'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $request = Request::create('/admin/lotto/yeekee/audit/rounds?lotto_draw_id=0', 'GET', ['lotto_draw_id' => '0']);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->loadRounds($request)
        );

        $response->assertOk();
        $response->assertJsonPath('rounds', []);
    }

    public function test_load_rounds_returns_empty_for_negative_draw_id(): void
    {
        $this->mockBouncer(['lotto.yeekee.audit.view' => true]);

        $request = Request::create('/admin/lotto/yeekee/audit/rounds?lotto_draw_id=-5', 'GET', ['lotto_draw_id' => '-5']);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->loadRounds($request)
        );

        $response->assertOk();
        $response->assertJsonPath('rounds', []);
    }

    // --- show (single-round audit) ---

    public function test_show_returns_403_without_view_permission(): void
    {
        $this->mockBouncer(['lotto.yeekee.audit.view' => false]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        app(YeekeeAuditController::class)->show(99);
    }

    public function test_show_returns_masked_shoots_without_sensitive_permission(): void
    {
        $this->mockBouncer([
            'lotto.yeekee.audit.view' => true,
            'lotto.yeekee.audit.view_sensitive' => false,
        ]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'Yeekee 80', 'result_mode' => 'yeekee']);
        DB::table('yeekee_rounds')->insert([
            'id' => 50,
            'market_id' => 1,
            'lotto_draw_id' => 10,
            'round_date' => '2026-05-02',
            'round_no' => 1,
            'status' => 'resulted',
            'shoot_count' => 2,
            'last_shoot_position' => 2,
            'shoot_snapshot_json' => json_encode(['version' => 1]),
            'shoot_snapshot_hash' => 'testhash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            ['id' => 1, 'yeekee_round_id' => 50, 'lotto_draw_id' => 10, 'market_id' => 1, 'member_id' => 9001, 'position' => 1, 'number_text' => '12345', 'number_value' => 12345, 'submitted_at' => '2026-05-02 10:00:00', 'ip_address' => '1.2.3.4', 'user_agent' => 'Mozilla', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'yeekee_round_id' => 50, 'lotto_draw_id' => 10, 'market_id' => 1, 'member_id' => 9002, 'position' => 2, 'number_text' => '67890', 'number_value' => 67890, 'submitted_at' => '2026-05-02 10:01:00', 'ip_address' => '5.6.7.8', 'user_agent' => 'Chrome', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->show(50)
        );

        $response->assertOk();

        // number_text must be masked
        $shoots = $response->json('shoots');
        $this->assertCount(2, $shoots);
        $this->assertSame('123**', $shoots[0]['number_text']);
        $this->assertSame('678**', $shoots[1]['number_text']);

        // sensitive fields must not appear
        $this->assertArrayNotHasKey('member_id', $shoots[0]);
        $this->assertArrayNotHasKey('ip_address', $shoots[0]);
        $this->assertArrayNotHasKey('user_agent', $shoots[0]);

        // snapshot not returned without sensitive permission
        $this->assertArrayNotHasKey('snapshot', $response->json());

        // sensitivity hint must be present
        $this->assertSame('lotto.yeekee.audit.view_sensitive', $response->json('_sensitive_permission_required'));
    }

    public function test_show_returns_full_data_with_sensitive_permission(): void
    {
        $this->mockBouncer([
            'lotto.yeekee.audit.view' => true,
            'lotto.yeekee.audit.view_sensitive' => true,
        ]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'Yeekee 80', 'result_mode' => 'yeekee']);

        $snapshotData = ['version' => 1, 'round_id' => 60, 'shoots' => []];

        DB::table('yeekee_rounds')->insert([
            'id' => 60,
            'market_id' => 1,
            'lotto_draw_id' => 20,
            'round_date' => '2026-05-02',
            'round_no' => 3,
            'status' => 'resulted',
            'shoot_count' => 1,
            'last_shoot_position' => 1,
            'shoot_snapshot_json' => json_encode($snapshotData),
            'shoot_snapshot_hash' => hash('sha256', json_encode($snapshotData)),
            'shoot_closed_at' => '2026-05-02 09:05:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            'id' => 10, 'yeekee_round_id' => 60, 'lotto_draw_id' => 20, 'market_id' => 1, 'member_id' => 9001, 'position' => 1, 'number_text' => '45', 'number_value' => 45, 'submitted_at' => '2026-05-02 09:00:00', 'ip_address' => '10.0.0.1', 'user_agent' => 'TestAgent', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->show(60)
        );

        $response->assertOk();

        $shoots = $response->json('shoots');
        $this->assertCount(1, $shoots);

        // full number_text
        $this->assertSame('45', $shoots[0]['number_text']);

        // sensitive fields present
        $this->assertSame(9001, $shoots[0]['member_id']);
        $this->assertSame('10.0.0.1', $shoots[0]['ip_address']);
        $this->assertSame('TestAgent', $shoots[0]['user_agent']);

        // snapshot present
        $this->assertTrue($response->json('has_snapshot'));
        $this->assertNotEmpty($response->json('snapshot'));
        $this->assertNotEmpty($response->json('snapshot_hash'));

        // no sensitivity warning
        $this->assertNull($response->json('_sensitive_permission_required'));
    }

    public function test_show_returns_has_snapshot_false_when_no_snapshot(): void
    {
        $this->mockBouncer([
            'lotto.yeekee.audit.view' => true,
            'lotto.yeekee.audit.view_sensitive' => true,
        ]);

        DB::table('lotto_markets')->insert(['id' => 1, 'name' => 'M', 'result_mode' => 'yeekee']);
        DB::table('yeekee_rounds')->insert([
            'id' => 70, 'market_id' => 1, 'lotto_draw_id' => 1, 'round_no' => 1, 'status' => 'open',
            'shoot_snapshot_json' => null, 'shoot_snapshot_hash' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->createTestResponse(
            app(YeekeeAuditController::class)->show(70)
        );

        $response->assertOk();
        $this->assertFalse($response->json('has_snapshot'));
        $this->assertArrayNotHasKey('snapshot', $response->json());
    }

    public function test_show_returns_404_when_round_market_is_not_yeekee(): void
    {
        $this->mockBouncer([
            'lotto.yeekee.audit.view' => true,
            'lotto.yeekee.audit.view_sensitive' => true,
        ]);

        DB::table('lotto_markets')->insert(['id' => 3, 'name' => 'Normal', 'result_mode' => 'normal']);
        DB::table('yeekee_rounds')->insert([
            'id' => 88, 'market_id' => 3, 'lotto_draw_id' => 1, 'round_no' => 1, 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Yeekee round not found');

        app(YeekeeAuditController::class)->show(88);
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
