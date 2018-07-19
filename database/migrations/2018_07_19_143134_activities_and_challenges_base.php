<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActivitiesAndChallengesBase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id', false, true);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('name');

            $table->unsignedDecimal('distance_miles', 6, 3);
            $table->time('duration');

            $table->dateTime('started_at');
            \Challengr\Util::betterTimestamps($table);
        });

        Schema::create('challenges', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id', false, true);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('name');

            $table->unsignedDecimal('distance_miles', 6, 3)->nullable();
            $table->time('duration')->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            \Challengr\Util::betterTimestamps($table);
        });

        Schema::create('challenge_user', function (Blueprint $table) {
            $table->integer('user_id', false, true);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->integer('challenge_id', false, true);
            $table->foreign('challenge_id')->references('id')->on('challenges')->onDelete('cascade');

            \Challengr\Util::betterTimestamps($table);

            $table->unique(['user_id', 'challenge_id']);
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
