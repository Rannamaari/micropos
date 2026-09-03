<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->string('receipt_shop_name')->nullable();
            $table->string('receipt_tax_number')->nullable();
            $table->string('receipt_gst_label')->nullable();
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->boolean('receipt_show_address')->nullable();
            $table->boolean('receipt_show_phone')->nullable();
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->json('receipt_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn('receipt_snapshot');
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn([
                'receipt_shop_name', 'receipt_tax_number', 'receipt_gst_label', 'receipt_header', 'receipt_footer',
                'receipt_show_address', 'receipt_show_phone',
            ]);
        });
    }
};
