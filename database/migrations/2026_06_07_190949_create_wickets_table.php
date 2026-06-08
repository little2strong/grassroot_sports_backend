<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ball_event_id')->nullable()->constrained('ball_events')->nullOnDelete();
            $table->foreignId('innings_id')->constrained('innings')->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('fixture_id')->constrained('fixtures')->cascadeOnDelete();
            $table->foreignId('dismissed_batter_id')->constrained('users')->cascadeOnDelete();
            $table->enum('dismissal_type', [
                'bowled', 'caught', 'lbw', 'run_out', 'stumped',
                'hit_wicket', 'caught_and_bowled', 'retired', 'retired_hurt',
                'obstructing_field', 'hit_ball_twice', 'timed_out',
            ]);
            $table->foreignId('bowler_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('fielder_one_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('fielder_two_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('runs_at_dismissal')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['innings_id', 'dismissed_batter_id']);
            $table->index(['match_id', 'bowler_id']);
            $table->index('dismissal_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wickets');
    }
};
