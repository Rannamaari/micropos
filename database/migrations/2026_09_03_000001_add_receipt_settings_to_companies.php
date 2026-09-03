<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('receipt_shop_name')->nullable()->after('name');
            $table->string('receipt_gst_label')->default('GST No.')->after('tax_number');
            $table->text('receipt_header')->nullable()->after('address');
            $table->text('receipt_footer')->nullable()->after('receipt_header');
            $table->boolean('receipt_show_address')->default(true)->after('receipt_footer');
            $table->boolean('receipt_show_phone')->default(true)->after('receipt_show_address');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'receipt_shop_name', 'receipt_gst_label', 'receipt_header', 'receipt_footer',
                'receipt_show_address', 'receipt_show_phone',
            ]);
        });
    }
};
