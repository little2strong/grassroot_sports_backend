<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->enum('toss_winner_side', ['club', 'opponent'])->nullable()->after('toss_decision');
        });

        Schema::table('innings', function (Blueprint $table) {
            $table->boolean('batting_is_club')->default(true)->after('bowling_team_id');
            $table->boolean('bowling_is_club')->default(false)->after('batting_is_club');
            $table->unsignedTinyInteger('external_striker_index')->nullable()->after('current_bowler_id');
            $table->unsignedTinyInteger('external_non_striker_index')->nullable()->after('external_striker_index');
            $table->unsignedTinyInteger('external_bowler_index')->nullable()->after('external_non_striker_index');
        });

        Schema::table('innings', function (Blueprint $table) {
            $table->dropForeign(['batting_team_id']);
            $table->dropForeign(['bowling_team_id']);
        });

        Schema::table('innings', function (Blueprint $table) {
            $table->unsignedBigInteger('batting_team_id')->nullable()->change();
            $table->unsignedBigInteger('bowling_team_id')->nullable()->change();
            $table->foreign('batting_team_id')->references('id')->on('teams')->nullOnDelete();
            $table->foreign('bowling_team_id')->references('id')->on('teams')->nullOnDelete();
        });

        Schema::table('batting_scores', function (Blueprint $table) {
            $table->unsignedTinyInteger('external_player_index')->nullable()->after('user_id');
            $table->string('external_player_name')->nullable()->after('external_player_index');
        });

        Schema::table('batting_scores', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('batting_scores', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->unsignedBigInteger('team_id')->nullable()->change();
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
        });

        Schema::table('bowling_figures', function (Blueprint $table) {
            $table->unsignedTinyInteger('external_player_index')->nullable()->after('user_id');
            $table->string('external_player_name')->nullable()->after('external_player_index');
        });

        Schema::table('bowling_figures', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('bowling_figures', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->unsignedBigInteger('team_id')->nullable()->change();
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
        });

        Schema::table('ball_events', function (Blueprint $table) {
            $table->unsignedTinyInteger('external_striker_index')->nullable()->after('bowler_id');
            $table->unsignedTinyInteger('external_non_striker_index')->nullable()->after('external_striker_index');
            $table->unsignedTinyInteger('external_bowler_index')->nullable()->after('external_non_striker_index');
        });

        Schema::table('ball_events', function (Blueprint $table) {
            $table->dropForeign(['striker_id']);
            $table->dropForeign(['non_striker_id']);
            $table->dropForeign(['bowler_id']);
            $table->dropForeign(['batting_team_id']);
            $table->dropForeign(['bowling_team_id']);
        });

        Schema::table('ball_events', function (Blueprint $table) {
            $table->unsignedBigInteger('striker_id')->nullable()->change();
            $table->unsignedBigInteger('non_striker_id')->nullable()->change();
            $table->unsignedBigInteger('bowler_id')->nullable()->change();
            $table->unsignedBigInteger('batting_team_id')->nullable()->change();
            $table->unsignedBigInteger('bowling_team_id')->nullable()->change();
            $table->foreign('striker_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('non_striker_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('bowler_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('batting_team_id')->references('id')->on('teams')->nullOnDelete();
            $table->foreign('bowling_team_id')->references('id')->on('teams')->nullOnDelete();
        });

        Schema::table('wickets', function (Blueprint $table) {
            $table->unsignedTinyInteger('external_dismissed_batter_index')->nullable()->after('dismissed_batter_id');
            $table->string('external_dismissed_batter_name')->nullable()->after('external_dismissed_batter_index');
            $table->unsignedTinyInteger('external_bowler_index')->nullable()->after('bowler_id');
            $table->unsignedTinyInteger('external_fielder_one_index')->nullable()->after('fielder_one_id');
            $table->unsignedTinyInteger('external_fielder_two_index')->nullable()->after('fielder_two_id');
        });

        Schema::table('wickets', function (Blueprint $table) {
            $table->dropForeign(['dismissed_batter_id']);
            $table->unsignedBigInteger('dismissed_batter_id')->nullable()->change();
            $table->foreign('dismissed_batter_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('over_summaries', function (Blueprint $table) {
            $table->unsignedTinyInteger('external_bowler_index')->nullable()->after('bowler_id');
            $table->string('external_bowler_name')->nullable()->after('external_bowler_index');
        });

        Schema::table('over_summaries', function (Blueprint $table) {
            $table->dropForeign(['bowler_id']);
            $table->unsignedBigInteger('bowler_id')->nullable()->change();
            $table->foreign('bowler_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('over_summaries', function (Blueprint $table) {
            $table->dropForeign(['bowling_team_id']);
            $table->unsignedBigInteger('bowling_team_id')->nullable()->change();
            $table->foreign('bowling_team_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally omitted for brevity in dev; reverse would restore NOT NULL FKs.
    }
};
