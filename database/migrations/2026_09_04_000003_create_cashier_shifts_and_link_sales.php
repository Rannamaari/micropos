<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('warehouse_id');
            $table->uuid('cashier_id');
            $table->string('shift_number');
            $table->string('currency', 3);
            $table->string('status')->default('open');
            $table->decimal('opening_cash', 15, 4)->default(0);
            $table->decimal('expected_cash', 15, 4)->nullable();
            $table->decimal('closing_cash', 15, 4)->nullable();
            $table->decimal('cash_variance', 15, 4)->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->json('report_snapshot')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('cashier_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign(['company_id', 'branch_id'])->references(['company_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['company_id', 'warehouse_id'])->references(['company_id', 'id'])->on('warehouses')->restrictOnDelete();
            $table->unique(['company_id', 'shift_number']);
            $table->index(['company_id', 'branch_id', 'cashier_id', 'status']);
            $table->index(['company_id', 'closed_at']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->uuid('cashier_shift_id')->nullable()->after('warehouse_id');
            $table->foreign('cashier_shift_id')->references('id')->on('cashier_shifts')->nullOnDelete();
            $table->index(['company_id', 'cashier_shift_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropForeign(['cashier_shift_id']);
            $table->dropIndex(['company_id', 'cashier_shift_id', 'status']);
            $table->dropColumn('cashier_shift_id');
        });

        Schema::dropIfExists('cashier_shifts');
    }
};
