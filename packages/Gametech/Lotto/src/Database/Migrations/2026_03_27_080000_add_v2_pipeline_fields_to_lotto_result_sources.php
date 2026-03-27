<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddV2PipelineFieldsToLottoResultSources extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_result_sources')) {
            return;
        }

        Schema::table('lotto_result_sources', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_result_sources', 'fetch_config_json')) {
                $table->json('fetch_config_json')->nullable();
            }

            if (! Schema::hasColumn('lotto_result_sources', 'selection_config_json')) {
                $table->json('selection_config_json')->nullable();
            }

            if (! Schema::hasColumn('lotto_result_sources', 'readiness_config_json')) {
                $table->json('readiness_config_json')->nullable();
            }

            if (! Schema::hasColumn('lotto_result_sources', 'pipeline_version')) {
                $table->string('pipeline_version', 32)->nullable();
            }

            if (! Schema::hasColumn('lotto_result_sources', 'fetch_strategy')) {
                $table->string('fetch_strategy', 64)->nullable();
            }

            if (! Schema::hasColumn('lotto_result_sources', 'selection_stage')) {
                $table->string('selection_stage', 64)->nullable();
            }

            if (! Schema::hasColumn('lotto_result_sources', 'supports_partial')) {
                $table->boolean('supports_partial')->default(false);
            }

            if (! Schema::hasColumn('lotto_result_sources', 'requires_browser')) {
                $table->boolean('requires_browser')->default(false);
            }

            if (! Schema::hasColumn('lotto_result_sources', 'shadow_enabled')) {
                $table->boolean('shadow_enabled')->default(false);
            }

            if (! Schema::hasColumn('lotto_result_sources', 'cutover_enabled')) {
                $table->boolean('cutover_enabled')->default(false);
            }
        });

        if (! $this->hasIndex('lotto_result_sources', 'lotto_result_sources_pipeline_version_idx')) {
            Schema::table('lotto_result_sources', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_result_sources', 'pipeline_version')) {
                    $table->index(['pipeline_version'], 'lotto_result_sources_pipeline_version_idx');
                }
            });
        }

        if (! $this->hasIndex('lotto_result_sources', 'lotto_result_sources_fetch_strategy_idx')) {
            Schema::table('lotto_result_sources', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_result_sources', 'fetch_strategy')) {
                    $table->index(['fetch_strategy'], 'lotto_result_sources_fetch_strategy_idx');
                }
            });
        }

        if (! $this->hasIndex('lotto_result_sources', 'lotto_result_sources_selection_stage_idx')) {
            Schema::table('lotto_result_sources', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_result_sources', 'selection_stage')) {
                    $table->index(['selection_stage'], 'lotto_result_sources_selection_stage_idx');
                }
            });
        }

        if (! $this->hasIndex('lotto_result_sources', 'lotto_result_sources_shadow_cutover_idx')) {
            Schema::table('lotto_result_sources', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_result_sources', 'shadow_enabled') && Schema::hasColumn('lotto_result_sources', 'cutover_enabled')) {
                    $table->index(['shadow_enabled', 'cutover_enabled'], 'lotto_result_sources_shadow_cutover_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_result_sources')) {
            return;
        }

        Schema::table('lotto_result_sources', function (Blueprint $table): void {
            if ($this->hasIndex('lotto_result_sources', 'lotto_result_sources_shadow_cutover_idx')) {
                $table->dropIndex('lotto_result_sources_shadow_cutover_idx');
            }

            if (Schema::hasColumn('lotto_result_sources', 'cutover_enabled')) {
                $table->dropColumn('cutover_enabled');
            }

            if (Schema::hasColumn('lotto_result_sources', 'shadow_enabled')) {
                $table->dropColumn('shadow_enabled');
            }

            if (Schema::hasColumn('lotto_result_sources', 'requires_browser')) {
                $table->dropColumn('requires_browser');
            }

            if (Schema::hasColumn('lotto_result_sources', 'supports_partial')) {
                $table->dropColumn('supports_partial');
            }

            if (Schema::hasColumn('lotto_result_sources', 'selection_stage')) {
                if ($this->hasIndex('lotto_result_sources', 'lotto_result_sources_selection_stage_idx')) {
                    $table->dropIndex('lotto_result_sources_selection_stage_idx');
                }
                $table->dropColumn('selection_stage');
            }

            if (Schema::hasColumn('lotto_result_sources', 'fetch_strategy')) {
                if ($this->hasIndex('lotto_result_sources', 'lotto_result_sources_fetch_strategy_idx')) {
                    $table->dropIndex('lotto_result_sources_fetch_strategy_idx');
                }
                $table->dropColumn('fetch_strategy');
            }

            if (Schema::hasColumn('lotto_result_sources', 'pipeline_version')) {
                if ($this->hasIndex('lotto_result_sources', 'lotto_result_sources_pipeline_version_idx')) {
                    $table->dropIndex('lotto_result_sources_pipeline_version_idx');
                }
                $table->dropColumn('pipeline_version');
            }

            if (Schema::hasColumn('lotto_result_sources', 'readiness_config_json')) {
                $table->dropColumn('readiness_config_json');
            }

            if (Schema::hasColumn('lotto_result_sources', 'selection_config_json')) {
                $table->dropColumn('selection_config_json');
            }

            if (Schema::hasColumn('lotto_result_sources', 'fetch_config_json')) {
                $table->dropColumn('fetch_config_json');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        try {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();

            if ($driver === 'sqlite') {
                $rows = $connection->select("PRAGMA index_list('{$table}')");
                foreach ($rows as $row) {
                    if (($row->name ?? null) === $index) {
                        return true;
                    }
                }

                return false;
            }

            $rows = $connection->select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
                [$table, $index]
            );

            return ! empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
