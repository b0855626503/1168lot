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

        Schema::dropIfExists('lotto_result_archive_legacy_results');
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

        Schema::create('lotto_result_archive_legacy_results', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 100);
            $table->string('name_th')->nullable();
            $table->date('request_date')->nullable();
            $table->unsignedInteger('page')->nullable();
            $table->unsignedBigInteger('source_result_id')->nullable();
            $table->string('lottos_name', 100);
            $table->string('lottos_th')->nullable();
            $table->dateTime('lottos_date')->nullable();
            $table->string('lottos_date_raw', 50)->nullable();
            $table->string('lottos_time', 20)->nullable();
            $table->string('lottos_number', 50)->nullable();
            $table->string('lottos_under', 50)->nullable();
            $table->string('market_code', 100)->nullable();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->text('source_url')->nullable();
            $table->dateTime('fetched_at')->nullable();
            $table->enum('fetch_status', ['success', 'not_found', 'failed'])->default('success');
            $table->text('last_error')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->json('payload_json')->nullable();
            $table->string('unique_key', 64);
            $table->timestamps();
            $table->unique('unique_key');
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

        DB::table('lotto_result_archive_legacy_results')->insert([
            [
                'type' => 'test-legacy-morning',
                'name_th' => 'หวยทดสอบเช้า',
                'request_date' => '2026-04-22',
                'page' => 1,
                'source_result_id' => 100,
                'lottos_name' => 'test-legacy-morning',
                'lottos_th' => 'หวยทดสอบเช้า',
                'lottos_date' => '2026-04-22 00:00:00',
                'lottos_date_raw' => '22/04/2569',
                'lottos_time' => '15:00',
                'lottos_number' => '785',
                'lottos_under' => '71',
                'market_code' => 'test-legacy-morning',
                'market_id' => 1,
                'source_url' => null,
                'fetched_at' => now(),
                'fetch_status' => 'success',
                'last_error' => null,
                'checksum' => null,
                'payload_json' => null,
                'unique_key' => hash('sha256', 'test-legacy-morning|2026-04-22|100'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'test-legacy-afternoon',
                'name_th' => 'หวยทดสอบบ่าย',
                'request_date' => '2026-04-22',
                'page' => 1,
                'source_result_id' => null,
                'lottos_name' => 'test-legacy-afternoon',
                'lottos_th' => 'หวยทดสอบบ่าย',
                'lottos_date' => '2026-04-22 00:00:00',
                'lottos_date_raw' => '22/04/2569',
                'lottos_time' => '16:30',
                'lottos_number' => '014',
                'lottos_under' => '07',
                'market_code' => 'test-legacy-afternoon',
                'market_id' => 2,
                'source_url' => null,
                'fetched_at' => now(),
                'fetch_status' => 'success',
                'last_error' => null,
                'checksum' => null,
                'payload_json' => null,
                'unique_key' => hash('sha256', 'test-legacy-afternoon|2026-04-22|ext'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'test-legacy-yeekee',
                'name_th' => 'หวยยี่กีทดสอบ',
                'request_date' => '2026-04-22',
                'page' => 1,
                'source_result_id' => 200,
                'lottos_name' => 'test-legacy-yeekee',
                'lottos_th' => 'หวยยี่กีทดสอบ',
                'lottos_date' => '2026-04-22 00:00:00',
                'lottos_date_raw' => '22/04/2569',
                'lottos_time' => '12:00',
                'lottos_number' => '999',
                'lottos_under' => '99',
                'market_code' => 'test-legacy-yeekee',
                'market_id' => 3,
                'source_url' => null,
                'fetched_at' => now(),
                'fetch_status' => 'success',
                'last_error' => null,
                'checksum' => null,
                'payload_json' => null,
                'unique_key' => hash('sha256', 'test-legacy-yeekee|2026-04-22|200'),
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
            ->assertJsonPath('page', 1)
            ->assertJsonPath('errors', []);

        $result = $response->json('results.0');
        $this->assertNotNull($result);
        $this->assertEquals('test-legacy-morning', $result['lottosName']);
        $this->assertEquals('หวยทดสอบเช้า', $result['lottosTH']);
        $this->assertEquals(100, $result['id']);
        $this->assertEquals('785', $result['lottosNumber']);
        $this->assertEquals('71', $result['lottosUnder']);
        $this->assertEquals('22/04/2569', $result['lottosDate']);
        $this->assertEquals('15:00', $result['lottosTime']);
        $this->assertArrayNotHasKey('source_result_id', $result);
        $this->assertArrayNotHasKey('source_info_json', $result);
        $this->assertArrayNotHasKey('result_hash', $result);
        $this->assertArrayNotHasKey('raw_payload', $result);
        $this->assertArrayNotHasKey('payload_json', $result);
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
            ->assertJsonPath('count', 2);
    }

    public function test_yeekee_market_excluded(): void
    {
        // Yeekee types are excluded at service level via whereNotIn(getYeekeeCodes()).
        // The controller no longer returns UNSUPPORTED_TYPE for unknown/yeekee types (BOA-262: market_id is optional).
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-yeekee&date=2026-04-22');
        $response->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('errors.0.code', 'DRAW_DATE_NOT_FOUND');
    }

    public function test_snapshot_only_type_returns_results_without_market_entry(): void
    {
        // Per BOA-262: market_id is optional — snapshot-only types are served without rejection.
        // An unknown type (not in lotto_markets) returns DRAW_DATE_NOT_FOUND when no snapshot rows exist.
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=nonexistent&date=2026-04-22');
        $response->assertOk()
            ->assertJsonPath('type', 'nonexistent')
            ->assertJsonPath('count', 0)
            ->assertJsonPath('results', [])
            ->assertJsonPath('errors.0.code', 'DRAW_DATE_NOT_FOUND');
    }

    public function test_name_th_from_snapshot_for_market_not_in_lotto_markets(): void
    {
        // Seed a snapshot-only type (not in lotto_markets) with a stored name_th.
        // Controller should read nameTH from snapshot's name_th, not from LotteryMarket.
        DB::table('lotto_result_archive_legacy_results')->insert([
            'type' => 'snapshot-only',
            'name_th' => 'หวย Snapshot Only',
            'request_date' => '2026-04-22',
            'page' => 1,
            'source_result_id' => 9999,
            'lottos_name' => 'snapshot-only',
            'lottos_th' => 'หวย Snapshot Only',
            'lottos_date' => '2026-04-22 00:00:00',
            'lottos_date_raw' => '22/04/2569',
            'lottos_time' => '10:00',
            'lottos_number' => '555',
            'lottos_under' => '55',
            'market_code' => null,
            'market_id' => null,
            'source_url' => null,
            'fetched_at' => now(),
            'fetch_status' => 'success',
            'last_error' => null,
            'checksum' => null,
            'payload_json' => null,
            'unique_key' => hash('sha256', 'snapshot-only|2026-04-22|9999'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=snapshot-only&date=2026-04-22');
        $response->assertOk()
            ->assertJsonPath('type', 'snapshot-only')
            ->assertJsonPath('nameTH', 'หวย Snapshot Only')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.lottosNumber', '555');
    }

    public function test_preserves_leading_zero(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-afternoon&date=2026-04-22');
        $result = $response->json('results.0');
        $this->assertEquals('014', $result['lottosNumber']);
        $this->assertEquals('07', $result['lottosUnder']);
    }

    public function test_fallback_id_uses_row_pk_when_no_source_result_id(): void
    {
        // When source_result_id is null, the response id falls back to the row's auto-increment PK.
        // This avoids crc32 collisions when lottos_date_raw is null or shared across multiple rows.
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-afternoon&date=2026-04-22');
        $result = $response->json('results.0');
        // The afternoon row is inserted second so it gets PK=2 in the test DB.
        $this->assertEquals(2, $result['id']);
        $this->assertIsInt($result['id']);
    }

    public function test_lottos_date_uses_raw_string(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-04-22');
        $result = $response->json('results.0');
        $this->assertEquals('22/04/2569', $result['lottosDate']);
    }

    public function test_not_found_returns_errors(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-01-01');
        $response->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('results', [])
            ->assertJsonPath('errors.0.code', 'DRAW_DATE_NOT_FOUND');
    }

    public function test_no_date_returns_422(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy')->assertStatus(422);
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

    public function test_all_types_pagination_counts_rows(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?from_date=2026-04-01&to_date=2026-04-30');
        $response->assertOk()->assertJsonPath('count', 2);
    }

    public function test_alias_type_normalized_to_canonical_before_query(): void
    {
        // Seed a row stored under canonical type 'xsthm' (as the fetch command would store it).
        // Request with alias 'hanoi-special' must normalize to 'xsthm' and return the row.
        DB::table('lotto_result_archive_legacy_results')->insert([
            'type' => 'xsthm',
            'name_th' => 'หวยฮานอยพิเศษ',
            'request_date' => '2026-04-05',
            'page' => 1,
            'source_result_id' => 5001,
            'lottos_name' => 'Hanoi Special',
            'lottos_th' => 'หวยฮานอยพิเศษ',
            'lottos_date' => '2026-04-05 00:00:00',
            'lottos_date_raw' => '05/04/2569',
            'lottos_time' => '18:30',
            'lottos_number' => '123',
            'lottos_under' => '45',
            'market_code' => null,
            'market_id' => null,
            'source_url' => null,
            'fetched_at' => now(),
            'fetch_status' => 'success',
            'last_error' => null,
            'checksum' => null,
            'payload_json' => null,
            'unique_key' => hash('sha256', 'xsthm|2026-04-05|5001'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=hanoi-special&date=2026-04-05');

        $response->assertOk()
            ->assertJsonPath('type', 'xsthm')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.lottosNumber', '123')
            ->assertJsonPath('errors', []);
    }

    public function test_canonical_type_query_still_works_unchanged(): void
    {
        // Querying with the canonical type directly must still work (not double-normalized).
        DB::table('lotto_result_archive_legacy_results')->insert([
            'type' => 'xsthm',
            'name_th' => 'หวยฮานอยพิเศษ',
            'request_date' => '2026-04-06',
            'page' => 1,
            'source_result_id' => 5002,
            'lottos_name' => 'Hanoi Special',
            'lottos_th' => 'หวยฮานอยพิเศษ',
            'lottos_date' => '2026-04-06 00:00:00',
            'lottos_date_raw' => '06/04/2569',
            'lottos_time' => '18:30',
            'lottos_number' => '456',
            'lottos_under' => '78',
            'market_code' => null,
            'market_id' => null,
            'source_url' => null,
            'fetched_at' => now(),
            'fetch_status' => 'success',
            'last_error' => null,
            'checksum' => null,
            'payload_json' => null,
            'unique_key' => hash('sha256', 'xsthm|2026-04-06|5002'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=xsthm&date=2026-04-06');

        $response->assertOk()
            ->assertJsonPath('type', 'xsthm')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.lottosNumber', '456');
    }

    public function test_unknown_type_passes_through_unchanged(): void
    {
        // A type with no mapping in the registry must pass through without modification.
        // It will return no results (no rows for it), but must not be rejected or altered.
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=totally-unknown-type&date=2026-04-22');

        $response->assertOk()
            ->assertJsonPath('type', 'totally-unknown-type')
            ->assertJsonPath('count', 0)
            ->assertJsonPath('errors.0.code', 'DRAW_DATE_NOT_FOUND');
    }

    public function test_sentinel_rows_not_returned_by_api(): void
    {
        // Insert a failed and a not_found sentinel row for the same date as success rows.
        // The service must filter fetch_status='success' only — sentinel rows must not appear in response.
        DB::table('lotto_result_archive_legacy_results')->insert([
            [
                'type' => 'test-legacy-morning',
                'name_th' => null,
                'request_date' => '2026-04-22',
                'page' => null,
                'source_result_id' => null,
                'lottos_name' => 'test-legacy-morning',
                'lottos_th' => null,
                'lottos_date' => null,
                'lottos_date_raw' => null,
                'lottos_time' => null,
                'lottos_number' => null,
                'lottos_under' => null,
                'market_code' => null,
                'market_id' => null,
                'source_url' => null,
                'fetched_at' => now(),
                'fetch_status' => 'failed',
                'last_error' => 'Connection timeout',
                'checksum' => null,
                'payload_json' => null,
                'unique_key' => hash('sha256', 'test-legacy-morning|2026-04-22|sentinel-failed'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'test-legacy-morning',
                'name_th' => null,
                'request_date' => '2026-04-23',
                'page' => null,
                'source_result_id' => null,
                'lottos_name' => 'test-legacy-morning',
                'lottos_th' => null,
                'lottos_date' => null,
                'lottos_date_raw' => null,
                'lottos_time' => null,
                'lottos_number' => null,
                'lottos_under' => null,
                'market_code' => null,
                'market_id' => null,
                'source_url' => null,
                'fetched_at' => now(),
                'fetch_status' => 'not_found',
                'last_error' => null,
                'checksum' => null,
                'payload_json' => null,
                'unique_key' => hash('sha256', 'test-legacy-morning|2026-04-23|sentinel-not-found'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Querying the date that has both a success row and a failed sentinel for same type:
        // only the success row should appear.
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-04-22');
        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.lottosNumber', '785');

        // Date with only a not_found sentinel should return 0 results.
        $response2 = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-04-23');
        $response2->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('errors.0.code', 'DRAW_DATE_NOT_FOUND');
    }
}
