<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // matches → innings
        Schema::table('matches', function (Blueprint $table) {
            $table->foreign('first_innings_id')
                ->references('id')->on('innings')
                ->nullOnDelete();
            $table->foreign('second_innings_id')
                ->references('id')->on('innings')
                ->nullOnDelete();
        });

        // ball_events → wickets
        Schema::table('ball_events', function (Blueprint $table) {
            $table->foreign('wicket_id')
                ->references('id')->on('wickets')
                ->nullOnDelete();
        });

        // batting_scores → wickets
        Schema::table('batting_scores', function (Blueprint $table) {
            $table->foreign('wicket_id')
                ->references('id')->on('wickets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('batting_scores', function (Blueprint $table) {
            $table->dropForeign(['wicket_id']);
        });

        Schema::table('ball_events', function (Blueprint $table) {
            $table->dropForeign(['wicket_id']);
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['first_innings_id']);
            $table->dropForeign(['second_innings_id']);
        });
    }
};
