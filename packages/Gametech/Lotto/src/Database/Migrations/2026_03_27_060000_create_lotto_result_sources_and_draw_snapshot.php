<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateLottoResultSourcesAndDrawSnapshot extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_result_sources')) {
            Schema::create('lotto_result_sources', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('market_id');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('priority')->default(100);
                $table->string('source_type', 16); // api | html
                $table->string('endpoint_url', 2048);
                $table->string('http_method', 16)->default('GET');
                $table->json('request_headers_json')->nullable();
                $table->json('request_query_template_json')->nullable();
                $table->json('request_body_template_json')->nullable();
                $table->string('lookup_date_mode', 64)->default('ROUND_DATE');
                $table->integer('lookup_date_offset_days')->default(0);
                $table->string('parser_type', 32)->default('JSON_PATH');
                $table->json('parser_config_json')->nullable();
                $table->json('mapping_config_json')->nullable();
                $table->json('validation_config_json')->nullable();
                $table->json('retry_policy_json')->nullable();
                $table->unsignedInteger('timeout_seconds')->default(10);
                $table->dateTime('effective_from')->nullable();
                $table->dateTime('effective_to')->nullable();
                $table->timestamps();

                $table->index(['market_id', 'is_active'], 'lotto_result_sources_market_active_idx');
                $table->index(['market_id', 'priority'], 'lotto_result_sources_market_priority_idx');
                $table->index(['effective_from', 'effective_to'], 'lotto_result_sources_effective_window_idx');
                $table->foreign('market_id')
                    ->references('id')
                    ->on('lotto_markets')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('lotto_draws')) {
            Schema::table('lotto_draws', function (Blueprint $table): void {
                if (! Schema::hasColumn('lotto_draws', 'result_source_snapshot_json')) {
                    $table->json('result_source_snapshot_json')->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'result_source_id')) {
                    $table->unsignedBigInteger('result_source_id')->nullable();
                    if (! $this->hasIndex('lotto_draws', 'lotto_draws_result_source_id_idx')) {
                        $table->index(['result_source_id'], 'lotto_draws_result_source_id_idx');
                    }
                }

                if (! Schema::hasColumn('lotto_draws', 'result_source_version')) {
                    $table->string('result_source_version', 64)->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'result_applied_at')) {
                    $table->dateTime('result_applied_at')->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'result_conflicted_at')) {
                    $table->dateTime('result_conflicted_at')->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'result_conflict_payload_json')) {
                    $table->json('result_conflict_payload_json')->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'result_exhausted_at')) {
                    $table->dateTime('result_exhausted_at')->nullable();
                }

                if (! $this->hasIndex('lotto_draws', 'lotto_draws_status_result_at_idx')) {
                    $table->index(['status', 'result_at'], 'lotto_draws_status_result_at_idx');
                }
            });
        }

        if (Schema::hasTable('lotto_result_fetch_logs')) {
            Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
                if (! Schema::hasColumn('lotto_result_fetch_logs', 'is_manual_retry')) {
                    $table->boolean('is_manual_retry')->default(false);
                }

                if (! Schema::hasColumn('lotto_result_fetch_logs', 'pipeline_stage')) {
                    $table->string('pipeline_stage', 32)->nullable();
                }

                if (! Schema::hasColumn('lotto_result_fetch_logs', 'run_id')) {
                    $table->string('run_id', 64)->nullable();
                    if (! $this->hasIndex('lotto_result_fetch_logs', 'lotto_result_fetch_logs_run_id_idx')) {
                        $table->index(['run_id'], 'lotto_result_fetch_logs_run_id_idx');
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lotto_result_fetch_logs')) {
            Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_result_fetch_logs', 'run_id')) {
                    if ($this->hasIndex('lotto_result_fetch_logs', 'lotto_result_fetch_logs_run_id_idx')) {
                        $table->dropIndex('lotto_result_fetch_logs_run_id_idx');
                    }
                    $table->dropColumn('run_id');
                }
                if (Schema::hasColumn('lotto_result_fetch_logs', 'pipeline_stage')) {
                    $table->dropColumn('pipeline_stage');
                }
                if (Schema::hasColumn('lotto_result_fetch_logs', 'is_manual_retry')) {
                    $table->dropColumn('is_manual_retry');
                }
            });
        }

        if (Schema::hasTable('lotto_draws')) {
            Schema::table('lotto_draws', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_draws', 'result_exhausted_at')) {
                    $table->dropColumn('result_exhausted_at');
                }
                if (Schema::hasColumn('lotto_draws', 'result_conflict_payload_json')) {
                    $table->dropColumn('result_conflict_payload_json');
                }
                if (Schema::hasColumn('lotto_draws', 'result_conflicted_at')) {
                    $table->dropColumn('result_conflicted_at');
                }
                if (Schema::hasColumn('lotto_draws', 'result_applied_at')) {
                    $table->dropColumn('result_applied_at');
                }
                if (Schema::hasColumn('lotto_draws', 'result_source_version')) {
                    $table->dropColumn('result_source_version');
                }
                if (Schema::hasColumn('lotto_draws', 'result_source_id')) {
                    if ($this->hasIndex('lotto_draws', 'lotto_draws_result_source_id_idx')) {
                        $table->dropIndex('lotto_draws_result_source_id_idx');
                    }
                    $table->dropColumn('result_source_id');
                }
                if (Schema::hasColumn('lotto_draws', 'result_source_snapshot_json')) {
                    $table->dropColumn('result_source_snapshot_json');
                }

                if ($this->hasIndex('lotto_draws', 'lotto_draws_status_result_at_idx')) {
                    $table->dropIndex('lotto_draws_status_result_at_idx');
                }
            });
        }

        if (Schema::hasTable('lotto_result_sources')) {
            Schema::dropIfExists('lotto_result_sources');
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        try {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                $rows = DB::select("PRAGMA index_list('{$table}')");
                foreach ($rows as $row) {
                    if (($row->name ?? null) === $index) {
                        return true;
                    }
                }

                return false;
            }

            $rows = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
                [$table, $index]
            );

            return ! empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
