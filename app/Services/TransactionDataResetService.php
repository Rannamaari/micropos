<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class TransactionDataResetService
{
    /** @return array<string, int> */
    public function resetSales(string $companyId): array
    {
        return DB::transaction(function () use ($companyId): array {
            $sales = DB::table('sales')->where('company_id', $companyId)->pluck('id');
            $saleReturns = DB::table('sale_returns')->where('company_id', $companyId)->pluck('id');
            $saleItems = DB::table('sale_items')->where('company_id', $companyId)->pluck('id');
            $customerPayments = DB::table('customer_payments')->where('company_id', $companyId)->whereIn('sale_id', $sales)->pluck('id');

            DB::table('receipt_print_events')->where('company_id', $companyId)->whereIn('sale_id', $sales)->delete();
            DB::table('stock_movements')->where('company_id', $companyId)->whereIn('reference_type', [Sale::class, SaleReturn::class])->delete();
            DB::table('customer_transactions')->where('company_id', $companyId)->where(function ($query) use ($customerPayments): void {
                $query->whereIn('reference_type', [Sale::class, SaleReturn::class])
                    ->orWhere(fn ($nested) => $nested->where('reference_type', CustomerPayment::class)->whereIn('reference_id', $customerPayments));
            })->delete();
            DB::table('customer_payments')->where('company_id', $companyId)->whereIn('id', $customerPayments)->delete();
            DB::table('sale_return_items')->where('company_id', $companyId)->whereIn('sale_return_id', $saleReturns)->delete();
            DB::table('sale_returns')->where('company_id', $companyId)->whereIn('id', $saleReturns)->delete();
            DB::table('sale_payments')->where('company_id', $companyId)->whereIn('sale_id', $sales)->delete();
            DB::table('sale_items')->where('company_id', $companyId)->whereIn('id', $saleItems)->delete();
            DB::table('sales')->where('company_id', $companyId)->whereIn('id', $sales)->delete();
            DB::table('document_sequences')->where('company_id', $companyId)->whereIn('type', ['sale', 'sale_return'])->delete();

            $this->rebuildInventoryBalances($companyId);

            return ['sales' => $sales->count(), 'sale_returns' => $saleReturns->count()];
        });
    }

    /** @return array<string, int> */
    public function resetAllTransactions(string $companyId): array
    {
        return DB::transaction(function () use ($companyId): array {
            $sales = $this->resetSales($companyId);
            $purchaseReturns = DB::table('purchase_returns')->where('company_id', $companyId)->pluck('id');
            $purchases = DB::table('purchases')->where('company_id', $companyId)->pluck('id');
            $purchaseItems = DB::table('purchase_items')->where('company_id', $companyId)->pluck('id');
            $stockCounts = DB::table('stock_counts')->where('company_id', $companyId)->pluck('id');

            DB::table('purchase_return_items')->where('company_id', $companyId)->whereIn('purchase_return_id', $purchaseReturns)->delete();
            DB::table('purchase_payments')->where('company_id', $companyId)->whereIn('purchase_id', $purchases)->delete();
            DB::table('purchase_returns')->where('company_id', $companyId)->whereIn('id', $purchaseReturns)->delete();
            DB::table('purchase_items')->where('company_id', $companyId)->whereIn('id', $purchaseItems)->delete();
            DB::table('purchases')->where('company_id', $companyId)->whereIn('id', $purchases)->delete();
            DB::table('supplier_transactions')->where('company_id', $companyId)->delete();
            DB::table('customer_payments')->where('company_id', $companyId)->delete();
            DB::table('customer_transactions')->where('company_id', $companyId)->delete();
            DB::table('stock_count_items')->where('company_id', $companyId)->whereIn('stock_count_id', $stockCounts)->delete();
            DB::table('stock_counts')->where('company_id', $companyId)->whereIn('id', $stockCounts)->delete();
            DB::table('stock_movements')->where('company_id', $companyId)->delete();
            DB::table('inventory_balances')->where('company_id', $companyId)->delete();
            DB::table('document_sequences')->where('company_id', $companyId)->whereIn('type', ['purchase', 'sale', 'purchase_return', 'sale_return'])->delete();

            return $sales + ['purchases' => $purchases->count(), 'purchase_returns' => $purchaseReturns->count(), 'stock_counts' => $stockCounts->count()];
        });
    }

    private function rebuildInventoryBalances(string $companyId): void
    {
        DB::table('inventory_balances')->where('company_id', $companyId)->delete();
        $quantities = [];

        StockMovement::query()
            ->where('company_id', $companyId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->each(function (StockMovement $movement) use (&$quantities, $companyId): void {
                $key = $movement->warehouse_id.'|'.$movement->product_id;
                $before = $quantities[$key] ?? 0.0;
                $after = round($before + (float) $movement->quantity, 4);
                $movement->forceFill(['quantity_before' => $before, 'quantity_after' => $after])->save();
                $quantities[$key] = $after;
            });

        foreach ($quantities as $key => $quantity) {
            [$warehouseId, $productId] = explode('|', $key, 2);
            InventoryBalance::query()->create(['company_id' => $companyId, 'warehouse_id' => $warehouseId, 'product_id' => $productId, 'quantity' => $quantity]);
        }
    }
}
