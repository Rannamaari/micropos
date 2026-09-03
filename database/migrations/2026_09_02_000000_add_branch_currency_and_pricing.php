<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->string('currency', 3)->default('MVR')->after('city');
        });

        // Preserve the currency used by existing branches before branch currency takes over.
        DB::table('branches')->orderBy('id')->each(function (object $branch): void {
            $currency = DB::table('companies')->where('id', $branch->company_id)->value('currency') ?: 'MVR';

            DB::table('branches')->where('id', $branch->id)->update(['currency' => $currency]);
        });

        Schema::create('product_branch_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('product_id');
            $table->string('currency', 3);
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4);
            $table->decimal('wholesale_price', 15, 4)->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign(['company_id', 'branch_id'])->references(['company_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['company_id', 'product_id'])->references(['company_id', 'id'])->on('products')->restrictOnDelete();
            $table->unique(['branch_id', 'product_id']);
            $table->index(['company_id', 'branch_id']);
        });

        foreach (['sales', 'purchases'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('currency', 3)->default('MVR')->after('status');
            });
        }

        Schema::table('sale_payments', function (Blueprint $table): void {
            $table->string('currency', 3)->default('MVR')->after('payment_method');
        });

        Schema::table('purchase_payments', function (Blueprint $table): void {
            $table->string('currency', 3)->default('MVR')->after('payment_method');
        });

        Schema::table('customer_transactions', function (Blueprint $table): void {
            $table->string('currency', 3)->default('MVR')->after('amount');
            $table->index(['customer_id', 'currency', 'occurred_at']);
        });

        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->string('currency', 3)->default('MVR')->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('sale_payments', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('customer_payments', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('customer_transactions', function (Blueprint $table): void {
            $table->dropIndex(['customer_id', 'currency', 'occurred_at']);
            $table->dropColumn('currency');
        });
        Schema::table('purchases', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::dropIfExists('product_branch_prices');
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn('currency'));
    }
};
