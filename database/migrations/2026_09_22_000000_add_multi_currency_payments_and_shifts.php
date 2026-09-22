<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->string('secondary_currency', 3)->nullable()->after('currency');
            $table->decimal('secondary_currency_rate', 18, 8)->nullable()->after('secondary_currency');
        });

        Schema::table('sale_payments', function (Blueprint $table): void {
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency');
            $table->decimal('currency_amount', 15, 4)->nullable()->after('exchange_rate');
        });

        Schema::table('cashier_shifts', function (Blueprint $table): void {
            $table->json('opening_cash_by_currency')->nullable()->after('opening_cash');
            $table->json('expected_cash_by_currency')->nullable()->after('expected_cash');
            $table->json('closing_cash_by_currency')->nullable()->after('closing_cash');
            $table->json('cash_variance_by_currency')->nullable()->after('cash_variance');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table): void {
            $table->dropColumn([
                'opening_cash_by_currency',
                'expected_cash_by_currency',
                'closing_cash_by_currency',
                'cash_variance_by_currency',
            ]);
        });

        Schema::table('sale_payments', function (Blueprint $table): void {
            $table->dropColumn(['exchange_rate', 'currency_amount']);
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn(['secondary_currency', 'secondary_currency_rate']);
        });
    }
};
