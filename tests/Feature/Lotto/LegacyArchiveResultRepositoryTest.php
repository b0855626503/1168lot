<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Gametech\Lotto\Repositories\LegacyArchiveResultRepository;
use Gametech\Lotto\Repositories\LegacyArchiveUpsertResult;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyArchiveResultRepositoryTest extends TestCase
{
    protected LegacyArchiveResultRepository $repository;

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

            $table->string('unique_key', 64);

            $table->timestamps();

            $table->unique('unique_key');
            $table->index(['type', 'request_date'], 'lrlar_type_date');
            $table->index('request_date', 'lrlar_request_date');
            $table->index(['lottos_name', 'request_date'], 'lrlar_name_date');
            $table->index('fetched_at', 'lrlar_fetched_at');
            $table->index('fetch_status', 'lrlar_fetch_status');
        });

        $this->repository = new LegacyArchiveResultRepository;
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_result_archive_legacy_results');

        parent::tearDown();
    }

    public function test_upsert_returns_model(): void
    {
        $data = [
            'type' => 'egx30',
            'lottos_name' => 'EGX30',
            'request_date' => '2026-01-15',
            'source_result_id' => 12345,
            'fetch_status' => 'success',
            'lottos_number' => '100',
        ];

        $model = $this->repository->upsert($data);

        $this->assertInstanceOf(LottoResultArchiveLegacyResult::class, $model);
        $this->assertNotNull($model->id);
        $this->assertSame('egx30', $model->type);
        $this->assertSame('EGX30', $model->lottos_name);
        $this->assertSame(1, LottoResultArchiveLegacyResult::count());
    }

    public function test_upsert_with_result_creates_new_row(): void
    {
        $data = [
            'type' => 'egx30',
            'lottos_name' => 'EGX30',
            'request_date' => '2026-01-15',
            'source_result_id' => 12345,
            'fetch_status' => 'success',
            'lottos_number' => '100',
        ];

        $result = $this->repository->upsertWithResult($data);

        $this->assertInstanceOf(LegacyArchiveUpsertResult::class, $result);
        $this->assertNotNull($result->model->id);
        $this->assertSame('egx30', $result->model->type);
        $this->assertSame('EGX30', $result->model->lottos_name);
        $this->assertTrue($result->wasCreated);
        $this->assertFalse($result->wasUpdated);
        $this->assertFalse($result->wasSkipped);
        $this->assertSame(1, LottoResultArchiveLegacyResult::count());
    }

    public function test_upsert_is_idempotent(): void
    {
        $data = [
            'type' => 'egx30',
            'lottos_name' => 'EGX30',
            'request_date' => '2026-01-15',
            'source_result_id' => 12345,
            'fetch_status' => 'success',
            'lottos_number' => '100',
        ];

        $first = $this->repository->upsertWithResult($data);
        $second = $this->repository->upsertWithResult($data);

        $this->assertSame($first->model->id, $second->model->id);
        $this->assertFalse($second->wasUpdated);
        $this->assertSame(1, LottoResultArchiveLegacyResult::count());
    }

    public function test_success_row_not_overwritten_by_failed_without_force(): void
    {
        $successData = [
            'type' => 'egx30',
            'lottos_name' => 'EGX30',
            'request_date' => '2026-01-15',
            'source_result_id' => 12345,
            'fetch_status' => 'success',
            'lottos_number' => '999',
        ];

        $this->repository->upsertWithResult($successData);

        $failedData = array_merge($successData, [
            'fetch_status' => 'failed',
            'last_error' => 'Network timeout',
            'lottos_number' => null,
        ]);

        $result = $this->repository->upsertWithResult($failedData, false);

        $this->assertSame('success', $result->model->fetch_status);
        $this->assertSame('999', $result->model->lottos_number);
        $this->assertTrue($result->wasSkipped);
        $this->assertFalse($result->wasWritten());
        $this->assertSame(1, LottoResultArchiveLegacyResult::count());
    }

    public function test_success_row_not_overwritten_by_not_found_without_force(): void
    {
        $successData = [
            'type' => 'hanoi',
            'lottos_name' => 'HANOI',
            'request_date' => '2026-02-10',
            'source_result_id' => 77777,
            'fetch_status' => 'success',
            'lottos_number' => '456',
        ];

        $this->repository->upsertWithResult($successData);

        $notFoundData = array_merge($successData, [
            'fetch_status' => 'not_found',
            'lottos_number' => null,
        ]);

        $result = $this->repository->upsertWithResult($notFoundData, false);

        $this->assertSame('success', $result->model->fetch_status);
        $this->assertSame('456', $result->model->lottos_number);
        $this->assertTrue($result->wasSkipped);
        $this->assertSame(1, LottoResultArchiveLegacyResult::count());
    }

    public function test_force_flag_overwrites_success_row(): void
    {
        $successData = [
            'type' => 'egx30',
            'lottos_name' => 'EGX30',
            'request_date' => '2026-01-15',
            'source_result_id' => 12345,
            'fetch_status' => 'success',
            'lottos_number' => '999',
        ];

        $this->repository->upsertWithResult($successData);

        $failedData = array_merge($successData, [
            'fetch_status' => 'failed',
            'last_error' => 'Forced overwrite',
            'lottos_number' => null,
        ]);

        $result = $this->repository->upsertWithResult($failedData, true);

        $this->assertSame('failed', $result->model->fetch_status);
        $this->assertNull($result->model->lottos_number);
        $this->assertFalse($result->wasSkipped);
        $this->assertTrue($result->wasWritten());
        $this->assertSame(1, LottoResultArchiveLegacyResult::count());
    }

    public function test_fallback_unique_key_when_no_source_result_id(): void
    {
        $data = [
            'type' => 'thai_gov',
            'lottos_name' => 'THAI_GOV',
            'request_date' => '2026-03-01',
            'source_result_id' => null,
            'lottos_date_raw' => '01/03/2026',
            'fetch_status' => 'success',
            'lottos_number' => '111222',
        ];

        $first = $this->repository->upsertWithResult($data);

        $this->assertSame(1, LottoResultArchiveLegacyResult::count());

        $second = $this->repository->upsertWithResult($data);

        $this->assertSame($first->model->id, $second->model->id);
        $this->assertSame(1, LottoResultArchiveLegacyResult::count());

        $expectedKey = hash('sha256', 'thai_gov|2026-03-01|THAI_GOV|01/03/2026');
        $this->assertSame($expectedKey, $first->model->unique_key);
    }
}
