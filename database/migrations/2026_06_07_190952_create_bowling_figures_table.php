<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bowling_figures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('innings_id')->constrained('innings')->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('fixture_id')->constrained('fixtures')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->decimal('overs', 5, 1)->default(0);
            $table->unsignedSmallInteger('balls_bowled')->default(0);
            $table->unsignedSmallInteger('maidens')->default(0);
            $table->unsignedSmallInteger('runs_conceded')->default(0);
            $table->unsignedTinyInteger('wickets')->default(0);
            $table->unsignedSmallInteger('wides_bowled')->default(0);
            $table->unsignedSmallInteger('no_balls_bowled')->default(0);
            $table->decimal('economy', 5, 2)->nullable()
                ->storedAs('CASE WHEN overs > 0 THEN ROUND(runs_conceded / overs, 2) ELSE NULL END');
            $table->decimal('strike_rate', 6, 2)->nullable()
                ->storedAs('CASE WHEN wickets > 0 THEN ROUND(balls_bowled * 1.0 / wickets, 2) ELSE NULL END');
            $table->boolean('is_current_bowler')->default(false);
            $table->timestamps();

            $table->unique(['innings_id', 'user_id']);
            $table->index(['match_id', 'user_id']);
            $table->index(['innings_id', 'wickets']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bowling_figures');
    }
};
