<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 80)->default('poultry');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('registration_number')->nullable();
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('currency', 10)->default('NGN');
            $table->string('timezone', 80)->default('Africa/Lagos');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->nullable()->constrained('farms')->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 80);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->unique(['farm_id', 'key']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('name');
            $table->string('module_key', 80)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('farm_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('status', 30)->default('active')->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->unique(['farm_id', 'user_id']);
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->boolean('allowed')->default(true);
            $table->timestamps();
            $table->unique(['farm_id', 'user_id', 'permission_id']);
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['farm_id', 'email']);
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version', 30)->default('1.0.0');
            $table->boolean('is_core')->default(false);
            $table->boolean('enabled')->default(true)->index();
            $table->json('dependencies')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['farm_id', 'module_id']);
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('batch_number');
            $table->string('production_type', 80)->default('broiler')->index();
            $table->string('breed')->nullable();
            $table->date('date_in');
            $table->unsignedInteger('initial_birds');
            $table->unsignedInteger('current_birds');
            $table->decimal('purchase_cost', 14, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->string('source')->nullable();
            $table->unsignedInteger('expected_cycle_days')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['farm_id', 'batch_number']);
            $table->index(['farm_id', 'status']);
        });

        Schema::create('feature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 80)->default('general');
            $table->text('description');
            $table->text('problem')->nullable();
            $table->text('suggested_solution')->nullable();
            $table->string('priority', 30)->default('normal');
            $table->string('status', 40)->default('submitted')->index();
            $table->string('attachment_path')->nullable();
            $table->boolean('contact_permission')->default(true);
            $table->timestamps();
            $table->index(['farm_id', 'status']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120)->index();
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['farm_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('feature_requests');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('farm_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('farms');
    }
};
