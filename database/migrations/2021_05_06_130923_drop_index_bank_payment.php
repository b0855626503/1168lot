<?php

use Doctrine\DBAL\Types\Types;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropIndexBankPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', Types::STRING);
    }

    public function up()
    {


        Schema::table('bank_payment', function (Blueprint $table)  {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails('bank_payment');

            if ($doctrineTable->hasIndex('id')) {
                $table->dropIndex('id');
            }
            if ($doctrineTable->hasIndex('report_id')) {
                $table->dropIndex('report_id');
            }
            if ($doctrineTable->hasIndex('tx_hash')) {
                $table->dropIndex('tx_hash');
            }
            if ($doctrineTable->hasIndex('bank_index')) {
                $table->dropIndex('bank_index');
            }

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
