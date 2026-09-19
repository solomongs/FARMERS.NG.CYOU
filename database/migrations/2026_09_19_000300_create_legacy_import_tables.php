<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egg_production_records', function (Blueprint $table) {
            $table->unsignedInteger('age_days')->nullable()->after('record_date');
        });

        Schema::create('farm_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('key', 120);
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['farm_id', 'key']);
        });

        Schema::create('legacy_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $table->string('source', 80)->default('poultryplus-v2');
            $table->string('source_farm_id')->nullable()->index();
            $table->string('source_farm_name')->nullable();
            $table->string('file_hash', 64);
            $table->string('archive_path')->nullable();
            $table->unsignedInteger('source_version')->nullable();
            $table->timestamp('source_exported_at')->nullable();
            $table->string('status', 30)->default('processing')->index();
            $table->json('counts')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['farm_id', 'file_hash']);
        });

        Schema::create('legacy_record_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_import_id')->constrained('legacy_imports')->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('store_name', 100);
            $table->string('legacy_id', 191);
            $table->string('target_table', 100);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('status', 30)->default('imported');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['farm_id', 'store_name', 'legacy_id'], 'legacy_map_unique');
            $table->index(['target_table', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_record_maps');
        Schema::dropIfExists('legacy_imports');
        Schema::dropIfExists('farm_settings');

        Schema::table('egg_production_records', function (Blueprint $table) {
            $table->dropColumn('age_days');
        });
    }
};
