<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixture_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('imported_by')->constrained('users')->cascadeOnDelete();
            $table->enum('source_type', ['image', 'pdf', 'excel', 'csv']);
            $table->string('file_path');
            $table->string('original_filename');
            $table->enum('status', [
                'queued', 'processing', 'completed', 'failed', 'partial',
            ])->default('queued');
            $table->json('extracted_data')->nullable();
            $table->json('parsed_fixtures')->nullable();
            $table->json('errors')->nullable();
            $table->unsignedSmallInteger('total_extracted')->default(0);
            $table->unsignedSmallInteger('total_imported')->default(0);
            $table->unsignedSmallInteger('total_failed')->default(0);
            $table->timestamp('started_processing_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['club_id', 'status']);
            $table->index('imported_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixture_imports');
    }
};
