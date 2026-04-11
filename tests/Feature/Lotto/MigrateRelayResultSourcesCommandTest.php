<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrateRelayResultSourcesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('lottery_result_relay.api_base_url', 'https://api.1168lot.test');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_result_sources', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->boolean('is_active')->default(false);
            $table->string('endpoint_url')->nullable();
            $table->string('http_method', 16)->nullable();
            $table->text('request_headers_json')->nullable();
            $table->text('request_query_template_json')->nullable();
            $table->string('parser_type', 32)->nullable();
            $table->text('parser_config_json')->nullable();
            $table->text('mapping_config_json')->nullable();
            $table->text('validation_config_json')->nullable();
            $table->text('selection_config_json')->nullable();
            $table->text('readiness_config_json')->nullable();
            $table->text('fetch_config_json')->nullable();
            $table->string('pipeline_version', 32)->nullable();
            $table->string('fetch_strategy', 32)->nullable();
            $table->string('selection_stage', 32)->nullable();
            $table->boolean('supports_partial')->default(false);
            $table->boolean('requires_browser')->default(false);
            $table->boolean('shadow_enabled')->default(false);
            $table->boolean('cutover_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function test_command_updates_sources_to_public_relay_contract(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 1,
            'code' => 'downjone-stock',
            'is_enabled' => 1,
        ]);

        DB::table('lotto_result_sources')->insert([
            'id' => 55,
            'market_id' => 1,
            'is_active' => 0,
            'endpoint_url' => 'https://old.example.com/result',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('lotto:migrate-relay-result-sources', [
            '--apply' => true,
            '--activate' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $source = DB::table('lotto_result_sources')->where('id', 55)->first();

        $this->assertSame('https://api.1168lot.test/api/v1/get_lottery', $source->endpoint_url);
        $this->assertSame(1, (int) $source->is_active);
        $this->assertStringContainsString('"type":"dji"', (string) $source->request_query_template_json);
        $this->assertStringContainsString('$.results[0].lottosNumber', (string) $source->parser_config_json);
    }
}
