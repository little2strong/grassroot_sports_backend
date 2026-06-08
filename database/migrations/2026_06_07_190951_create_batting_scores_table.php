<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batting_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('innings_id')->constrained('innings')->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('fixture_id')->constrained('fixtures')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('batting_order');
            $table->boolean('is_on_strike')->default(false);
            $table->boolean('has_batted')->default(false);
            $table->unsignedSmallInteger('runs')->default(0);
            $table->unsignedSmallInteger('balls_faced')->default(0);
            $table->unsignedSmallInteger('fours')->default(0);
            $table->unsignedSmallInteger('sixes')->default(0);
            $table->boolean('is_out')->default(false);
            $table->enum('dismissal_type', [
                'bowled', 'caught', 'lbw', 'run_out', 'stumped',
                'hit_wicket', 'caught_and_bowled', 'retired', 'retired_hurt',
                'not_out', 'absent_hurt', 'obstructing_field',
                'hit_ball_twice', 'timed_out', null,
            ])->nullable();
            $table->foreignId('dismissed_by_bowler_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('caught_by_fielder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('run_out_by_fielder_one_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('run_out_by_fielder_two_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('stumped_by_keeper_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('wicket_id')->nullable();
            $table->text('dismissal_description')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['innings_id', 'user_id']);
            $table->index(['innings_id', 'batting_order']);
            $table->index(['match_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batting_scores');
    }
};
