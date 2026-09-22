<?php

namespace App\Console\Commands;

use App\Models\CashierShift;
use App\Models\CustomerPayment;
use App\Models\FinancialDocumentSnapshot;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\FinancialDocumentSnapshotService;
use Illuminate\Console\Command;

class BackfillFinancialDocumentSnapshotsCommand extends Command
{
    protected $signature = 'micro-pos:backfill-financial-snapshots {--company= : Limit the backfill to one company UUID} {--dry-run : Report eligible documents without creating snapshots}';

    protected $description = 'Create missing immutable snapshots for existing finalized financial documents without overwriting any snapshot.';

    public function handle(FinancialDocumentSnapshotService $snapshots): int
    {
        $companyId = $this->option('company');
        $dryRun = (bool) $this->option('dry-run');
        $counts = [];

        $this->backfill(Sale::query()->whereIn('status', ['completed', 'partially_refunded', 'refunded']), $companyId, $dryRun, $counts, fn (Sale $sale) => $snapshots->captureSale($sale, $sale->created_by, true));
        $this->backfill(Purchase::query()->whereIn('status', ['partially_received', 'received']), $companyId, $dryRun, $counts, fn (Purchase $purchase) => $snapshots->capturePurchase($purchase, $purchase->received_by ?? $purchase->created_by, true));
        $this->backfill(SaleReturn::query(), $companyId, $dryRun, $counts, fn (SaleReturn $return) => $snapshots->captureSaleReturn($return, $return->created_by, true));
        $this->backfill(PurchaseReturn::query(), $companyId, $dryRun, $counts, fn (PurchaseReturn $return) => $snapshots->capturePurchaseReturn($return, $return->created_by, true));
        $this->backfill(PurchasePayment::query(), $companyId, $dryRun, $counts, fn (PurchasePayment $payment) => $snapshots->capturePurchasePayment($payment, $payment->created_by, true));
        $this->backfill(CustomerPayment::query(), $companyId, $dryRun, $counts, fn (CustomerPayment $payment) => $snapshots->captureCustomerPayment($payment, $payment->created_by, true));
        $this->backfill(CashierShift::query()->where('status', 'closed'), $companyId, $dryRun, $counts, fn (CashierShift $shift) => $snapshots->captureCashierShift($shift, $shift->cashier_id, true));

        foreach ($counts as $type => $count) {
            $this->line("{$type}: {$count}");
        }

        $this->warn($dryRun
            ? 'Dry run only. No snapshots were created.'
            : 'Snapshots were reconstructed from the best currently available records. They are marked reconstructed and are not claimed to be historically exact where master data had already changed.');

        return self::SUCCESS;
    }

    private function backfill($query, ?string $companyId, bool $dryRun, array &$counts, callable $capture): void
    {
        $query->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('id')
            ->each(function ($document) use ($dryRun, &$counts, $capture): void {
                $exists = FinancialDocumentSnapshot::query()
                    ->where('document_type', $document::class)
                    ->where('document_id', $document->id)
                    ->where('version', 1)
                    ->exists();

                if ($exists) {
                    return;
                }

                $counts[class_basename($document)] = ($counts[class_basename($document)] ?? 0) + 1;

                if (! $dryRun) {
                    $capture($document);
                }
            });
    }
}
