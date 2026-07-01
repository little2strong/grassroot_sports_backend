<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->json('home_opponent_players')->nullable()->after('home_opponent_name');
            $table->json('away_opponent_players')->nullable()->after('away_opponent_name');
            $table->foreignId('scorer_user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('scorer_assigned_at')->nullable()->after('scorer_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scorer_user_id');
            $table->dropColumn(['home_opponent_players', 'away_opponent_players', 'scorer_assigned_at']);
        });
    }
};
