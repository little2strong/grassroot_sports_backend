<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('batting_style', ['right_hand', 'left_hand'])->nullable();
            $table->enum('bowling_style', [
                'right_arm_fast', 'right_arm_fast_medium', 'right_arm_medium',
                'right_arm_off_break', 'right_arm_leg_break',
                'left_arm_fast', 'left_arm_fast_medium', 'left_arm_medium',
                'left_arm_orthodox', 'left_arm_chinaman',
            ])->nullable();
            $table->enum('primary_role', [
                'batsman', 'bowler', 'all_rounder', 'wicket_keeper',
            ])->default('all_rounder');
            $table->text('bio')->nullable();
            $table->unsignedInteger('total_matches')->default(0);
            $table->unsignedInteger('total_runs')->default(0);
            $table->unsignedInteger('total_wickets')->default(0);
            $table->unsignedInteger('highest_score')->default(0);
            $table->unsignedInteger('total_fifties')->default(0);
            $table->unsignedInteger('total_hundreds')->default(0);
            $table->unsignedInteger('total_five_wickets')->default(0);
            $table->boolean('is_public_profile')->default(true);
            $table->timestamps();

            $table->index('primary_role');
            $table->index('batting_style');
            $table->index('bowling_style');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};
