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
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('type');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->unique(['company_id', 'type']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('credit_limit', 15, 4)->nullable();
            $table->unsignedInteger('payment_terms_days')->nullable();
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'id']);
            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('supplier_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('supplier_id');
            $table->string('type');
            $table->decimal('amount', 15, 4);
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'supplier_id'])->references(['company_id', 'id'])->on('suppliers')->restrictOnDelete();
            $table->index(['company_id', 'supplier_id', 'occurred_at']);
            $table->index(['company_id', 'supplier_id', 'type']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->decimal('credit_limit', 15, 4)->nullable();
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_walk_in')->default(false);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'id']);
            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'is_walk_in']);
        });

        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('customer_id');
            $table->string('type');
            $table->decimal('amount', 15, 4);
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'customer_id'])->references(['company_id', 'id'])->on('customers')->restrictOnDelete();
            $table->index(['company_id', 'customer_id', 'occurred_at']);
            $table->index(['company_id', 'customer_id', 'type']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('supplier_id');
            $table->string('purchase_number');
            $table->string('supplier_invoice_number')->nullable();
            $table->string('status');
            $table->date('purchase_date');
            $table->date('expected_date')->nullable();
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('discount_total', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('shipping_total', 15, 4)->default(0);
            $table->decimal('other_cost_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            $table->decimal('paid_total', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('received_by')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'branch_id'])->references(['company_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['company_id', 'warehouse_id'])->references(['company_id', 'id'])->on('warehouses')->restrictOnDelete();
            $table->foreign(['company_id', 'supplier_id'])->references(['company_id', 'id'])->on('suppliers')->restrictOnDelete();
            $table->unique(['company_id', 'purchase_number']);
            $table->unique(['company_id', 'id']);
            $table->index(['company_id', 'status', 'purchase_date']);
            $table->index(['company_id', 'warehouse_id', 'purchase_date']);
            $table->index(['company_id', 'supplier_id', 'purchase_date']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_id');
            $table->uuid('company_id');
            $table->uuid('product_id');
            $table->string('description')->nullable();
            $table->decimal('ordered_quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases')->restrictOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign(['company_id', 'product_id'])->references(['company_id', 'id'])->on('products')->restrictOnDelete();
            $table->index(['purchase_id', 'product_id']);
            $table->index(['company_id', 'product_id']);
        });

        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('purchase_id');
            $table->uuid('supplier_id');
            $table->string('payment_method');
            $table->decimal('amount', 15, 4);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('purchase_id')->references('id')->on('purchases')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'purchase_id'])->references(['company_id', 'id'])->on('purchases')->restrictOnDelete();
            $table->foreign(['company_id', 'supplier_id'])->references(['company_id', 'id'])->on('suppliers')->restrictOnDelete();
            $table->index(['company_id', 'supplier_id', 'paid_at']);
            $table->index(['company_id', 'purchase_id', 'paid_at']);
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('purchase_id');
            $table->uuid('warehouse_id');
            $table->uuid('supplier_id');
            $table->string('purchase_return_number');
            $table->date('return_date');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('purchase_id')->references('id')->on('purchases')->restrictOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'purchase_id'])->references(['company_id', 'id'])->on('purchases')->restrictOnDelete();
            $table->foreign(['company_id', 'warehouse_id'])->references(['company_id', 'id'])->on('warehouses')->restrictOnDelete();
            $table->foreign(['company_id', 'supplier_id'])->references(['company_id', 'id'])->on('suppliers')->restrictOnDelete();
            $table->unique(['company_id', 'purchase_return_number']);
            $table->index(['company_id', 'purchase_id', 'return_date']);
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_return_id');
            $table->uuid('purchase_item_id');
            $table->uuid('company_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();

            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->restrictOnDelete();
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->restrictOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign(['company_id', 'product_id'])->references(['company_id', 'id'])->on('products')->restrictOnDelete();
            $table->index(['purchase_return_id', 'product_id']);
            $table->index(['purchase_item_id']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('warehouse_id');
            $table->uuid('customer_id')->nullable();
            $table->string('sale_number');
            $table->string('status');
            $table->string('client_transaction_uuid')->nullable();
            $table->date('sale_date');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('discount_total', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            $table->decimal('paid_total', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'branch_id'])->references(['company_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['company_id', 'warehouse_id'])->references(['company_id', 'id'])->on('warehouses')->restrictOnDelete();
            $table->foreign(['company_id', 'customer_id'])->references(['company_id', 'id'])->on('customers')->restrictOnDelete();
            $table->unique(['company_id', 'sale_number']);
            $table->unique(['company_id', 'client_transaction_uuid']);
            $table->unique(['company_id', 'id']);
            $table->index(['company_id', 'status', 'sale_date']);
            $table->index(['company_id', 'warehouse_id', 'sale_date']);
            $table->index(['company_id', 'customer_id', 'sale_date']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id');
            $table->uuid('company_id');
            $table->uuid('product_id');
            $table->string('description');
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->restrictOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign(['company_id', 'product_id'])->references(['company_id', 'id'])->on('products')->restrictOnDelete();
            $table->index(['sale_id', 'product_id']);
            $table->index(['company_id', 'product_id']);
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('sale_id');
            $table->string('payment_method');
            $table->decimal('amount', 15, 4);
            $table->decimal('amount_tendered', 15, 4)->nullable();
            $table->decimal('change_due', 15, 4)->default(0);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'sale_id'])->references(['company_id', 'id'])->on('sales')->restrictOnDelete();
            $table->index(['company_id', 'sale_id', 'paid_at']);
        });

        Schema::create('customer_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('customer_id');
            $table->uuid('sale_id')->nullable();
            $table->string('payment_method');
            $table->decimal('amount', 15, 4);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'customer_id'])->references(['company_id', 'id'])->on('customers')->restrictOnDelete();
            $table->foreign(['company_id', 'sale_id'])->references(['company_id', 'id'])->on('sales')->restrictOnDelete();
            $table->index(['company_id', 'customer_id', 'paid_at']);
            $table->index(['company_id', 'sale_id', 'paid_at']);
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('sale_id');
            $table->uuid('warehouse_id');
            $table->uuid('customer_id')->nullable();
            $table->string('sale_return_number');
            $table->date('return_date');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            $table->string('refund_status')->default('pending');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->restrictOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'sale_id'])->references(['company_id', 'id'])->on('sales')->restrictOnDelete();
            $table->foreign(['company_id', 'warehouse_id'])->references(['company_id', 'id'])->on('warehouses')->restrictOnDelete();
            $table->foreign(['company_id', 'customer_id'])->references(['company_id', 'id'])->on('customers')->restrictOnDelete();
            $table->unique(['company_id', 'sale_return_number']);
            $table->index(['company_id', 'sale_id', 'return_date']);
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_return_id');
            $table->uuid('sale_item_id');
            $table->uuid('company_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();

            $table->foreign('sale_return_id')->references('id')->on('sale_returns')->restrictOnDelete();
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->restrictOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign(['company_id', 'product_id'])->references(['company_id', 'id'])->on('products')->restrictOnDelete();
            $table->index(['sale_return_id', 'product_id']);
            $table->index(['sale_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('customer_transactions');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('supplier_transactions');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('document_sequences');
    }
};
