<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('description')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->string('founded_year', 4)->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('hide_player_names_publicly')->default(false);
            $table->boolean('hide_player_photos_publicly')->default(false);
            $table->boolean('show_public_profiles')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('country');
            $table->index('city');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
