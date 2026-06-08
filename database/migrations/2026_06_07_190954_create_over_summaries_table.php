<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('over_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('innings_id')->constrained('innings')->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->unsignedSmallInteger('over_number');
            $table->foreignId('bowler_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('bowling_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('runs')->default(0);
            $table->unsignedTinyInteger('wickets')->default(0);
            $table->unsignedTinyInteger('extras')->default(0);
            $table->boolean('is_maiden')->default(false);
            $table->json('balls')->nullable();
            $table->timestamps();

            $table->unique(['innings_id', 'over_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('over_summaries');
    }
};
