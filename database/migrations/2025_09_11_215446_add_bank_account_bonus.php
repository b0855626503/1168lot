<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankAccountBonus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('banks_account', ['bonus','date_start','time_start','date_end','time_end'])) {
            Schema::table('banks_account', function (Blueprint $t) {
                $t->decimal('bonus', 10)->default(0.00);
                $t->date('date_start')->after('code');
                $t->time('time_start')->after('date_start');
                $t->date('date_end')->nullable()->after('time_start');
                $t->time('time_end')->nullable()->after('date_end');

                $t->index(['date_start','time_start']);
                $t->index(['date_end','time_end']);

                $t->dateTime('start_at')->storedAs("STR_TO_DATE(CONCAT(date_start,' ',time_start), '%Y-%m-%d %H:%i:%s')")->after('time_end');
                $t->dateTime('end_at')->nullable()->storedAs("CASE
                WHEN date_end IS NULL OR time_end IS NULL THEN NULL
                ELSE STR_TO_DATE(CONCAT(date_end,' ',time_end), '%Y-%m-%d %H:%i:%s')
            END")->after('start_at');

                $t->index('start_at'); $t->index('end_at');
            });
            DB::statement("
            ALTER TABLE banks_account
            ADD CONSTRAINT chk_promo_range
            CHECK (end_at IS NULL OR end_at >= start_at)
        ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banks_account', function (Blueprint $t) {
            $t->dropConstrainedForeignIdIfExists('chk_promo_range'); // เผื่อใช้ FK; ถ้า error ให้ใช้ DB::statement ลบ
            $t->dropIndex(['date_start','time_start']);
            $t->dropIndex(['date_end','time_end']);
            $t->dropIndex(['start_at']);
            $t->dropIndex(['end_at']);

            $t->dropColumn(['start_at','end_at','time_end','date_end','time_start','date_start']);
        });
    }
}
