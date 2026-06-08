<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ball_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('innings_id')->constrained('innings')->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('fixture_id')->constrained('fixtures')->cascadeOnDelete();
            $table->unsignedSmallInteger('over_number');
            $table->unsignedTinyInteger('ball_number');
            $table->unsignedInteger('ball_sequence');
            $table->unsignedInteger('legal_ball_sequence');
            $table->foreignId('striker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('non_striker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('bowler_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('batting_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('bowling_team_id')->constrained('teams')->cascadeOnDelete();
            $table->enum('event_type', [
                'dot', 'run', 'wide', 'no_ball',
                'bye', 'leg_bye', 'wicket', 'penalty', 'retired', 'combo',
            ]);
            $table->unsignedTinyInteger('runs_scored')->default(0);
            $table->unsignedTinyInteger('total_runs')->default(0);
            $table->boolean('is_boundary_four')->default(false);
            $table->boolean('is_boundary_six')->default(false);
            $table->enum('extras_type', [
                'wide', 'no_ball', 'bye', 'leg_bye', 'penalty', null,
            ])->nullable();
            $table->unsignedTinyInteger('extras_runs')->default(0);
            $table->boolean('is_legal_delivery')->default(true);
            $table->boolean('is_wicket_ball')->default(false);
            $table->unsignedBigInteger('wicket_id')->nullable();
            $table->enum('no_ball_type', [
                'overstepping', 'high_full_toss', 'above_waist',
                'bounce_above_shoulders', null,
            ])->nullable();
            $table->boolean('is_wide_plus_boundary')->default(false);
            $table->text('commentary')->nullable();
            $table->text('scorer_notes')->nullable();
            $table->boolean('is_undo')->default(false);
            $table->foreignId('replaced_ball_event_id')->nullable()->constrained('ball_events')->nullOnDelete();
            $table->uuid('offline_uuid')->nullable()->unique();
            $table->boolean('is_synced')->default(true);
            $table->timestamps();

            $table->unique(['innings_id', 'ball_sequence']);
            $table->index(['match_id', 'over_number', 'ball_number']);
            $table->index(['innings_id', 'over_number']);
            $table->index('striker_id');
            $table->index('bowler_id');
            $table->index('event_type');
            $table->index('is_legal_delivery');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ball_events');
    }
};
