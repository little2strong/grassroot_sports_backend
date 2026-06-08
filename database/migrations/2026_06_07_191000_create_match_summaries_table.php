<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->unique()->constrained('fixtures')->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->json('innings_one')->nullable();
            $table->json('innings_two')->nullable();
            $table->json('key_stats')->nullable();
            $table->text('narrative')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_summaries');
    }
};
