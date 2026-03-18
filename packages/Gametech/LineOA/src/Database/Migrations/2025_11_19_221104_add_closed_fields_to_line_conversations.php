<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClosedFieldsToLineConversations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('line_conversations', ['assigned_employee_name','assigned_at','locked_by_employee_name','locked_at'])) {
            Schema::table('line_conversations', function (Blueprint $table) {
                $table->string('assigned_employee_name')->nullable()->after('assigned_employee_id');
                $table->string('locked_by_employee_name')->nullable()->after('locked_by_employee_id');
                $table->timestamp('assigned_at')->nullable()->after('assigned_employee_name');
                $table->timestamp('locked_at')->nullable()->after('locked_by_employee_name');
            });
        }

        Schema::table('line_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('closed_by_employee_id')->nullable()->after('assigned_at');
            $table->string('closed_by_employee_name')->nullable()->after('closed_by_employee_id');
            $table->timestamp('closed_at')->nullable()->after('closed_by_employee_name');
        });
    }

    public function down()
    {
        Schema::table('line_conversations', function (Blueprint $table) {
            $table->dropColumn([
                'closed_by_employee_id',
                'closed_by_employee_name',
                'closed_at',
            ]);
        });
    }
}
