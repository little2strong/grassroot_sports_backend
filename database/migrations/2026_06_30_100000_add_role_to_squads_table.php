<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('squads', function (Blueprint $table) {
            $table->enum('role', [
                'captain',
                'vice_captain',
                'wicketkeeper',
                'batsman',
                'bowler',
                'all_rounder',
            ])->nullable()->after('is_wicket_keeper');
        });
    }

    public function down(): void
    {
        Schema::table('squads', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
