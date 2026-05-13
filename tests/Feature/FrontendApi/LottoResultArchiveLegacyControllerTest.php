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

    public function test_single_date_returns_grouped_shape(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-04-22');

        $response->assertOk()
            ->assertJsonPath('draw_date', '2026-04-22')
            ->assertJsonPath('language', 'th')
            ->assertJsonPath('summary.group_count', 1)
            ->assertJsonPath('summary.market_count', 1)
            ->assertJsonPath('summary.result_count', 1);

        $market = $response->json('groups.0.markets.0');
        $this->assertNotNull($market);
        $this->assertEquals('test-legacy-morning', $market['market_code']);
        $this->assertEquals('หวยทดสอบเช้า', $market['market_name']);

        $result = $market['results'][0];
        $this->assertEquals('test-legacy-morning', $result['lottosName']);
        $this->assertEquals('หวยทดสอบเช้า', $result['lottosTH']);
        $this->assertEquals(100, $result['id']);
        $this->assertEquals('785', $result['lottosNumber']);
        $this->assertEquals('71', $result['lottosUnder']);
        $this->assertEquals('22/04/2569', $result['lottosDate']);
        $this->assertEquals('15:00', $result['lottosTime']);
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
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-yeekee&date=2026-04-22');
        $response->assertOk()
            ->assertJsonPath('summary.group_count', 0)
            ->assertJsonPath('summary.result_count', 0);
    }

    public function test_snapshot_only_type_returns_empty_groups_when_no_market(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=nonexistent&date=2026-04-22');
        $response->assertOk()
            ->assertJsonPath('draw_date', '2026-04-22')
            ->assertJsonPath('summary.result_count', 0)
            ->assertJsonPath('groups', []);
    }

    public function test_snapshot_only_type_with_market_lookup_shows_in_unknown_group(): void
    {
        // Seed a snapshot-only type that has NO matching lotto_markets entry.
        // It should appear in the fallback "unknown" group (group_id=0).
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
            ->assertJsonPath('summary.result_count', 1);

        $market = $response->json('groups.0.markets.0');
        $this->assertEquals('snapshot-only', $market['market_code']);
        $this->assertEquals('555', $market['results'][0]['lottosNumber']);
    }

    public function test_preserves_leading_zero(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-afternoon&date=2026-04-22');
        $result = $response->json('groups.0.markets.0.results.0');
        $this->assertEquals('014', $result['lottosNumber']);
        $this->assertEquals('07', $result['lottosUnder']);
    }

    public function test_fallback_id_uses_row_pk_when_no_source_result_id(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-afternoon&date=2026-04-22');
        $result = $response->json('groups.0.markets.0.results.0');
        $this->assertEquals(2, $result['id']);
        $this->assertIsInt($result['id']);
    }

    public function test_lottos_date_uses_raw_string(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-04-22');
        $result = $response->json('groups.0.markets.0.results.0');
        $this->assertEquals('22/04/2569', $result['lottosDate']);
    }

    public function test_not_found_returns_empty_groups(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-01-01');
        $response->assertOk()
            ->assertJsonPath('groups', [])
            ->assertJsonPath('summary.result_count', 0);
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
            ->assertJsonPath('summary.result_count', 1)
            ->assertJsonPath('draw_date', '2026-04-05');

        $market = $response->json('groups.0.markets.0');
        $this->assertEquals('xsthm', $market['market_code']);
        $this->assertEquals('123', $market['results'][0]['lottosNumber']);
    }

    public function test_canonical_type_query_still_works_unchanged(): void
    {
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
            ->assertJsonPath('summary.result_count', 1);

        $result = $response->json('groups.0.markets.0.results.0');
        $this->assertEquals('456', $result['lottosNumber']);
        $this->assertEquals('xsthm', $response->json('groups.0.markets.0.market_code'));
    }

    public function test_unknown_type_passes_through_unchanged(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=totally-unknown-type&date=2026-04-22');

        $response->assertOk()
            ->assertJsonPath('groups', [])
            ->assertJsonPath('summary.result_count', 0);
    }

    public function test_sentinel_rows_not_returned_by_api(): void
    {
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

        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-04-22');
        $response->assertOk()
            ->assertJsonPath('summary.result_count', 1);
        $this->assertEquals('785', $response->json('groups.0.markets.0.results.0.lottosNumber'));

        $response2 = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy?type=test-legacy-morning&date=2026-04-23');
        $response2->assertOk()
            ->assertJsonPath('groups', [])
            ->assertJsonPath('summary.result_count', 0);
    }

    // ── /by-date endpoint tests ───────────────────────────────────────────

    public function test_by_date_returns_send_response_wrapper(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/by-date?date=2026-04-22');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ดึงผลรางวัลตามวันที่สำเร็จ');
    }

    public function test_by_date_requires_valid_date(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/by-date')
            ->assertStatus(422);
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/by-date?date=invalid')
            ->assertStatus(422);
    }

    public function test_by_date_returns_grouped_shape_matching_results_by_date(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/by-date?date=2026-04-22');

        $response->assertOk()
            ->assertJsonPath('data.draw_date', '2026-04-22')
            ->assertJsonPath('data.summary.group_count', 1)
            ->assertJsonPath('data.summary.result_count', 2);

        // language may be th or en depending on middleware — just verify it exists
        $this->assertNotNull($response->json('data.language'));
        $this->assertContains($response->json('data.language'), ['th', 'en']);

        $group = $response->json('data.groups.0');
        $this->assertArrayHasKey('group_id', $group);
        $this->assertArrayHasKey('group_code', $group);
        $this->assertArrayHasKey('group_name', $group);
        $this->assertArrayHasKey('markets', $group);
        $this->assertIsArray($group['markets']);
        $this->assertGreaterThanOrEqual(1, count($group['markets']));

        $market = $group['markets'][0];
        $this->assertArrayHasKey('market_id', $market);
        $this->assertArrayHasKey('market_name', $market);
        $this->assertArrayHasKey('market_logo', $market);
        $this->assertArrayHasKey('market_icon', $market);
        $this->assertArrayHasKey('result', $market);
        $this->assertIsArray($market['result']);

        $result = $market['result'];
        $this->assertArrayHasKey('draw_id', $result);
        $this->assertArrayHasKey('draw_date', $result);
        $this->assertArrayHasKey('result_at', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('resulted', $result['status']);
        $this->assertArrayHasKey('result_number', $result);
        $this->assertIsArray($result['result_number']);
        $this->assertArrayHasKey('first_prize', $result);
        $this->assertArrayHasKey('last_2_digits', $result);
        $this->assertArrayHasKey('result_top_3', $result);
        $this->assertArrayHasKey('result_top_2', $result);
        $this->assertArrayHasKey('result_bottom_2', $result);
    }

    public function test_by_date_result_number_maps_snapshot_fields(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/by-date?date=2026-04-22');

        $groups = $response->json('data.groups');
        $allMarkets = [];
        foreach ($groups as $group) {
            foreach ($group['markets'] as $market) {
                $allMarkets[$market['market_name']] = $market;
            }
        }

        $this->assertArrayHasKey('หวยทดสอบเช้า', $allMarkets);
        $morning = $allMarkets['หวยทดสอบเช้า'];
        $result = $morning['result'];

        $this->assertEquals('785', $result['first_prize']);
        $this->assertEquals('71', $result['last_2_digits']);
        $this->assertEquals('71', $result['result_bottom_2']);
        $this->assertEquals('785', $result['result_top_3']); // last 3 chars of '785' = '785'
    }

    public function test_by_date_yeekee_excluded(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/by-date?date=2026-04-22');

        $response->assertOk();
        $groups = $response->json('data.groups');
        foreach ($groups as $group) {
            foreach ($group['markets'] as $market) {
                $this->assertNotEquals('test-legacy-yeekee', $market['market_code'] ?? '');
            }
        }
    }

    public function test_by_date_empty_date_returns_empty_groups(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/by-date?date=2026-01-01');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.groups', [])
            ->assertJsonPath('data.summary.result_count', 0);
    }

    // ── /markets/{marketId}/results endpoint tests ─────────────────────────

    public function test_market_results_returns_send_response_wrapper(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/markets/1/results');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ดึงผลย้อนหลังสำเร็จ');
    }

    public function test_market_results_unknown_market_returns_404(): void
    {
        $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/markets/9999/results')
            ->assertStatus(404);
    }

    public function test_market_results_returns_market_info(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/markets/1/results');

        $response->assertOk()
            ->assertJsonPath('data.market.id', 1)
            ->assertJsonPath('data.market.name', 'หวยทดสอบเช้า')
            ->assertJsonPath('data.market.group_id', 1)
            ->assertJsonPath('data.market.group_name', 'Test Group');
    }

    public function test_market_results_returns_history_and_pagination(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/markets/1/results');

        $response->assertOk()
            ->assertJsonPath('data.pagination.page', 1)
            ->assertJsonPath('data.pagination.limit', 20)
            ->assertJsonPath('data.pagination.count', 1)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_market_results_latest_and_history_match(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/markets/1/results');

        $response->assertOk();
        $latest = $response->json('data.latest_result');
        $this->assertNotNull($latest);
        $this->assertEquals('785', $latest['first_prize']);
        $this->assertEquals('71', $latest['last_2_digits']);
        $this->assertEquals('resulted', $latest['status']);

        $history = $response->json('data.history');
        $this->assertCount(1, $history);
        $this->assertEquals($latest['first_prize'], $history[0]['first_prize']);
    }

    public function test_market_results_pagination_limit_obeys_limits(): void
    {
        $response = $this->getJson('http://api.localhost/api/v1/lotto/result-archive-legacy/markets/1/results?limit=1&page=1');

        $response->assertOk()
            ->assertJsonPath('data.pagination.limit', 1);
    }
}
