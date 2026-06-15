<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            if (!Schema::hasColumn('clubs', 'short_name')) {
                $table->string('short_name', 10)->nullable()->after('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            if (Schema::hasColumn('clubs', 'short_name')) {
                $table->dropColumn('short_name');
            }
        });
    }
};
