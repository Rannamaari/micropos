<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('cancellation_reason')->nullable()->after('notes');
            $table->text('cancellation_notes')->nullable()->after('cancellation_reason');
            $table->uuid('cancelled_by')->nullable()->after('created_by');
            $table->timestamp('cancelled_at')->nullable()->after('voided_at');

            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'status', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'status', 'cancelled_at']);
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn([
                'cancellation_reason',
                'cancellation_notes',
                'cancelled_by',
                'cancelled_at',
            ]);
        });
    }
};
