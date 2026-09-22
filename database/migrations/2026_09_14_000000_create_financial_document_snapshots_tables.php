<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_document_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('document_type', 160);
            $table->uuid('document_id');
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('document_kind', 60);
            $table->json('snapshot');
            $table->char('checksum', 64);
            $table->unsignedSmallInteger('signing_key_version')->default(1);
            $table->boolean('is_reconstructed')->default(false);
            $table->timestamp('finalized_at')->index();
            $table->uuid('finalized_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('finalized_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['document_type', 'document_id', 'version'], 'financial_document_snapshot_version_unique');
            $table->index(['company_id', 'document_kind', 'finalized_at'], 'financial_document_snapshot_lookup');
        });

        Schema::create('financial_document_audit_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('document_type', 160);
            $table->uuid('document_id');
            $table->string('action', 40);
            $table->unsignedInteger('print_number')->nullable();
            $table->uuid('actor_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['document_type', 'document_id', 'occurred_at'], 'financial_document_audit_lookup');
            $table->unique(['document_type', 'document_id', 'print_number'], 'financial_document_audit_print_unique');
        });

        Schema::table('receipt_print_events', function (Blueprint $table): void {
            $table->string('printed_by_name')->nullable()->after('printed_by');
        });
    }

    public function down(): void
    {
        Schema::table('receipt_print_events', function (Blueprint $table): void {
            $table->dropColumn('printed_by_name');
        });

        Schema::dropIfExists('financial_document_audit_events');
        Schema::dropIfExists('financial_document_snapshots');
    }
};
