<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_print_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('sale_id');
            $table->uuid('printed_by')->nullable();
            $table->unsignedInteger('reprint_number');
            $table->string('format', 20);
            $table->timestamp('printed_at')->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->restrictOnDelete();
            $table->foreign('printed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign(['company_id', 'sale_id'])->references(['company_id', 'id'])->on('sales')->restrictOnDelete();
            $table->unique(['sale_id', 'reprint_number']);
            $table->index(['company_id', 'sale_id', 'printed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_print_events');
    }
};
