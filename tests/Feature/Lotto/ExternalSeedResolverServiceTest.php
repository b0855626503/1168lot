<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\Yeekee\Seed\ExternalSeedResolverService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class ExternalSeedResolverServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yeekee_rounds');
        parent::tearDown();
    }

    public function test_rejects_non_whitelist_provider(): void
    {
        $this->seedRound(1, 101, 15);

        $this->expectException(InvalidArgumentException::class);
        app(ExternalSeedResolverService::class)->resolveForRound(1, [
            'primary_source' => 'CUSTOM_PROVIDER',
        ]);
    }

    public function test_retry_returns_same_snapshot(): void
    {
        $this->seedRound(2, 102, 15);
        $resolver = app(ExternalSeedResolverService::class);

        $first = $resolver->resolveForRound(2, [
            'primary_source' => 'ETH_BLOCK_HASH',
            'mock_seed_value' => 'seed-abc',
        ]);

        $second = $resolver->resolveForRound(2, [
            'primary_source' => 'ETH_BLOCK_HASH',
            'mock_seed_value' => 'seed-changed',
        ]);

        $this->assertSame($first, $second);
        $this->assertSame('seed-abc', $second['seed_value']);
    }

    public function test_rejects_fast_round_when_provider_does_not_support_without_override(): void
    {
        $this->seedRound(3, 103, 5);

        $this->expectException(InvalidArgumentException::class);
        app(ExternalSeedResolverService::class)->resolveForRound(3, [
            'primary_source' => 'ETH_BLOCK_HASH',
            'allow_fast_round_override' => false,
        ]);
    }

    private function seedRound(int $roundId, int $drawId, int $roundDurationMinutes): void
    {
        DB::table('yeekee_rounds')->insert([
            'id' => $roundId,
            'market_id' => 11,
            'lotto_draw_id' => $drawId,
            'round_date' => '2026-04-30',
            'round_no' => 1,
            'bet_open_at' => now(),
            'bet_close_at' => now(),
            'shoot_open_at' => now(),
            'shoot_close_at' => now(),
            'result_compute_at' => now(),
            'expected_settlement_deadline_at' => now(),
            'status' => 'pending_result',
            'config_snapshot_json' => json_encode([
                'round_config' => [
                    'round_duration_minutes' => $roundDurationMinutes,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function prepareSchema(): void
    {
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
            $table->json('config_snapshot_json')->nullable();
            $table->timestamps();
        });
    }
}
