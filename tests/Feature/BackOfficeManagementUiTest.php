<?php

namespace Tests\Feature;

use App\Filament\Resources\Purchases\Pages\ViewPurchase;
use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
    public function authorized_admin_can_open_branch_receipt_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@micropos.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/receipt-settings')
            ->assertOk()
            ->assertSee('Branch Receipt Settings');
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

    #[Test]
    public function purchase_receive_action_uses_fresh_remaining_quantities(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);
        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Cola',
            'sku' => 'COLA-1',
        ]);
        $supplier = Supplier::factory()->create(['company_id' => $warehouse->company_id]);
        $purchase = app(PurchaseService::class)->createPurchase(
            $warehouse->company_id,
            $warehouse->id,
            $supplier->id,
            [
                ['product_id' => $product->id, 'ordered_quantity' => 10, 'unit_cost' => 8.5],
            ],
            ['branch_id' => $warehouse->branch_id],
        );

        $component = Livewire::actingAs($user)->test(ViewPurchase::class, ['record' => $purchase->id]);

        app(PurchaseService::class)->receivePurchase($purchase->id, [
            $purchase->items->firstOrFail()->id => 6,
        ], $user->id);

        /** @var PurchaseItem $purchaseItem */
        $purchaseItem = $purchase->items->firstOrFail()->fresh();

        $component
            ->callAction('receive_items', data: [
                'items' => [[
                    'purchase_item_id' => $purchaseItem->id,
                    'receive_now' => '4.0000',
                ]],
                'received_at' => now()->toDateString(),
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('purchase_items', [
            'id' => $purchaseItem->id,
            'received_quantity' => '10.0000',
        ]);
    }

    private function userWithRole(string $role, Warehouse $warehouse): User
    {
        $user = User::factory()->forWarehouse($warehouse)->create();
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
