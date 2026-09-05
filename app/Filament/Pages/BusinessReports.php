<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminSupport;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessReports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Business Reports';

    protected static ?string $title = 'Business Reports';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.business-reports';

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?string $branchId = null;

    public static function canAccess(): bool
    {
        return (bool) AdminSupport::user()?->can('reports.view');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('nav.reports');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess() && AdminSupport::companyId(), 403);

        $this->dateFrom = today()->subDays(29)->toDateString();
        $this->dateTo = today()->toDateString();
        $this->branchId = AdminSupport::user()?->branch_id ?: $this->branches()->keys()->first();
    }

    public function updatedBranchId(): void
    {
        if (! $this->branchId || ! $this->branches()->has($this->branchId)) {
            $this->branchId = $this->branches()->keys()->first();
        }
    }

    public function updatedDateFrom(): void
    {
        if ($this->dateFrom > $this->dateTo) {
            $this->dateTo = $this->dateFrom;
        }
    }

    public function updatedDateTo(): void
    {
        if ($this->dateTo < $this->dateFrom) {
            $this->dateFrom = $this->dateTo;
        }
    }

    /** @return array<string, mixed> */
    public function getReportProperty(): array
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return ['branch' => null, 'summary' => [], 'payments' => collect(), 'bestSellers' => collect(), 'lowStock' => collect()];
        }

        $sales = $this->salesQuery($branch);
        $summary = (clone $sales)
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(subtotal), 0) as subtotal, COALESCE(SUM(discount_total), 0) as discounts, COALESCE(SUM(tax_total), 0) as tax, COALESCE(SUM(grand_total), 0) as sales_total, COALESCE(SUM(paid_total), 0) as paid_total, COALESCE(SUM(balance_due), 0) as receivables')
            ->first();
        $grossProfit = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $branch->company_id)
            ->where('sales.branch_id', $branch->id)
            ->whereIn('sales.status', $this->saleStatuses())
            ->whereBetween('sales.sale_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('COALESCE(SUM((sale_items.unit_price - sale_items.unit_cost) * sale_items.quantity), 0) as gross_profit')
            ->value('gross_profit');
        $returns = SaleReturn::query()
            ->where('company_id', $branch->company_id)
            ->whereBetween('return_date', [$this->dateFrom, $this->dateTo])
            ->whereHas('sale', fn (Builder $query) => $query->where('branch_id', $branch->id))
            ->selectRaw('COUNT(*) as returns_count, COALESCE(SUM(grand_total), 0) as returns_total')
            ->first();
        $inventoryValue = $this->inventoryValue($branch);

        $payments = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.company_id', $branch->company_id)
            ->where('sales.branch_id', $branch->id)
            ->whereIn('sales.status', $this->saleStatuses())
            ->whereBetween('sales.sale_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('sale_payments.payment_method, COALESCE(SUM(sale_payments.amount), 0) as total')
            ->groupBy('sale_payments.payment_method')
            ->orderByDesc('total')
            ->get();

        $bestSellers = Product::query()
            ->join('sale_items', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $branch->company_id)
            ->where('sales.branch_id', $branch->id)
            ->whereIn('sales.status', $this->saleStatuses())
            ->whereBetween('sales.sale_date', [$this->dateFrom, $this->dateTo])
            ->select('products.id', 'products.name', 'products.sku')
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as quantity_sold, COALESCE(SUM(sale_items.line_total), 0) as sales_total')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get();

        return [
            'branch' => $branch,
            'summary' => [
                'transactions' => (int) ($summary->transactions ?? 0),
                'sales_total' => (float) ($summary->sales_total ?? 0),
                'discounts' => (float) ($summary->discounts ?? 0),
                'tax' => (float) ($summary->tax ?? 0),
                'paid_total' => (float) ($summary->paid_total ?? 0),
                'receivables' => (float) ($summary->receivables ?? 0),
                'gross_profit' => $grossProfit,
                'returns_count' => (int) ($returns->returns_count ?? 0),
                'returns_total' => (float) ($returns->returns_total ?? 0),
                'inventory_value' => $inventoryValue,
            ],
            'payments' => $payments,
            'bestSellers' => $bestSellers,
            'lowStock' => $this->lowStock($branch),
        ];
    }

    /** @return Collection<string, string> */
    public function branches(): Collection
    {
        return Branch::query()
            ->where('company_id', AdminSupport::companyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function selectedBranch(): ?Branch
    {
        return $this->branchId
            ? Branch::query()->where('company_id', AdminSupport::companyId())->find($this->branchId)
            : null;
    }

    private function salesQuery(Branch $branch): Builder
    {
        return Sale::query()
            ->where('company_id', $branch->company_id)
            ->where('branch_id', $branch->id)
            ->whereIn('status', $this->saleStatuses())
            ->whereBetween('sale_date', [$this->dateFrom, $this->dateTo]);
    }

    /** @return list<string> */
    private function saleStatuses(): array
    {
        return ['completed', 'refunded', 'partially_refunded'];
    }

    private function inventoryValue(Branch $branch): float
    {
        return (float) DB::table('products')
            ->leftJoin('inventory_balances', function ($join) use ($branch): void {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->where('inventory_balances.company_id', '=', $branch->company_id);
            })
            ->leftJoin('warehouses', function ($join) use ($branch): void {
                $join->on('warehouses.id', '=', 'inventory_balances.warehouse_id')
                    ->where('warehouses.branch_id', '=', $branch->id)
                    ->where('warehouses.is_active', true);
            })
            ->where('products.company_id', $branch->company_id)
            ->where('products.track_inventory', true)
            ->selectRaw('COALESCE(SUM(CASE WHEN warehouses.id IS NOT NULL THEN inventory_balances.quantity * products.cost_price ELSE 0 END), 0) as value')
            ->value('value');
    }

    private function lowStock(Branch $branch): Collection
    {
        return Product::query()
            ->leftJoin('inventory_balances', function ($join) use ($branch): void {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->where('inventory_balances.company_id', '=', $branch->company_id);
            })
            ->leftJoin('warehouses', function ($join) use ($branch): void {
                $join->on('warehouses.id', '=', 'inventory_balances.warehouse_id')
                    ->where('warehouses.branch_id', '=', $branch->id)
                    ->where('warehouses.is_active', true);
            })
            ->where('products.company_id', $branch->company_id)
            ->where('products.is_active', true)
            ->where('products.track_inventory', true)
            ->select('products.id', 'products.name', 'products.sku', 'products.minimum_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN warehouses.id IS NOT NULL THEN inventory_balances.quantity ELSE 0 END), 0) as current_quantity')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.minimum_stock')
            ->havingRaw('COALESCE(SUM(CASE WHEN warehouses.id IS NOT NULL THEN inventory_balances.quantity ELSE 0 END), 0) <= products.minimum_stock')
            ->orderBy('current_quantity')
            ->orderBy('products.name')
            ->limit(10)
            ->get();
    }
}
