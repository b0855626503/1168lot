<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Console\Commands\FetchLegacyArchiveResultsCommand;
use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Gametech\Lotto\Repositories\LegacyArchiveResultRepository;
use Gametech\Lotto\Services\LegacyArchiveSourceClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class LegacyArchiveSourceClientTest extends TestCase
{
    private string $baseUrl = 'https://legacy-api.example.com';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('lotto.legacy_archive.base_url', $this->baseUrl);

        Schema::dropIfExists('lotto_result_archive_legacy_results');

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
            $table->index(['type', 'request_date'], 'lrlar_type_date');
            $table->index('request_date', 'lrlar_request_date');
            $table->index(['lottos_name', 'request_date'], 'lrlar_name_date');
            $table->index('fetched_at', 'lrlar_fetched_at');
            $table->index('fetch_status', 'lrlar_fetch_status');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_result_archive_legacy_results');

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function test_happy_path_maps_and_upserts_rows_correctly(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response([
                'type' => 'egx30',
                'nameTH' => 'หุ้นอียิปต์',
                'date' => '2026-04-22',
                'page' => 1,
                'results' => [
                    [
                        'id' => 116506,
                        'lottosName' => 'egx30',
                        'lottosTH' => 'อียิปต์',
                        'lottosDate' => '2026-04-21T17:00:00.000Z',
                        'lottosTime' => '20:00',
                        'lottosNumber' => '239',
                        'lottosUnder' => '95',
                    ],
                ],
            ], 200),
        ]);

        $client = new LegacyArchiveSourceClient;
        $repository = new LegacyArchiveResultRepository;

        $rows = $client->fetch('egx30', '2026-04-22');

        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('egx30', $row['type']);
        $this->assertSame('หุ้นอียิปต์', $row['name_th']);
        $this->assertSame('2026-04-22', $row['request_date']);
        $this->assertSame(1, $row['page']);
        $this->assertSame(116506, $row['source_result_id']);
        $this->assertSame('egx30', $row['lottos_name']);
        $this->assertSame('อียิปต์', $row['lottos_th']);
        $this->assertSame('2026-04-21T17:00:00.000Z', $row['lottos_date_raw']);
        $this->assertNotNull($row['lottos_date']);
        $this->assertSame('20:00', $row['lottos_time']);
        $this->assertSame('239', $row['lottos_number']);
        $this->assertSame('95', $row['lottos_under']);
        $this->assertSame('success', $row['fetch_status']);
        $this->assertIsArray($row['payload_json']);
        $this->assertSame(116506, $row['payload_json']['id']);

        // Upsert and confirm row in DB
        $repository->upsert($row);

        $this->assertSame(1, LottoResultArchiveLegacyResult::count());

        $saved = LottoResultArchiveLegacyResult::first();
        $this->assertSame('egx30', $saved->type);
        $this->assertSame('239', $saved->lottos_number);
        $this->assertSame('95', $saved->lottos_under);
        $this->assertSame('success', $saved->fetch_status);
        $this->assertSame(116506, (int) $saved->source_result_id);
    }

    public function test_happy_path_url_contains_type_and_date(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response([
                'type' => 'egx30',
                'nameTH' => '',
                'date' => '2026-04-22',
                'page' => 1,
                'results' => [
                    [
                        'id' => 1,
                        'lottosName' => 'egx30',
                        'lottosTH' => '',
                        'lottosDate' => '2026-04-21T17:00:00.000Z',
                        'lottosTime' => '20:00',
                        'lottosNumber' => '100',
                        'lottosUnder' => '50',
                    ],
                ],
            ], 200),
        ]);

        $client = new LegacyArchiveSourceClient;
        $rows = $client->fetch('egx30', '2026-04-22');

        $this->assertSame('success', $rows[0]['fetch_status']);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'type=egx30')
                && str_contains($request->url(), 'date=2026-04-22');
        });
    }

    // -------------------------------------------------------------------------
    // 404 not_found path
    // -------------------------------------------------------------------------

    public function test_404_returns_not_found_sentinel_row(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response('Not Found', 404),
        ]);

        $client = new LegacyArchiveSourceClient;
        $rows = $client->fetch('egx30', '2026-04-22');

        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('not_found', $row['fetch_status']);
        $this->assertSame('egx30', $row['type']);
        $this->assertSame('2026-04-22', $row['request_date']);
        $this->assertNull($row['lottos_number']);
        $this->assertNull($row['last_error']);
    }

    public function test_not_found_row_can_be_upserted(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response('Not Found', 404),
        ]);

        $client = new LegacyArchiveSourceClient;
        $repository = new LegacyArchiveResultRepository;

        $rows = $client->fetch('egx30', '2026-04-22');
        $repository->upsert($rows[0]);

        $this->assertSame(1, LottoResultArchiveLegacyResult::count());
        $saved = LottoResultArchiveLegacyResult::first();
        $this->assertSame('not_found', $saved->fetch_status);
    }

    // -------------------------------------------------------------------------
    // HTTP failure path
    // -------------------------------------------------------------------------

    public function test_http_500_returns_failed_sentinel_row(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response('Internal Server Error', 500),
        ]);

        $client = new LegacyArchiveSourceClient;
        $rows = $client->fetch('egx30', '2026-04-22');

        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('failed', $row['fetch_status']);
        $this->assertNotNull($row['last_error']);
        $this->assertStringContainsString('500', $row['last_error']);
    }

    public function test_connection_exception_returns_failed_sentinel_row(): void
    {
        Http::fake([
            $this->baseUrl.'*' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $client = new LegacyArchiveSourceClient;
        $rows = $client->fetch('egx30', '2026-04-22');

        $this->assertCount(1, $rows);
        $this->assertSame('failed', $rows[0]['fetch_status']);
        $this->assertStringContainsString('Connection refused', (string) $rows[0]['last_error']);
    }

    // -------------------------------------------------------------------------
    // Cache version bump after successful upsert
    // -------------------------------------------------------------------------

    public function test_cache_version_bumped_after_successful_upsert_via_command(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response([
                'type' => 'egx30',
                'nameTH' => 'หุ้นอียิปต์',
                'date' => '2026-04-22',
                'page' => 1,
                'results' => [
                    [
                        'id' => 999,
                        'lottosName' => 'egx30',
                        'lottosTH' => 'อียิปต์',
                        'lottosDate' => '2026-04-21T17:00:00.000Z',
                        'lottosTime' => '20:00',
                        'lottosNumber' => '555',
                        'lottosUnder' => '22',
                    ],
                ],
            ], 200),
        ]);

        Cache::forget('lotto:archive:egx30:version');

        $command = new FetchLegacyArchiveResultsCommand;
        $command->setLaravel($this->app);

        $command->run(
            new ArrayInput([
                '--from' => '2026-04-22',
                '--to' => '2026-04-22',
                '--type' => 'egx30',
            ], $command->getDefinition()),
            new NullOutput
        );

        $version = Cache::get('lotto:archive:egx30:version');
        $this->assertNotNull($version);
        $this->assertGreaterThanOrEqual(1, (int) $version);
    }

    public function test_cache_version_not_bumped_for_not_found_row(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response('Not Found', 404),
        ]);

        Cache::forget('lotto:archive:egx30:version');

        $command = new FetchLegacyArchiveResultsCommand;
        $command->setLaravel($this->app);

        $command->run(
            new ArrayInput([
                '--from' => '2026-04-22',
                '--to' => '2026-04-22',
                '--type' => 'egx30',
            ], $command->getDefinition()),
            new NullOutput
        );

        $this->assertNull(Cache::get('lotto:archive:egx30:version'));
    }

    public function test_cache_not_bumped_when_protection_rule_blocks_not_found_overwrite(): void
    {
        // The 404 sentinel from LegacyArchiveSourceClient has source_result_id=null,
        // lottos_name='egx30', lottos_date_raw=null → key = sha256('egx30|2026-04-22|egx30|')
        // Pre-seed a success row with that exact key so the protection rule fires.
        $existingKey = hash('sha256', 'egx30|2026-04-22|egx30|');
        LottoResultArchiveLegacyResult::create([
            'type' => 'egx30',
            'lottos_name' => 'egx30',
            'request_date' => '2026-04-22',
            'fetch_status' => 'success',
            'unique_key' => $existingKey,
        ]);

        Http::fake([
            $this->baseUrl.'*' => Http::response('Not Found', 404),
        ]);

        Cache::forget('lotto:archive:egx30:version');

        $command = new FetchLegacyArchiveResultsCommand;
        $command->setLaravel($this->app);

        $command->run(
            new ArrayInput([
                '--from' => '2026-04-22',
                '--to' => '2026-04-22',
                '--type' => 'egx30',
            ], $command->getDefinition()),
            new NullOutput
        );

        $this->assertNull(Cache::get('lotto:archive:egx30:version'));
    }

    public function test_non_json_200_response_returns_failed_sentinel(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response('<html>error</html>', 200),
        ]);

        $client = new LegacyArchiveSourceClient;
        $rows = $client->fetch('egx30', '2026-04-22');

        $this->assertCount(1, $rows);
        $this->assertSame('failed', $rows[0]['fetch_status']);
        $this->assertStringContainsString('Non-JSON', (string) $rows[0]['last_error']);
    }

    public function test_non_array_results_field_returns_failed_sentinel(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response([
                'type' => 'egx30',
                'date' => '2026-04-22',
                'results' => 'pending',
            ], 200),
        ]);

        $client = new LegacyArchiveSourceClient;
        $rows = $client->fetch('egx30', '2026-04-22');

        $this->assertCount(1, $rows);
        $this->assertSame('failed', $rows[0]['fetch_status']);
        $this->assertStringContainsString('non-array', strtolower((string) $rows[0]['last_error']));
    }

    // -------------------------------------------------------------------------
    // Multiple result items in one response
    // -------------------------------------------------------------------------

    public function test_multiple_result_items_produce_multiple_rows(): void
    {
        Http::fake([
            $this->baseUrl.'*' => Http::response([
                'type' => 'hanoi',
                'nameTH' => 'หวยฮานอย',
                'date' => '2026-04-22',
                'page' => 1,
                'results' => [
                    [
                        'id' => 101,
                        'lottosName' => 'hanoi',
                        'lottosTH' => 'ฮานอย',
                        'lottosDate' => '2026-04-21T12:00:00.000Z',
                        'lottosTime' => '15:00',
                        'lottosNumber' => '111',
                        'lottosUnder' => '11',
                    ],
                    [
                        'id' => 102,
                        'lottosName' => 'hanoi',
                        'lottosTH' => 'ฮานอย',
                        'lottosDate' => '2026-04-21T15:00:00.000Z',
                        'lottosTime' => '18:00',
                        'lottosNumber' => '222',
                        'lottosUnder' => '22',
                    ],
                ],
            ], 200),
        ]);

        $client = new LegacyArchiveSourceClient;
        $repository = new LegacyArchiveResultRepository;

        $rows = $client->fetch('hanoi', '2026-04-22');

        $this->assertCount(2, $rows);

        foreach ($rows as $row) {
            $repository->upsert($row);
        }

        $this->assertSame(2, LottoResultArchiveLegacyResult::count());
    }
}
