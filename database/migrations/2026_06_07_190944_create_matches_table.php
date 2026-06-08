<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->unique()->constrained('fixtures')->cascadeOnDelete();
            $table->unsignedTinyInteger('current_innings_number')->default(1);
            $table->unsignedSmallInteger('current_over_number')->default(0);
            $table->unsignedTinyInteger('current_ball_number')->default(0);
            $table->unsignedSmallInteger('total_legal_deliveries')->default(0);
            $table->boolean('is_paused')->default(false);
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->unsignedBigInteger('first_innings_id')->nullable();
            $table->unsignedBigInteger('second_innings_id')->nullable();
            $table->json('powerplay_config')->nullable();
            $table->enum('current_powerplay', ['pp1', 'pp2', 'pp3', 'none'])->default('none');
            $table->timestamps();

            $table->index('is_paused');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
