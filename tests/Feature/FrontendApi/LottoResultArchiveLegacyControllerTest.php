<?php

namespace Tests\Feature\FrontendApi;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoResultArchiveLegacyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('lotto_result_archives');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('lotto_groups')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->string('result_mode', 32)->default('normal');
        });

        Schema::create('lotto_result_archives', function (Blueprint $table): void {
            $table->id();
            $table->string('market_code', 50);
            $table->date('draw_date');
            $table->string('draw_key', 50);
            $table->json('result_set');
            $table->string('result_hash', 64);
            $table->unsignedBigInteger('source_draw_id')->nullable();
            $table->string('source_type', 30)->default('internal_mirror');
            $table->unsignedInteger('correction_count')->default(0);
            $table->json('previous_result_set')->nullable();
            $table->json('source_info_json')->nullable();
            $table->dateTime('corrected_at')->nullable();
            $table->timestamps();
            $table->unique(['market_code', 'draw_date', 'draw_key']);
        });

        DB::table('lotto_groups')->insert(['id' => 1, 'name' => 'Test Group', 'code' => 'test-group']);

        $this->seedTestData();
    }

    protected function seedTestData(): void
    {
        DB::table('lotto_markets')->insert([
            [
                'id' => 1,
                'group_id' => 1,
                'name' => 'หวยทดสอบเช้า',
                'code' => 'test-legacy-morning',
                'is_enabled' => true,
                'result_mode' => 'normal',
            ],
            [
                'id' => 2,
                'group_id' => 1,
                'name' => 'หวยทดสอบบ่าย',
                'code' => 'test-legacy-afternoon',
                'is_enabled' => true,
                'result_mode' => 'normal',
            ],
            [
                'id' => 3,
                'group_id' => 1,
                'name' => 'หวยยี่กีทดสอบ',
                'code' => 'test-legacy-yeekee',
                'is_enabled' => true,
                'result_mode' => 'yeekee',
            ],
        ]);

        DB::table('lotto_result_archives')->insert([
            // Morning: three_up, two_up, two_down — source_draw_id = 100
            [
                'market_code' => 'test-legacy-morning',
                'draw_date' => '2026-04-22',
                'draw_key' => 'three_up',
                'result_set' => json_encode(['785']),
                'result_hash' => 'abc123',
                'source_draw_id' => 100,
                'source_type' => 'internal_mirror',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'market_code' => 'test-legacy-morning',
                'draw_date' => '2026-04-22',
                'draw_key' => 'two_up',
                'result_set' => json_encode(['85']),
                'result_hash' => 'abc456',
                'source_draw_id' => 100,
                'source_type' => 'internal_mirror',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'market_code' => 'test-legacy-morning',
                'draw_date' => '2026-04-22',
                'draw_key' => 'two_down',
                'result_set' => json_encode(['71']),
                'result_hash' => 'abc789',
                'source_draw_id' => 100,
                'source_type' => 'internal_mirror',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Afternoon: three_up + two_down — no source_draw_id (external)
            [
                'market_code' => 'test-legacy-afternoon',
                'draw_date' => '2026-04-22',
                'draw_key' => 'three_up',
                'result_set' => json_encode(['014']),
                'result_hash' => 'def123',
                'source_draw_id' => null,
                'source_type' => 'external_fetch',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'market_code' => 'test-legacy-afternoon',
                'draw_date' => '2026-04-22',
                'draw_key' => 'two_down',
                'result_set' => json_encode(['07']),
                'result_hash' => 'def456',
                'source_draw_id' => null,
                'source_type' => 'external_fetch',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Yeekee — must be excluded
            [
                'market_code' => 'test-legacy-yeekee',
                'draw_date' => '2026-04-22',
                'draw_key' => 'three_up',
                'result_set' => json_encode(['999']),
                'result_hash' => 'yee001',
                'source_draw_id' => 200,
                'source_type' => 'internal_mirror',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_type_and_date_returns_legacy_shape(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-04-22');

        $response->assertOk()
            ->assertJsonPath('type', 'test-legacy-morning')
            ->assertJsonPath('nameTH', 'หวยทดสอบเช้า')
            ->assertJsonPath('date', '2026-04-22')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('page', 1);

        $result = $response->json('results.0');

        $this->assertNotNull($result);
        $this->assertEquals('test-legacy-morning', $result['lottosName']);
        $this->assertEquals('หวยทดสอบเช้า', $result['lottosTH']);
        $this->assertEquals(100, $result['id']);
        $this->assertEquals('785', $result['lottosNumber']);
        $this->assertEquals('71', $result['lottosUnder']);

        // must NOT expose two_up
        $this->assertArrayNotHasKey('two_up', $result);
        // must NOT expose internals
        $this->assertArrayNotHasKey('source_info_json', $result);
        $this->assertArrayNotHasKey('result_hash', $result);
        $this->assertArrayNotHasKey('raw_payload', $result);
        $this->assertArrayNotHasKey('result_set', $result);
    }

    public function test_type_and_date_range(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&from_date=2026-04-01&to_date=2026-04-30');

        $response->assertOk()
            ->assertJsonPath('type', 'test-legacy-morning')
            ->assertJsonPath('date', '2026-04-01..2026-04-30')
            ->assertJsonPath('count', 1);
    }

    public function test_all_types_and_date_range(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?from_date=2026-04-01&to_date=2026-04-30&per_page=50');

        $response->assertOk()
            ->assertJsonPath('type', 'all')
            ->assertJsonPath('nameTH', 'ทั้งหมด')
            ->assertJsonPath('count', 2); // morning + afternoon, NOT yeekee
    }

    public function test_yeekee_market_excluded(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-yeekee&date=2026-04-22');

        $response->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_preserves_leading_zero(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-afternoon&date=2026-04-22');

        $result = $response->json('results.0');
        $this->assertEquals('014', $result['lottosNumber']);
        $this->assertEquals('07', $result['lottosUnder']);
    }

    public function test_deterministic_id_for_external_rows(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-afternoon&date=2026-04-22');

        $result = $response->json('results.0');
        $expectedId = (int) sprintf('%u', crc32('test-legacy-afternoon|2026-04-22'));
        $this->assertEquals($expectedId, $result['id']);
    }

    public function test_no_date_returns_422(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy')
            ->assertStatus(422);
    }

    public function test_date_mixed_with_range_returns_422(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test&date=2026-04-22&from_date=2026-04-01')
            ->assertStatus(422);
    }

    public function test_invalid_date_returns_422(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test&date=2026-99-99')
            ->assertStatus(422);
    }

    public function test_from_date_gt_to_date_returns_422(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?from_date=2026-05-01&to_date=2026-04-01')
            ->assertStatus(422);
    }

    public function test_per_page_gt_500_returns_422(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?date=2026-04-22&per_page=501')
            ->assertStatus(422);
    }

    public function test_from_date_without_to_date_returns_422(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?from_date=2026-04-01')
            ->assertStatus(422);
    }

    public function test_to_date_without_from_date_returns_422(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?to_date=2026-04-30')
            ->assertStatus(422);
    }
}
