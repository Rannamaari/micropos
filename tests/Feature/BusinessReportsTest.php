<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function an_admin_can_view_branch_scoped_business_reports(): void
    {
        $warehouse = Warehouse::factory()->create();
        $admin = User::factory()->forWarehouse($warehouse)->create();
        $admin->assignRole(Role::findByName('admin'));
        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Report Cola',
            'sku' => 'REPORT-COLA',
            'cost_price' => 4,
        ]);
        $sale = Sale::factory()->create([
            'company_id' => $warehouse->company_id,
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'status' => SaleStatus::Completed,
            'sale_date' => today(),
            'subtotal' => 10,
            'grand_total' => 10,
            'paid_total' => 10,
            'balance_due' => 0,
        ]);
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'company_id' => $warehouse->company_id,
            'product_id' => $product->id,
            'description' => $product->name,
            'quantity' => 2,
            'unit_price' => 5,
            'unit_cost' => 4,
            'line_total' => 10,
        ]);
        SalePayment::factory()->create([
            'company_id' => $warehouse->company_id,
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 10,
        ]);

        $this->actingAs($admin)
            ->get('/admin/business-reports')
            ->assertOk()
            ->assertSee('Business Reports')
            ->assertSee('Report Cola')
            ->assertSee('Tender Mix');
    }

    #[Test]
    public function a_manager_cannot_access_business_reports(): void
    {
        $warehouse = Warehouse::factory()->create();
        $manager = User::factory()->forWarehouse($warehouse)->create();
        $manager->assignRole(Role::findByName('manager'));

        $this->actingAs($manager)->get('/admin/business-reports')->assertForbidden();
    }
}
