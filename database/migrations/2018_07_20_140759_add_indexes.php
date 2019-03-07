<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->index(['user_id', 'started_at', 'duration'], 'duration_by_user_started_at');
            $table->index(['user_id', 'started_at', 'distance_miles'], 'distance_miles_by_user_started_at');
        });

        Schema::table('challenges', function (Blueprint $table) {
            $table->index(['starts_at', 'ends_at'], 'starts_at_ends_at');
        });

        Schema::table('challenge_user', function (Blueprint $table) {
            $table->index(['challenge_id', 'user_id'], 'challenge_user');
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
