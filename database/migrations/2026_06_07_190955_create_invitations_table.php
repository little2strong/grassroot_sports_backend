<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('invited_email');
            $table->string('invited_phone', 20)->nullable();
            $table->foreignId('invited_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('role', [
                'admin', 'captain', 'manager', 'scorer', 'player',
            ]);
            $table->string('token', 64)->unique();
            $table->enum('status', [
                'pending', 'accepted', 'rejected', 'expired', 'cancelled',
            ])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['invited_email', 'status']);
            $table->index(['club_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
