<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original currency migration may already have run before these
        // ledger fields were introduced, so add them independently for upgrades.
        if (! Schema::hasColumn('customer_transactions', 'currency')) {
            Schema::table('customer_transactions', function (Blueprint $table): void {
                $table->string('currency', 3)->default('MVR')->after('amount');
                $table->index(['customer_id', 'currency', 'occurred_at']);
            });
        }

        if (! Schema::hasColumn('customer_payments', 'currency')) {
            Schema::table('customer_payments', function (Blueprint $table): void {
                $table->string('currency', 3)->default('MVR')->after('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customer_payments', 'currency')) {
            Schema::table('customer_payments', fn (Blueprint $table) => $table->dropColumn('currency'));
        }

        if (Schema::hasColumn('customer_transactions', 'currency')) {
            Schema::table('customer_transactions', function (Blueprint $table): void {
                $table->dropIndex(['customer_id', 'currency', 'occurred_at']);
                $table->dropColumn('currency');
            });
        }
    }
};
