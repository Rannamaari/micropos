<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_use_their_branch_currency_and_price(): void
    {
        $company = Company::factory()->create(['currency' => 'MVR']);
        $mvrBranch = Branch::factory()->create(['company_id' => $company->id, 'currency' => 'MVR']);
        $usdBranch = Branch::factory()->create(['company_id' => $company->id, 'currency' => 'USD']);
        $mvrWarehouse = Warehouse::factory()->create(['company_id' => $company->id, 'branch_id' => $mvrBranch->id]);
        $usdWarehouse = Warehouse::factory()->create(['company_id' => $company->id, 'branch_id' => $usdBranch->id]);
        $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => Unit::factory()->create()->id, 'selling_price' => 20, 'tax_rate' => 0, 'track_inventory' => false]);

        ProductBranchPrice::query()->create([
            'company_id' => $company->id,
            'branch_id' => $usdBranch->id,
            'product_id' => $product->id,
            'currency' => 'USD',
            'cost_price' => 1,
            'selling_price' => 3,
        ]);

        $mvrSale = app(SalesService::class)->createSale($company->id, $mvrBranch->id, $mvrWarehouse->id, [['product_id' => $product->id, 'quantity' => 1]], [['amount' => 20]]);
        $usdSale = app(SalesService::class)->createSale($company->id, $usdBranch->id, $usdWarehouse->id, [['product_id' => $product->id, 'quantity' => 1]], [['amount' => 3]]);

        $this->assertSame('MVR', $mvrSale->currency);
        $this->assertSame('20.0000', $mvrSale->grand_total);
        $this->assertSame('USD', $usdSale->currency);
        $this->assertSame('3.0000', $usdSale->grand_total);
        $this->assertSame('USD', $usdSale->payments->first()->currency);
    }
}
