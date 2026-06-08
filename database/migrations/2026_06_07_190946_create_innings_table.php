<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('innings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('fixture_id')->constrained('fixtures')->cascadeOnDelete();
            $table->foreignId('batting_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('bowling_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('innings_number');
            $table->unsignedSmallInteger('runs')->default(0);
            $table->unsignedTinyInteger('wickets')->default(0);
            $table->decimal('overs', 5, 1)->default(0);
            $table->unsignedSmallInteger('legal_deliveries')->default(0);
            $table->unsignedSmallInteger('extras_total')->default(0);
            $table->unsignedSmallInteger('wides')->default(0);
            $table->unsignedSmallInteger('no_balls')->default(0);
            $table->unsignedSmallInteger('byes')->default(0);
            $table->unsignedSmallInteger('leg_byes')->default(0);
            $table->unsignedSmallInteger('penalty_runs')->default(0);
            $table->unsignedSmallInteger('target')->nullable();
            $table->foreignId('striker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('non_striker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_bowler_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('result', [
                'all_out', 'overs_completed', 'target_achieved',
                'innings_declared', 'abandoned', 'in_progress',
            ])->default('in_progress');
            $table->text('result_note')->nullable();
            $table->unsignedTinyInteger('total_batters')->default(11);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['match_id', 'innings_number']);
            $table->index('batting_team_id');
            $table->index('bowling_team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('innings');
    }
};
