<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->enum('match_type', [
                't10', 't20', 'odi_50', 'odi_40', 'test', 'custom',
            ])->default('t20');
            $table->unsignedSmallInteger('overs_per_innings')->default(20);
            $table->enum('ball_type', ['leather', 'tennis', 'tape'])->default('leather');
            $table->enum('status', [
                'draft', 'published', 'live', 'paused',
                'completed', 'abandoned', 'cancelled', 'postponed',
            ])->default('draft');
            $table->foreignId('toss_winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->enum('toss_decision', ['bat', 'bowl'])->nullable();
            $table->enum('result_type', [
                'runs', 'wickets', 'tie', 'dl_method',
                'draw', 'no_result', 'cancelled',
            ])->nullable();
            $table->unsignedSmallInteger('result_margin')->nullable();
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('man_of_the_match_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('result_description')->nullable();
            $table->unsignedSmallInteger('home_team_runs')->default(0);
            $table->unsignedSmallInteger('home_team_wickets')->default(0);
            $table->decimal('home_team_overs', 5, 1)->default(0);
            $table->unsignedSmallInteger('away_team_runs')->default(0);
            $table->unsignedSmallInteger('away_team_wickets')->default(0);
            $table->decimal('away_team_overs', 5, 1)->default(0);
            $table->boolean('is_public')->default(true);
            $table->string('public_share_slug')->unique()->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('scheduled_date');
            $table->index('status');
            $table->index('match_type');
            $table->index('is_public');
            $table->index(['scheduled_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
