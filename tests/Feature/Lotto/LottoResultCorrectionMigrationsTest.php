<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoResultCorrectionMigrationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareBaseTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_result_correction_items');
        Schema::dropIfExists('lotto_result_corrections');
        Schema::dropIfExists('lotto_winnings');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        parent::tearDown();
    }

    public function test_result_correction_migrations_up_and_down_work(): void
    {
        $migrations = $this->loadMigrations();
        foreach ($migrations as $migration) {
            $migration->up();
        }

        $this->assertTrue(Schema::hasTable('lotto_result_corrections'));
        $this->assertTrue(Schema::hasTable('lotto_result_correction_items'));
        $this->assertTrue(Schema::hasColumn('lotto_winnings', 'voided_by_correction_id'));
        $this->assertTrue(Schema::hasColumn('lotto_winnings', 'voided_at'));

        for ($i = count($migrations) - 1; $i >= 0; $i--) {
            $migrations[$i]->down();
        }

        $this->assertFalse(Schema::hasTable('lotto_result_correction_items'));
        $this->assertFalse(Schema::hasTable('lotto_result_corrections'));
    }

    /**
     * @return array<int, Migration>
     */
    private function loadMigrations(): array
    {
        $paths = [
            '/packages/Gametech/Lotto/src/Database/Migrations/2026_05_09_180000_create_lotto_result_corrections_table.php',
            '/packages/Gametech/Lotto/src/Database/Migrations/2026_05_09_180100_create_lotto_result_correction_items_table.php',
            '/packages/Gametech/Lotto/src/Database/Migrations/2026_05_09_180200_add_voided_columns_to_lotto_winnings.php',
        ];

        $rootPath = dirname(__DIR__, 3);

        return array_map(
            static fn (string $path): Migration => require $rootPath.$path,
            $paths
        );
    }

    private function prepareBaseTables(): void
    {
        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->timestamps();
        });

        Schema::create('lotto_winnings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->unsignedBigInteger('settlement_batch_id')->nullable();
            $table->timestamps();
        });
    }
}
