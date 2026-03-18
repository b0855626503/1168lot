<?php

use Doctrine\DBAL\Types\Types;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropIndexMember extends Migration
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

        Schema::table('members', function (Blueprint $table){
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails('members');

            if ($doctrineTable->hasIndex('upline_code')) {
                $table->dropIndex('upline_code');
            }
            if ($doctrineTable->hasIndex('acc_check')) {
                $table->dropIndex('acc_check');
            }
            if ($doctrineTable->hasIndex('acc_bay')) {
                $table->dropIndex('acc_bay');
            }
            if ($doctrineTable->hasIndex('code')) {
                $table->dropIndex('code');
            }
            if ($doctrineTable->hasIndex('id')) {
                $table->dropIndex('id');
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
