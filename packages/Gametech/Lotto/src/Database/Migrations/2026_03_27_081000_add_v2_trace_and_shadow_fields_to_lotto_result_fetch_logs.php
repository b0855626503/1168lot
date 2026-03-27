<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddV2TraceAndShadowFieldsToLottoResultFetchLogs extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_result_fetch_logs')) {
            return;
        }

        Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_result_fetch_logs', 'trace_json')) {
                $table->json('trace_json')->nullable();
            }

            if (! Schema::hasColumn('lotto_result_fetch_logs', 'error_code')) {
                $table->string('error_code', 64)->nullable();
            }

            if (! Schema::hasColumn('lotto_result_fetch_logs', 'error_stage')) {
                $table->string('error_stage', 64)->nullable();
            }

            if (! Schema::hasColumn('lotto_result_fetch_logs', 'legacy_result_json')) {
                $table->json('legacy_result_json')->nullable();
            }

            if (! Schema::hasColumn('lotto_result_fetch_logs', 'v2_result_json')) {
                $table->json('v2_result_json')->nullable();
            }

            if (! Schema::hasColumn('lotto_result_fetch_logs', 'shadow_diff_json')) {
                $table->json('shadow_diff_json')->nullable();
            }

            if (! Schema::hasColumn('lotto_result_fetch_logs', 'shadow_compare_status')) {
                $table->string('shadow_compare_status', 32)->nullable();
            }
        });

        if (! $this->hasIndex('lotto_result_fetch_logs', 'lotto_result_fetch_logs_error_code_idx')) {
            Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_result_fetch_logs', 'error_code')) {
                    $table->index(['error_code'], 'lotto_result_fetch_logs_error_code_idx');
                }
            });
        }

        if (! $this->hasIndex('lotto_result_fetch_logs', 'lotto_result_fetch_logs_error_stage_idx')) {
            Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_result_fetch_logs', 'error_stage')) {
                    $table->index(['error_stage'], 'lotto_result_fetch_logs_error_stage_idx');
                }
            });
        }

        if (! $this->hasIndex('lotto_result_fetch_logs', 'lotto_result_fetch_logs_shadow_compare_status_idx')) {
            Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_result_fetch_logs', 'shadow_compare_status')) {
                    $table->index(['shadow_compare_status'], 'lotto_result_fetch_logs_shadow_compare_status_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_result_fetch_logs')) {
            return;
        }

        Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_result_fetch_logs', 'shadow_compare_status')) {
                if ($this->hasIndex('lotto_result_fetch_logs', 'lotto_result_fetch_logs_shadow_compare_status_idx')) {
                    $table->dropIndex('lotto_result_fetch_logs_shadow_compare_status_idx');
                }
                $table->dropColumn('shadow_compare_status');
            }

            if (Schema::hasColumn('lotto_result_fetch_logs', 'shadow_diff_json')) {
                $table->dropColumn('shadow_diff_json');
            }

            if (Schema::hasColumn('lotto_result_fetch_logs', 'v2_result_json')) {
                $table->dropColumn('v2_result_json');
            }

            if (Schema::hasColumn('lotto_result_fetch_logs', 'legacy_result_json')) {
                $table->dropColumn('legacy_result_json');
            }

            if (Schema::hasColumn('lotto_result_fetch_logs', 'error_stage')) {
                if ($this->hasIndex('lotto_result_fetch_logs', 'lotto_result_fetch_logs_error_stage_idx')) {
                    $table->dropIndex('lotto_result_fetch_logs_error_stage_idx');
                }
                $table->dropColumn('error_stage');
            }

            if (Schema::hasColumn('lotto_result_fetch_logs', 'error_code')) {
                if ($this->hasIndex('lotto_result_fetch_logs', 'lotto_result_fetch_logs_error_code_idx')) {
                    $table->dropIndex('lotto_result_fetch_logs_error_code_idx');
                }
                $table->dropColumn('error_code');
            }

            if (Schema::hasColumn('lotto_result_fetch_logs', 'trace_json')) {
                $table->dropColumn('trace_json');
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
