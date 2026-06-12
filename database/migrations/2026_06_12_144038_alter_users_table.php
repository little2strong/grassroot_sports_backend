<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');

            $table->string('phone')->unique()->after('email');

            $table->enum('user_type', [
                'player',
                'club'
            ])->nullable();

            $table->string('image')->nullable();

            $table->boolean('is_onboarded')->default(false);

            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
