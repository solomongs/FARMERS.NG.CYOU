<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('record_date');
            $table->unsignedInteger('age_days')->nullable();
            $table->decimal('feed_consumed', 12, 3)->default(0);
            $table->decimal('water_consumed', 12, 3)->default(0);
            $table->unsignedInteger('mortality')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['farm_id', 'batch_id', 'record_date']);
        });

        Schema::create('feed_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('record_date');
            $table->unsignedInteger('age_days')->nullable();
            $table->string('feed_type');
            $table->decimal('quantity', 12, 3);
            $table->string('unit', 30)->default('kg');
            $table->decimal('cost_per_unit', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['farm_id', 'batch_id', 'record_date']);
        });

        Schema::create('medication_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('record_date');
            $table->unsignedInteger('age_days')->nullable();
            $table->string('name');
            $table->string('program_type', 50)->default('medication');
            $table->string('route')->nullable();
            $table->string('dosage')->nullable();
            $table->string('status', 30)->default('scheduled')->index();
            $table->string('administered_by')->nullable();
            $table->date('next_due_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['farm_id', 'batch_id', 'record_date']);
        });

        Schema::create('mortality_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('record_date');
            $table->unsignedInteger('age_days')->nullable();
            $table->unsignedInteger('number_dead');
            $table->string('suspected_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['farm_id', 'batch_id', 'record_date']);
        });

        Schema::create('egg_production_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('record_date');
            $table->unsignedInteger('number_of_birds');
            $table->unsignedInteger('eggs_collected');
            $table->unsignedInteger('cracked_eggs')->default(0);
            $table->unsignedInteger('damaged_eggs')->default(0);
            $table->unsignedInteger('saleable_eggs')->default(0);
            $table->decimal('production_rate', 8, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['farm_id', 'batch_id', 'record_date']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('sale_date');
            $table->string('item');
            $table->string('category', 80)->nullable();
            $table->decimal('quantity', 12, 3);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total_revenue', 14, 2);
            $table->string('buyer_name')->nullable();
            $table->string('buyer_contact')->nullable();
            $table->string('payment_status', 30)->default('paid');
            $table->string('payment_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['farm_id', 'sale_date']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('expense_date');
            $table->string('item');
            $table->string('category', 80)->index();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('cost_per_unit', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('vendor')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['farm_id', 'expense_date']);
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('category', 80)->nullable();
            $table->string('unit', 30);
            $table->decimal('balance', 14, 3)->default(0);
            $table->decimal('reorder_level', 14, 3)->default(0);
            $table->string('supplier')->nullable();
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->string('storage_location')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['farm_id', 'sku']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 30);
            $table->decimal('quantity', 14, 3);
            $table->decimal('balance_after', 14, 3);
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['farm_id', 'inventory_item_id', 'created_at']);
        });

        Schema::create('weekly_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('record_date');
            $table->unsignedInteger('week');
            $table->unsignedInteger('sample_size');
            $table->decimal('average_weight', 10, 3);
            $table->decimal('minimum_weight', 10, 3)->nullable();
            $table->decimal('maximum_weight', 10, 3)->nullable();
            $table->decimal('target_weight', 10, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['farm_id', 'batch_id', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_weights');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('egg_production_records');
        Schema::dropIfExists('mortality_records');
        Schema::dropIfExists('medication_records');
        Schema::dropIfExists('feed_records');
        Schema::dropIfExists('daily_records');
    }
};
