<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('yeekee_rounds')) {
            return;
        }

        Schema::table('yeekee_rounds', function (Blueprint $table): void {
            if (! Schema::hasColumn('yeekee_rounds', 'last_shoot_position')) {
                $table->unsignedInteger('last_shoot_position')->default(0)->after('status');
            }

            if (! Schema::hasColumn('yeekee_rounds', 'shoot_count')) {
                $table->unsignedInteger('shoot_count')->default(0)->after('last_shoot_position');
            }

            if (! Schema::hasColumn('yeekee_rounds', 'shoots_snapshot_json')) {
                $table->json('shoots_snapshot_json')->nullable()->after('config_snapshot_json');
            }

            if (! Schema::hasColumn('yeekee_rounds', 'shoots_snapshot_hash')) {
                $table->string('shoots_snapshot_hash', 64)->nullable()->after('shoots_snapshot_json');
            }

            if (! Schema::hasColumn('yeekee_rounds', 'shoots_frozen_at')) {
                $table->dateTime('shoots_frozen_at')->nullable()->after('shoots_snapshot_hash');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('yeekee_rounds')) {
            return;
        }

        Schema::table('yeekee_rounds', function (Blueprint $table): void {
            if (Schema::hasColumn('yeekee_rounds', 'shoots_frozen_at')) {
                $table->dropColumn('shoots_frozen_at');
            }

            if (Schema::hasColumn('yeekee_rounds', 'shoots_snapshot_hash')) {
                $table->dropColumn('shoots_snapshot_hash');
            }

            if (Schema::hasColumn('yeekee_rounds', 'shoots_snapshot_json')) {
                $table->dropColumn('shoots_snapshot_json');
            }

            if (Schema::hasColumn('yeekee_rounds', 'shoot_count')) {
                $table->dropColumn('shoot_count');
            }

            if (Schema::hasColumn('yeekee_rounds', 'last_shoot_position')) {
                $table->dropColumn('last_shoot_position');
            }
        });
    }
};
