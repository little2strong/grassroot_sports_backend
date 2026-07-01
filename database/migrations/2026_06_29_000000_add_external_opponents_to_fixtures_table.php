<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropForeign(['home_team_id']);
            $table->dropForeign(['away_team_id']);
        });

        Schema::table('fixtures', function (Blueprint $table) {
            $table->unsignedBigInteger('home_team_id')->nullable()->change();
            $table->unsignedBigInteger('away_team_id')->nullable()->change();
            $table->string('home_opponent_name')->nullable()->after('home_team_id');
            $table->string('away_opponent_name')->nullable()->after('away_team_id');

            $table->foreign('home_team_id')->references('id')->on('teams')->nullOnDelete();
            $table->foreign('away_team_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropForeign(['home_team_id']);
            $table->dropForeign(['away_team_id']);
            $table->dropColumn(['home_opponent_name', 'away_opponent_name']);
        });

        Schema::table('fixtures', function (Blueprint $table) {
            $table->unsignedBigInteger('home_team_id')->nullable(false)->change();
            $table->unsignedBigInteger('away_team_id')->nullable(false)->change();

            $table->foreign('home_team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('away_team_id')->references('id')->on('teams')->cascadeOnDelete();
        });
    }
};
