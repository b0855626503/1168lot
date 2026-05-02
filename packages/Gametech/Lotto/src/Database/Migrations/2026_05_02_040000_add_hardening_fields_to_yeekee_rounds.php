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

            if (! Schema::hasColumn('yeekee_rounds', 'shoot_snapshot_json')) {
                $table->json('shoot_snapshot_json')->nullable()->after('config_snapshot_json');
            }

            if (! Schema::hasColumn('yeekee_rounds', 'shoot_snapshot_hash')) {
                $table->string('shoot_snapshot_hash', 64)->nullable()->after('shoot_snapshot_json');
            }

            if (! Schema::hasColumn('yeekee_rounds', 'shoot_closed_at')) {
                $table->dateTime('shoot_closed_at')->nullable()->after('shoot_snapshot_hash');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('yeekee_rounds')) {
            return;
        }

        Schema::table('yeekee_rounds', function (Blueprint $table): void {
            if (Schema::hasColumn('yeekee_rounds', 'shoot_closed_at')) {
                $table->dropColumn('shoot_closed_at');
            }

            if (Schema::hasColumn('yeekee_rounds', 'shoot_snapshot_hash')) {
                $table->dropColumn('shoot_snapshot_hash');
            }

            if (Schema::hasColumn('yeekee_rounds', 'shoot_snapshot_json')) {
                $table->dropColumn('shoot_snapshot_json');
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
