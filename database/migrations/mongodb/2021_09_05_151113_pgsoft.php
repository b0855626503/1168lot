<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Pgsoft extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pgsoft', function ($collection) {
            $collection->id();
            $collection->string('operator_player_session');
            $collection->string('player_name');
            $collection->unsignedTinyInteger('game_id');
            $collection->string('parent_bet_id');
            $collection->string('bet_id');
            $collection->unsignedTinyInteger('bet_type');
            $collection->string('currency_code',3);
            $collection->unsignedTinyInteger('platform');
            $collection->decimal('jackpot_rtp_contribution_amount', 8, 2);
            $collection->decimal('jackpot_win_amount', 8, 2);
            $collection->decimal('transfer_amount', 8, 2);
            $collection->string('parent_bet_id');
            $collection->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pgsoft');
    }
}
