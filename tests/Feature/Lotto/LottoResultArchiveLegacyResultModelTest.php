<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoResultArchiveLegacyResultModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

            $table->string('unique_key');

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

    public function test_table_is_created(): void
    {
        $record = LottoResultArchiveLegacyResult::create([
            'type' => 'thai_gov',
            'name_th' => 'หวยรัฐบาล',
            'request_date' => '2026-05-01',
            'page' => 1,
            'source_result_id' => 98765,
            'lottos_name' => 'THAI_GOV',
            'lottos_th' => 'หวยรัฐบาล',
            'lottos_date' => '2026-05-01 13:30:00',
            'lottos_date_raw' => '01/05/2026',
            'lottos_time' => '13:30',
            'lottos_number' => '123456',
            'lottos_under' => '56',
            'market_code' => 'TH_GOV',
            'market_id' => 10,
            'source_url' => 'https://example.com/api/results',
            'fetched_at' => '2026-05-01 14:00:00',
            'fetch_status' => 'success',
            'checksum' => str_repeat('a', 64),
            'payload_json' => ['raw' => 'data'],
            'unique_key' => 'thai_gov_2026-05-01_98765',
        ]);

        $this->assertNotNull($record->id);

        $found = LottoResultArchiveLegacyResult::find($record->id);

        $this->assertNotNull($found);
        $this->assertSame('thai_gov', $found->type);
        $this->assertSame('THAI_GOV', $found->lottos_name);
        $this->assertSame('123456', $found->lottos_number);
        $this->assertSame('thai_gov_2026-05-01_98765', $found->unique_key);
    }

    public function test_casts_are_applied(): void
    {
        LottoResultArchiveLegacyResult::create([
            'type' => 'thai_gov',
            'lottos_name' => 'THAI_GOV',
            'request_date' => '2026-05-01',
            'lottos_date' => '2026-05-01 13:30:00',
            'fetched_at' => '2026-05-01 14:00:00',
            'payload_json' => ['key' => 'value', 'num' => 42],
            'fetch_status' => 'success',
            'unique_key' => 'thai_gov_2026-05-01_cast_test',
        ]);

        $record = LottoResultArchiveLegacyResult::where('unique_key', 'thai_gov_2026-05-01_cast_test')->firstOrFail();

        $this->assertInstanceOf(Carbon::class, $record->request_date);
        $this->assertSame('2026-05-01', $record->request_date->format('Y-m-d'));

        $this->assertInstanceOf(Carbon::class, $record->lottos_date);
        $this->assertSame('2026-05-01 13:30:00', $record->lottos_date->format('Y-m-d H:i:s'));

        $this->assertInstanceOf(Carbon::class, $record->fetched_at);
        $this->assertSame('2026-05-01 14:00:00', $record->fetched_at->format('Y-m-d H:i:s'));

        $this->assertIsArray($record->payload_json);
        $this->assertSame('value', $record->payload_json['key']);
        $this->assertSame(42, $record->payload_json['num']);
    }

    public function test_unique_key_prevents_duplicate(): void
    {
        $this->expectException(QueryException::class);

        $data = [
            'type' => 'thai_gov',
            'lottos_name' => 'THAI_GOV',
            'fetch_status' => 'success',
            'unique_key' => 'thai_gov_2026-05-01_dup_test',
        ];

        LottoResultArchiveLegacyResult::create($data);
        LottoResultArchiveLegacyResult::create($data);
    }
}
