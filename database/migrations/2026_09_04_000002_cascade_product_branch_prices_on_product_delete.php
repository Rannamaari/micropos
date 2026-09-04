<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('product_branch_prices', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['company_id', 'product_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign(['company_id', 'product_id'])->references(['company_id', 'id'])->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('product_branch_prices', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['company_id', 'product_id']);
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign(['company_id', 'product_id'])->references(['company_id', 'id'])->on('products')->restrictOnDelete();
        });
    }
};
