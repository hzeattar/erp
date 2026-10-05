<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contractor_profiles')) {
            Schema::create('contractor_profiles', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email')->nullable();
                $table->boolean('auto_approve_requests')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['company_id', 'is_active']);
                $table->unique(['company_id', 'email']);
            });
        }

        if (! Schema::hasColumn('contractor_warehouses', 'contractor_id')) {
            Schema::table('contractor_warehouses', function (Blueprint $table) {
                $table->unsignedInteger('contractor_id')->nullable()->after('company_id');
                $table->foreign('contractor_id')->references('id')->on('contractor_profiles')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('contractor_project_assignments')) {
            Schema::create('contractor_project_assignments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('contractor_id');
                $table->unsignedInteger('project_id');
                $table->string('responsibility')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('contractor_id')->references('id')->on('contractor_profiles')->cascadeOnDelete();
                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
                $table->unique(['contractor_id', 'project_id']);
            });
        }

        if (! Schema::hasTable('contractor_material_allowances')) {
            Schema::create('contractor_material_allowances', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('contractor_id');
                $table->unsignedInteger('project_id')->nullable();
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('allowed_quantity');
                $table->unsignedInteger('issued_quantity')->default(0);
                $table->boolean('auto_approve')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('contractor_id')->references('id')->on('contractor_profiles')->cascadeOnDelete();
                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->index(['company_id', 'contractor_id', 'project_id'], 'contractor_allowance_scope_idx');
            });
        }

        $allowanceIndex = DB::select("SHOW INDEX FROM contractor_material_allowances WHERE Key_name = 'contractor_allowance_scope_idx'");

        if ($allowanceIndex === []) {
            Schema::table('contractor_material_allowances', function (Blueprint $table) {
                $table->index(['company_id', 'contractor_id', 'project_id'], 'contractor_allowance_scope_idx');
            });
        }

        if (! Schema::hasTable('contractor_inventory_settings')) {
            Schema::create('contractor_inventory_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id')->unique();
                $table->boolean('auto_approve_requests')->default(false);
                $table->unsignedInteger('auto_approve_max_quantity')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('contractor_stock_requests')) {
            Schema::create('contractor_stock_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('contractor_id');
                $table->unsignedInteger('project_id')->nullable();
                $table->unsignedInteger('from_warehouse_id');
                $table->unsignedInteger('to_warehouse_id');
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('requested_quantity');
                $table->unsignedInteger('approved_quantity')->nullable();
                $table->string('status', 20)->default('pending');
                $table->boolean('was_auto_approved')->default(false);
                $table->text('request_notes')->nullable();
                $table->text('decision_notes')->nullable();
                $table->unsignedInteger('requested_by')->nullable();
                $table->unsignedInteger('decided_by')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->unsignedInteger('issued_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('contractor_id')->references('id')->on('contractor_profiles')->restrictOnDelete();
                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
                $table->foreign('from_warehouse_id')->references('id')->on('contractor_warehouses')->restrictOnDelete();
                $table->foreign('to_warehouse_id')->references('id')->on('contractor_warehouses')->restrictOnDelete();
                $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('decided_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['company_id', 'status', 'created_at']);
            });
        }

        if (! Schema::hasTable('contractor_inventory_movements')) {
            Schema::create('contractor_inventory_movements', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('contractor_id')->nullable();
                $table->unsignedInteger('warehouse_id');
                $table->unsignedInteger('project_id')->nullable();
                $table->unsignedInteger('product_id');
                $table->string('movement_type', 20);
                $table->integer('quantity');
                $table->string('reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('stock_request_id')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->date('movement_date');
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('contractor_id')->references('id')->on('contractor_profiles')->nullOnDelete();
                $table->foreign('warehouse_id')->references('id')->on('contractor_warehouses')->restrictOnDelete();
                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
                $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('stock_request_id')->references('id')->on('contractor_stock_requests')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['company_id', 'contractor_id', 'movement_type', 'movement_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_inventory_movements');
        Schema::dropIfExists('contractor_stock_requests');
        Schema::dropIfExists('contractor_inventory_settings');
        Schema::dropIfExists('contractor_material_allowances');
        Schema::dropIfExists('contractor_project_assignments');

        if (Schema::hasColumn('contractor_warehouses', 'contractor_id')) {
            Schema::table('contractor_warehouses', function (Blueprint $table) {
                $table->dropForeign(['contractor_id']);
                $table->dropColumn('contractor_id');
            });
        }

        Schema::dropIfExists('contractor_profiles');
    }
};
