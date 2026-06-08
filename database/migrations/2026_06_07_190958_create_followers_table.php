<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_identifier')->nullable();
            $table->foreignId('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('fixture_id')->nullable()->constrained('fixtures')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'club_id']);
            $table->index(['user_id', 'fixture_id']);
            $table->index('guest_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followers');
    }
};
