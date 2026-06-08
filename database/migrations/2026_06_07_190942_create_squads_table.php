<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained('fixtures')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('position', ['playing_xi', 'reserve', 'twelfth_man'])->default('playing_xi');
            $table->smallInteger('jersey_number')->nullable();
            $table->boolean('is_captain')->default(false);
            $table->boolean('is_wicket_keeper')->default(false);
            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['fixture_id', 'team_id', 'user_id']);
            $table->index(['fixture_id', 'team_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squads');
    }
};
