<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('discount_eligible')->default(false)->after('tax_rate');
            $table->decimal('normal_discount_price', 15, 4)->nullable()->after('discount_eligible');
            $table->decimal('vip_discount_price', 15, 4)->nullable()->after('normal_discount_price');
            $table->decimal('vvip_discount_price', 15, 4)->nullable()->after('vip_discount_price');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->string('discount_tier', 10)->default('normal')->after('is_walk_in');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('discount_tier');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['discount_eligible', 'normal_discount_price', 'vip_discount_price', 'vvip_discount_price']);
        });
    }
};
