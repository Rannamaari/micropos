<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FinancialDocumentAuditEvent;
use App\Models\FinancialDocumentSnapshot;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialDocumentSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function finalized_sales_keep_their_original_master_data_and_new_sales_use_the_latest_data(): void
    {
        $warehouse = Warehouse::factory()->create();
        $company = $warehouse->company;
        $branch = $warehouse->branch;
        $company->update(['name' => 'Original Company', 'receipt_shop_name' => 'Original Receipt Shop', 'tax_number' => 'GST-OLD']);
        $branch->update(['name' => 'Original Branch', 'code' => 'OLD', 'address' => 'Old branch address']);
        $warehouse->update(['name' => 'Original Warehouse', 'code' => 'OLD-WH']);
        $cashier = User::factory()->forWarehouse($warehouse)->create(['name' => 'Original Cashier']);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Original Customer', 'code' => 'CUS-OLD']);
        $unit = Unit::factory()->create(['name' => 'Piece', 'short_name' => 'pc']);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
            'name' => 'Original Product',
            'sku' => 'SKU-OLD',
            'selling_price' => 10,
            'tax_rate' => 8,
            'track_inventory' => false,
        ]);
        ProductBarcode::factory()->create(['company_id' => $company->id, 'product_id' => $product->id, 'barcode' => 'BAR-OLD', 'is_primary' => true]);

        $firstSale = app(SalesService::class)->createSale(
            $company->id,
            $branch->id,
            $warehouse->id,
            [['product_id' => $product->id, 'quantity' => 1]],
            [['payment_method' => 'cash', 'amount' => 10, 'amount_tendered' => 20]],
            ['customer_id' => $customer->id, 'created_by' => $cashier->id],
        );

        $snapshot = FinancialDocumentSnapshot::query()->where('document_id', $firstSale->id)->firstOrFail();
        $this->assertSame('Original Company', $snapshot->snapshot['company']['name']);
        $this->assertSame('Original Customer', $snapshot->snapshot['customer']['name']);
        $this->assertSame('Original Product', $snapshot->snapshot['lines'][0]['name']);
        $this->assertSame('SKU-OLD', $snapshot->snapshot['lines'][0]['sku']);
        $this->assertSame('BAR-OLD', $snapshot->snapshot['lines'][0]['barcode']);
        $this->assertSame('8.0000', $snapshot->snapshot['lines'][0]['tax_rate']);
        $this->assertSame('10.0000', $snapshot->snapshot['payments'][0]['amount']);
        $this->assertSame('20.0000', $snapshot->snapshot['payments'][0]['amount_tendered']);

        $company->update(['name' => 'Changed Company', 'receipt_shop_name' => 'Changed Shop', 'tax_number' => 'GST-NEW']);
        $branch->update(['name' => 'Changed Branch', 'code' => 'NEW', 'address' => 'New branch address']);
        $warehouse->update(['name' => 'Changed Warehouse', 'code' => 'NEW-WH']);
        $cashier->update(['name' => 'Changed Cashier']);
        $customer->update(['name' => 'Changed Customer', 'code' => 'CUS-NEW', 'address' => 'New customer address']);
        $unit->update(['name' => 'Box', 'short_name' => 'box']);
        $product->update(['name' => 'Changed Product', 'sku' => 'SKU-NEW', 'selling_price' => 25, 'tax_rate' => 0]);
        $product->primaryBarcode->update(['barcode' => 'BAR-NEW']);

        $snapshot->refresh();
        $this->assertSame('Original Company', $snapshot->snapshot['company']['name']);
        $this->assertSame('Original Branch', $snapshot->snapshot['branch']['name']);
        $this->assertSame('Original Warehouse', $snapshot->snapshot['warehouse']['name']);
        $this->assertSame('Original Cashier', $snapshot->snapshot['cashier']['name']);
        $this->assertSame('Original Customer', $snapshot->snapshot['customer']['name']);
        $this->assertSame('Original Product', $snapshot->snapshot['lines'][0]['name']);
        $this->assertSame('SKU-OLD', $snapshot->snapshot['lines'][0]['sku']);
        $this->assertSame('BAR-OLD', $snapshot->snapshot['lines'][0]['barcode']);

        $secondSale = app(SalesService::class)->createSale(
            $company->id,
            $branch->id,
            $warehouse->id,
            [['product_id' => $product->id, 'quantity' => 1]],
            [['payment_method' => 'card', 'amount' => 25]],
            ['customer_id' => $customer->id, 'created_by' => $cashier->id],
        );
        $secondSnapshot = FinancialDocumentSnapshot::query()->where('document_id', $secondSale->id)->firstOrFail();

        $this->assertSame('Changed Company', $secondSnapshot->snapshot['company']['name']);
        $this->assertSame('Changed Product', $secondSnapshot->snapshot['lines'][0]['name']);
        $this->assertSame('SKU-NEW', $secondSnapshot->snapshot['lines'][0]['sku']);
        $this->assertSame('BAR-NEW', $secondSnapshot->snapshot['lines'][0]['barcode']);
    }

    #[Test]
    public function snapshots_are_immutable_and_reprints_are_append_only_and_sequential(): void
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
        $snapshot = FinancialDocumentSnapshot::query()->where('document_id', $sale->id)->firstOrFail();
        $originalChecksum = $snapshot->checksum;

        try {
            $snapshot->update(['checksum' => str_repeat('0', 64)]);
            $this->fail('Snapshot update was unexpectedly allowed.');
        } catch (LogicException) {
            $this->assertDatabaseHas('financial_document_snapshots', ['id' => $snapshot->id, 'checksum' => $originalChecksum]);
        }

        $this->actingAs($admin)->get(route('admin.sales.receipt', ['sale' => $sale, 'format' => 'thermal']))->assertOk()->assertSee('REPRINT #1');
        $this->actingAs($admin)->get(route('admin.sales.receipt', ['sale' => $sale, 'format' => 'a4']))->assertOk()->assertSee('REPRINT #2');

        $this->assertDatabaseCount('receipt_print_events', 2);
        $this->assertDatabaseHas('financial_document_audit_events', [
            'document_id' => $sale->id,
            'action' => 'reprinted',
        ]);
        $this->assertGreaterThanOrEqual(3, FinancialDocumentAuditEvent::query()->where('document_id', $sale->id)->count());
    }
}
