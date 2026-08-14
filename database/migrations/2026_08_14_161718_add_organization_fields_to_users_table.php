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
            $table->uuid('company_id')->nullable()->after('id');
            $table->uuid('branch_id')->nullable()->after('company_id');
            $table->uuid('warehouse_id')->nullable()->after('branch_id');

            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign(['company_id', 'branch_id'])->references(['company_id', 'id'])->on('branches')->nullOnDelete();
            $table->foreign(['company_id', 'warehouse_id'])->references(['company_id', 'id'])->on('warehouses')->nullOnDelete();

            $table->index(['company_id', 'is_active']);
            $table->index(['branch_id', 'is_active']);
            $table->index(['warehouse_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['company_id', 'branch_id']);
            $table->dropForeign(['company_id', 'warehouse_id']);
            $table->dropIndex(['company_id', 'is_active']);
            $table->dropIndex(['branch_id', 'is_active']);
            $table->dropIndex(['warehouse_id', 'is_active']);
            $table->dropColumn(['company_id', 'branch_id', 'warehouse_id']);
        });
    }
};
