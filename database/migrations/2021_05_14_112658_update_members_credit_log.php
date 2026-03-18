<?php

use Doctrine\DBAL\Types\Types;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateMembersCreditLog extends Migration
{

    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', Types::STRING);
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('members_credit_log', function (Blueprint $table){
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails('members_credit_log');

            if ($doctrineTable->hasIndex('member_code')) {
                $table->dropIndex('member_code');
            }
            if ($doctrineTable->hasIndex('emp_code')) {
                $table->dropIndex('emp_code');
            }

            $table->index(['kind', 'member_code', 'date_create'],'members_credit_log_index');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
