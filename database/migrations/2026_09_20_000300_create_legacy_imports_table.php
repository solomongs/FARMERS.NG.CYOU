<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('source', 80)->default('poultryplus-static');
            $table->string('original_filename')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('records_detected')->default(0);
            $table->unsignedInteger('records_imported')->default(0);
            $table->unsignedInteger('records_skipped')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->json('summary')->nullable();
            $table->json('errors')->nullable();
            $table->string('checksum', 64)->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'status']);
        });

        Schema::create('legacy_import_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_import_id')->constrained('legacy_imports')->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 80);
            $table->string('source_id', 191);
            $table->string('target_type', 120);
            $table->string('target_id', 191);
            $table->timestamps();

            $table->unique(['farm_id', 'source_type', 'source_id'], 'legacy_map_unique_source');
            $table->index(['legacy_import_id', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_map');
        Schema::dropIfExists('legacy_imports');
    }
};
