<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ReceiptPrintEvent;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSaleReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_reprints_are_watermarked_and_audited_sequentially(): void
    {
        $warehouse = Warehouse::factory()->create();
        $admin = User::factory()->forWarehouse($warehouse)->create();
        $admin->assignRole(Role::findByName('admin'));
        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'selling_price' => 10,
            'tax_rate' => 0,
            'track_inventory' => false,
        ]);
        $sale = app(SalesService::class)->createSale(
            $warehouse->company_id,
            $warehouse->branch_id,
            $warehouse->id,
            [['product_id' => $product->id, 'quantity' => 1]],
            [['payment_method' => 'cash', 'amount' => 10]],
            ['created_by' => $admin->id],
        );

        $this->actingAs($admin)
            ->get(route('admin.sales.receipt', ['sale' => $sale, 'format' => 'thermal']))
            ->assertOk()
            ->assertSee('REPRINT #1')
            ->assertSee($sale->sale_number);
        $this->actingAs($admin)
            ->get(route('admin.sales.receipt', ['sale' => $sale, 'format' => 'a4']))
            ->assertOk()
            ->assertSee('REPRINT #2')
            ->assertSee('TAX INVOICE');

        $this->assertSame(2, ReceiptPrintEvent::query()->where('sale_id', $sale->id)->count());
        $this->assertDatabaseHas('receipt_print_events', ['sale_id' => $sale->id, 'reprint_number' => 2, 'format' => 'a4']);
    }
}
