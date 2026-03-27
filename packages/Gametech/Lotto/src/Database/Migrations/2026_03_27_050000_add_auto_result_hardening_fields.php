<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAutoResultHardeningFields extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_draws')) {
            Schema::table('lotto_draws', function (Blueprint $table): void {
                if (! Schema::hasColumn('lotto_draws', 'result_fetch_status')) {
                    $table->string('result_fetch_status', 32)->nullable();
                    if (! $this->hasIndex('lotto_draws', 'lotto_draws_result_fetch_status_idx')) {
                        $table->index(['result_fetch_status'], 'lotto_draws_result_fetch_status_idx');
                    }
                }

                if (! Schema::hasColumn('lotto_draws', 'result_fetch_attempts')) {
                    $table->unsignedInteger('result_fetch_attempts')->default(0);
                }

                if (! Schema::hasColumn('lotto_draws', 'result_fetched_at')) {
                    $table->dateTime('result_fetched_at')->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'result_fetch_error')) {
                    $table->text('result_fetch_error')->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'result_raw_payload_json')) {
                    $table->json('result_raw_payload_json')->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'result_normalized_payload_json')) {
                    $table->json('result_normalized_payload_json')->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'result_hash')) {
                    $table->string('result_hash', 128)->nullable();
                }

                if (! Schema::hasColumn('lotto_draws', 'exhausted_alerted_at')) {
                    $table->dateTime('exhausted_alerted_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('lotto_result_fetch_logs')) {
            Schema::create('lotto_result_fetch_logs', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('draw_id')->nullable();
                $table->unsignedBigInteger('market_id')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedInteger('attempt_no')->default(1);
                $table->string('status', 32);
                $table->text('request_url')->nullable();
                $table->json('request_meta_json')->nullable();
                $table->integer('response_http_status')->nullable();
                $table->longText('response_body')->nullable();
                $table->json('parsed_payload_json')->nullable();
                $table->json('normalized_result_json')->nullable();
                $table->boolean('is_dry_run')->default(false);
                $table->boolean('is_manual_settle')->default(false);
                $table->text('error_message')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->dateTime('created_at')->nullable();

                $table->index(['draw_id', 'created_at'], 'lotto_result_fetch_logs_draw_created_idx');
                $table->index(['source_id', 'created_at'], 'lotto_result_fetch_logs_source_created_idx');
                $table->index(['status', 'created_at'], 'lotto_result_fetch_logs_status_created_idx');
            });
        } else {
            Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
                if (! Schema::hasColumn('lotto_result_fetch_logs', 'attempt_no')) {
                    $table->unsignedInteger('attempt_no')->default(1);
                }

                if (! Schema::hasColumn('lotto_result_fetch_logs', 'is_dry_run')) {
                    $table->boolean('is_dry_run')->default(false);
                }

                if (! Schema::hasColumn('lotto_result_fetch_logs', 'is_manual_settle')) {
                    $table->boolean('is_manual_settle')->default(false);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lotto_draws')) {
            Schema::table('lotto_draws', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_draws', 'exhausted_alerted_at')) {
                    $table->dropColumn('exhausted_alerted_at');
                }
                if (Schema::hasColumn('lotto_draws', 'result_hash')) {
                    $table->dropColumn('result_hash');
                }
                if (Schema::hasColumn('lotto_draws', 'result_normalized_payload_json')) {
                    $table->dropColumn('result_normalized_payload_json');
                }
                if (Schema::hasColumn('lotto_draws', 'result_raw_payload_json')) {
                    $table->dropColumn('result_raw_payload_json');
                }
                if (Schema::hasColumn('lotto_draws', 'result_fetch_error')) {
                    $table->dropColumn('result_fetch_error');
                }
                if (Schema::hasColumn('lotto_draws', 'result_fetched_at')) {
                    $table->dropColumn('result_fetched_at');
                }
                if (Schema::hasColumn('lotto_draws', 'result_fetch_attempts')) {
                    $table->dropColumn('result_fetch_attempts');
                }
                if (Schema::hasColumn('lotto_draws', 'result_fetch_status')) {
                    if ($this->hasIndex('lotto_draws', 'lotto_draws_result_fetch_status_idx')) {
                        $table->dropIndex('lotto_draws_result_fetch_status_idx');
                    }
                    $table->dropColumn('result_fetch_status');
                }
            });
        }

        if (Schema::hasTable('lotto_result_fetch_logs')) {
            Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_result_fetch_logs', 'is_manual_settle')) {
                    $table->dropColumn('is_manual_settle');
                }
                if (Schema::hasColumn('lotto_result_fetch_logs', 'is_dry_run')) {
                    $table->dropColumn('is_dry_run');
                }
                if (Schema::hasColumn('lotto_result_fetch_logs', 'attempt_no')) {
                    $table->dropColumn('attempt_no');
                }
            });
        }
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
