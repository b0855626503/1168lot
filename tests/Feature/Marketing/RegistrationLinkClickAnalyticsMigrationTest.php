<?php

namespace Tests\Feature\Marketing;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationLinkClickAnalyticsMigrationTest extends TestCase
{
    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migration = require base_path('database/migrations/2026_05_02_210938_add_analytics_columns_to_registration_link_clicks_table.php');

        Schema::dropIfExists('registration_link_clicks');
        Schema::create('registration_link_clicks', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('registration_link_id');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('registration_link_clicks');
        parent::tearDown();
    }

    public function test_migration_adds_analytics_columns_and_indexes(): void
    {
        $this->migration->up();

        foreach ([
            'classification_type',
            'risk_score',
            'visitor_id',
            'client_confirmed_at',
            'submitted_at',
            'converted_member_id',
            'converted_at',
            'register_type',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('registration_link_clicks', $column),
                "Missing column {$column}"
            );
        }
    }

    public function test_migration_down_declares_only_analytics_columns_for_drop(): void
    {
        $source = file_get_contents(base_path('database/migrations/2026_05_02_210938_add_analytics_columns_to_registration_link_clicks_table.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString("'classification_type'", $source);
        $this->assertStringContainsString("'metadata_json'", $source);
        $this->assertStringContainsString('$table->dropColumn([', $source);
        $this->assertStringContainsString("'converted_member_id'", $source);
        $this->assertStringContainsString("'register_type'", $source);
    }
}
