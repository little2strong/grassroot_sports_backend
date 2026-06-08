<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_shortage_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained('fixtures')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->enum('request_type', [
                'unavailability', 'volunteer', 'external_request',
            ])->default('unavailability');
            $table->text('reason')->nullable();
            $table->foreignId('target_fixture_id')->nullable()->constrained('fixtures')->nullOnDelete();
            $table->foreignId('target_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->enum('status', [
                'pending', 'approved', 'rejected', 'fulfilled', 'cancelled',
            ])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['fixture_id', 'team_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_shortage_requests');
    }
};
