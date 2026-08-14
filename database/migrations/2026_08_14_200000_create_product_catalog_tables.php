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
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'id']);
            $table->index(['company_id', 'parent_id']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'id']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('short_name')->unique();
            $table->unsignedTinyInteger('precision')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('category_id')->nullable();
            $table->uuid('brand_id')->nullable();
            $table->uuid('unit_id');
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4)->default(0);
            $table->decimal('wholesale_price', 15, 4)->nullable();
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('minimum_stock', 15, 4)->default(0);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'id']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'category_id', 'is_active']);
            $table->index(['company_id', 'brand_id', 'is_active']);
        });

        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('company_id');
            $table->string('barcode');
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign(['company_id', 'product_id'])->references(['company_id', 'id'])->on('products')->cascadeOnDelete();
            $table->unique(['company_id', 'barcode']);
            $table->index(['product_id', 'is_primary']);
            $table->index(['company_id', 'barcode', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
