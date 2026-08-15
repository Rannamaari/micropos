<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BackOfficeManagementUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    #[Test]
    public function authorized_admin_can_open_back_office_dashboard(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    #[Test]
    public function cashier_cannot_access_back_office_panel(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('cashier', $warehouse);

        $response = $this->actingAs($user)->get('/admin');

        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    #[Test]
    public function product_management_page_is_company_scoped(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);

        $visibleProduct = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Visible Admin Product',
            'sku' => 'ADM-100',
        ]);

        $otherCompany = Company::factory()->create();
        Product::factory()->create([
            'company_id' => $otherCompany->id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Other Company Product',
            'sku' => 'OTH-100',
        ]);

        $this->actingAs($user)
            ->get('/admin/products')
            ->assertOk()
            ->assertSee($visibleProduct->name)
            ->assertDontSee('Other Company Product');
    }

    #[Test]
    public function purchase_orders_page_is_accessible_and_company_scoped(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);
        $supplier = Supplier::factory()->create(['company_id' => $warehouse->company_id]);
        $purchase = Purchase::factory()->create([
            'company_id' => $warehouse->company_id,
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'purchase_number' => 'PO-1001',
        ]);

        $otherWarehouse = Warehouse::factory()->create();
        $otherSupplier = Supplier::factory()->create(['company_id' => $otherWarehouse->company_id]);
        Purchase::factory()->create([
            'company_id' => $otherWarehouse->company_id,
            'branch_id' => $otherWarehouse->branch_id,
            'warehouse_id' => $otherWarehouse->id,
            'supplier_id' => $otherSupplier->id,
            'purchase_number' => 'PO-OTHER-1',
        ]);

        $this->actingAs($user)
            ->get('/admin/purchases')
            ->assertOk()
            ->assertSee($purchase->purchase_number)
            ->assertDontSee('PO-OTHER-1');
    }

    #[Test]
    public function authorized_admin_can_open_new_purchase_order_page(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);

        $this->actingAs($user)
            ->get('/admin/purchases/create')
            ->assertOk()
            ->assertSee('Purchase Order');
    }

    private function userWithRole(string $role, Warehouse $warehouse): User
    {
        $user = User::factory()->forWarehouse($warehouse)->create();
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
