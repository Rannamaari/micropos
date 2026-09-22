<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\FinancialDocumentAuditEvent;
use App\Models\FinancialDocumentSnapshot;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\ReceiptPrintEvent;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class FinancialDocumentSnapshotService
{
    public function __construct(private readonly ReceiptProfileResolver $receiptProfileResolver) {}

    public function captureSale(Sale $sale, ?string $actorId = null, bool $reconstructed = false): FinancialDocumentSnapshot
    {
        $sale->loadMissing([
            'company', 'branch', 'warehouse', 'customer', 'creator',
            'items.product.primaryBarcode', 'items.product.unit', 'payments',
        ]);

        $company = $this->company($sale->company, $sale->branch);

        if ($sale->receipt_snapshot) {
            $company['receipt'] = $sale->receipt_snapshot;
        }

        return $this->capture($sale, 'pos_sale_receipt', [
            'document' => $this->document($sale, $sale->sale_number, $sale->status->value, $sale->sale_date, $sale->completed_at ?? $sale->created_at, $sale->currency, 'pos'),
            'company' => $company,
            'branch' => $this->branch($sale->branch),
            'warehouse' => $this->warehouse($sale->warehouse),
            'customer' => $this->customer($sale->customer),
            'cashier' => $this->user($sale->creator),
            'lines' => $sale->items->map(fn ($item): array => $this->saleLine($item->product, $item))->all(),
            'totals' => $this->totals($sale),
            'payments' => $sale->payments->map(fn ($payment): array => $this->payment($payment))->all(),
        ], $actorId ?? $sale->created_by, $sale->completed_at ?? $sale->created_at, $reconstructed);
    }

    public function captureSaleReturn(SaleReturn $return, ?string $actorId = null, bool $reconstructed = false): FinancialDocumentSnapshot
    {
        $return->loadMissing([
            'sale.company', 'sale.branch', 'sale.creator', 'warehouse', 'customer', 'items.product.primaryBarcode', 'items.product.unit',
        ]);
        $sale = $return->sale;

        return $this->capture($return, 'sale_credit_note', [
            'document' => $this->document($return, $return->sale_return_number, $return->refund_status, $return->return_date, $return->created_at, $sale?->currency ?? 'MVR', 'pos'),
            'company' => $this->company($sale?->company, $sale?->branch),
            'branch' => $this->branch($sale?->branch),
            'warehouse' => $this->warehouse($return->warehouse),
            'customer' => $this->customer($return->customer),
            'cashier' => $this->user(User::query()->find($return->created_by)),
            'original_document' => $this->reference($sale),
            'lines' => $return->items->map(fn ($item): array => $this->saleLine($item->product, $item))->all(),
            'totals' => $this->totals($return),
            'payments' => [],
            'notes' => $return->notes,
        ], $actorId ?? $return->created_by, $return->created_at, $reconstructed);
    }

    public function capturePurchase(Purchase $purchase, ?string $actorId = null, bool $reconstructed = false): FinancialDocumentSnapshot
    {
        $purchase->loadMissing([
            'company', 'branch', 'warehouse', 'supplier', 'creator', 'receiver',
            'items.product.primaryBarcode', 'items.product.unit', 'payments',
        ]);

        return $this->capture($purchase, 'purchase_bill', [
            'document' => $this->document($purchase, $purchase->purchase_number, $purchase->status->value, $purchase->purchase_date, $purchase->received_at ?? $purchase->created_at, $purchase->currency, 'back_office') + [
                'supplier_invoice_number' => $purchase->supplier_invoice_number,
                'expected_date' => $purchase->expected_date?->toDateString(),
            ],
            'company' => $this->company($purchase->company, $purchase->branch),
            'branch' => $this->branch($purchase->branch),
            'warehouse' => $this->warehouse($purchase->warehouse),
            'supplier' => $this->supplier($purchase->supplier),
            'cashier' => $this->user($purchase->creator),
            'received_by' => $this->user($purchase->receiver),
            'lines' => $purchase->items->map(fn ($item): array => $this->purchaseLine($item->product, $item))->all(),
            'totals' => $this->totals($purchase),
            'payments' => $purchase->payments->map(fn ($payment): array => $this->payment($payment))->all(),
            'notes' => $purchase->notes,
        ], $actorId ?? $purchase->received_by ?? $purchase->created_by, $purchase->received_at ?? $purchase->created_at, $reconstructed);
    }

    public function capturePurchaseReturn(PurchaseReturn $return, ?string $actorId = null, bool $reconstructed = false): FinancialDocumentSnapshot
    {
        $return->loadMissing([
            'purchase.company', 'purchase.branch', 'purchase.creator', 'warehouse', 'supplier', 'items.product.primaryBarcode', 'items.product.unit',
        ]);
        $purchase = $return->purchase;

        return $this->capture($return, 'purchase_credit_note', [
            'document' => $this->document($return, $return->purchase_return_number, 'completed', $return->return_date, $return->created_at, $purchase?->currency ?? 'MVR', 'back_office'),
            'company' => $this->company($purchase?->company, $purchase?->branch),
            'branch' => $this->branch($purchase?->branch),
            'warehouse' => $this->warehouse($return->warehouse),
            'supplier' => $this->supplier($return->supplier),
            'cashier' => $this->user(User::query()->find($return->created_by)),
            'original_document' => $this->reference($purchase),
            'lines' => $return->items->map(fn ($item): array => $this->purchaseLine($item->product, $item))->all(),
            'totals' => $this->totals($return),
            'payments' => [],
            'notes' => $return->notes,
        ], $actorId ?? $return->created_by, $return->created_at, $reconstructed);
    }

    public function capturePurchasePayment(PurchasePayment $payment, ?string $actorId = null, bool $reconstructed = false): FinancialDocumentSnapshot
    {
        $payment->loadMissing(['purchase.company', 'purchase.branch', 'purchase.warehouse', 'supplier']);

        return $this->capture($payment, 'supplier_payment', [
            'document' => $this->document($payment, $payment->id, 'completed', $payment->paid_at?->toDateString(), $payment->paid_at, $payment->currency, 'back_office'),
            'company' => $this->company($payment->purchase?->company, $payment->purchase?->branch),
            'branch' => $this->branch($payment->purchase?->branch),
            'warehouse' => $this->warehouse($payment->purchase?->warehouse),
            'supplier' => $this->supplier($payment->supplier),
            'cashier' => $this->user(User::query()->find($payment->created_by)),
            'original_document' => $this->reference($payment->purchase),
            'payment' => $this->payment($payment),
            'totals' => ['grand_total' => $this->decimal($payment->amount)],
        ], $actorId ?? $payment->created_by, $payment->paid_at, $reconstructed);
    }

    public function captureCustomerPayment(CustomerPayment $payment, ?string $actorId = null, bool $reconstructed = false): FinancialDocumentSnapshot
    {
        $payment->loadMissing(['sale.company', 'sale.branch', 'sale.warehouse', 'customer']);

        return $this->capture($payment, 'customer_payment', [
            'document' => $this->document($payment, $payment->id, 'completed', $payment->paid_at?->toDateString(), $payment->paid_at, $payment->currency, 'back_office'),
            'company' => $this->company($payment->sale?->company, $payment->sale?->branch),
            'branch' => $this->branch($payment->sale?->branch),
            'warehouse' => $this->warehouse($payment->sale?->warehouse),
            'customer' => $this->customer($payment->customer),
            'cashier' => $this->user(User::query()->find($payment->created_by)),
            'original_document' => $this->reference($payment->sale),
            'payment' => $this->payment($payment),
            'totals' => ['grand_total' => $this->decimal($payment->amount)],
        ], $actorId ?? $payment->created_by, $payment->paid_at, $reconstructed);
    }

    public function captureCashierShift(CashierShift $shift, ?string $actorId = null, bool $reconstructed = false): FinancialDocumentSnapshot
    {
        $shift->loadMissing(['company', 'branch', 'warehouse', 'cashier']);

        return $this->capture($shift, 'cashier_shift_eod', [
            'document' => $this->document($shift, $shift->shift_number, $shift->status, $shift->closed_at?->toDateString(), $shift->closed_at, $shift->currency, 'pos'),
            'company' => $this->company($shift->company, $shift->branch),
            'branch' => $this->branch($shift->branch),
            'warehouse' => $this->warehouse($shift->warehouse),
            'cashier' => $this->user($shift->cashier),
            'shift' => [
                'opening_cash' => $this->decimal($shift->opening_cash),
                'opening_cash_by_currency' => $shift->opening_cash_by_currency ?? [$shift->currency => $this->decimal($shift->opening_cash)],
                'expected_cash' => $this->decimal($shift->expected_cash),
                'expected_cash_by_currency' => $shift->expected_cash_by_currency ?? [$shift->currency => $this->decimal($shift->expected_cash)],
                'closing_cash' => $this->decimal($shift->closing_cash),
                'closing_cash_by_currency' => $shift->closing_cash_by_currency ?? [$shift->currency => $this->decimal($shift->closing_cash)],
                'cash_variance' => $this->decimal($shift->cash_variance),
                'cash_variance_by_currency' => $shift->cash_variance_by_currency ?? [$shift->currency => $this->decimal($shift->cash_variance)],
                'opening_notes' => $shift->opening_notes,
                'closing_notes' => $shift->closing_notes,
                'opened_at' => $this->timestamp($shift->opened_at, $shift->company),
                'closed_at' => $this->timestamp($shift->closed_at, $shift->company),
                'report' => $shift->report_snapshot ?? [],
            ],
        ], $actorId ?? $shift->cashier_id, $shift->closed_at, $reconstructed);
    }

    public function audit(Model $document, string $action, ?string $actorId = null, array $metadata = [], ?Carbon $occurredAt = null, ?int $printNumber = null): FinancialDocumentAuditEvent
    {
        return FinancialDocumentAuditEvent::query()->create([
            'company_id' => $document->company_id,
            'document_type' => $document::class,
            'document_id' => $document->getKey(),
            'action' => $action,
            'print_number' => $printNumber,
            'actor_id' => $actorId,
            'metadata' => $metadata,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    public function recordSalePrint(Sale $sale, ?User $user, string $format): ReceiptPrintEvent
    {
        return DB::transaction(function () use ($sale, $user, $format): ReceiptPrintEvent {
            $lockedSale = Sale::query()->where('company_id', $sale->company_id)->lockForUpdate()->findOrFail($sale->id);
            $printNumber = (int) ReceiptPrintEvent::query()->where('sale_id', $lockedSale->id)->max('reprint_number') + 1;

            $event = ReceiptPrintEvent::query()->create([
                'company_id' => $lockedSale->company_id,
                'sale_id' => $lockedSale->id,
                'printed_by' => $user?->id,
                'printed_by_name' => $user?->name,
                'reprint_number' => $printNumber,
                'format' => $format,
                'printed_at' => now(),
            ]);

            $this->audit($lockedSale, $printNumber === 1 ? 'printed' : 'reprinted', $user?->id, [
                'format' => $format,
                'print_event_id' => $event->id,
            ], $event->printed_at, $printNumber);

            return $event;
        });
    }

    public function verify(FinancialDocumentSnapshot $snapshot): bool
    {
        return hash_equals($snapshot->checksum, $this->checksum($snapshot->snapshot, $snapshot->signing_key_version));
    }

    private function capture(Model $document, string $kind, array $snapshot, ?string $actorId, ?Carbon $finalizedAt, bool $reconstructed): FinancialDocumentSnapshot
    {
        $existing = FinancialDocumentSnapshot::query()
            ->where('document_type', $document::class)
            ->where('document_id', $document->getKey())
            ->where('version', 1)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        $company = $snapshot['company'] ?? [];
        $snapshot = [
            'snapshot_version' => 1,
            'captured_at' => $this->timestamp(now(), $document->company ?? null),
            'reconstructed' => $reconstructed,
            ...$snapshot,
        ];
        $keyVersion = (int) config('document-snapshots.signing_key_version', 1);

        $created = FinancialDocumentSnapshot::query()->create([
            'company_id' => $document->company_id,
            'document_type' => $document::class,
            'document_id' => $document->getKey(),
            'version' => 1,
            'document_kind' => $kind,
            'snapshot' => $snapshot,
            'checksum' => $this->checksum($snapshot, $keyVersion),
            'signing_key_version' => $keyVersion,
            'is_reconstructed' => $reconstructed,
            'finalized_at' => $finalizedAt ?? now(),
            'finalized_by' => $actorId,
        ]);

        $this->audit($document, $reconstructed ? 'snapshot_backfilled' : 'finalized', $actorId, [
            'snapshot_id' => $created->id,
            'snapshot_version' => 1,
            'checksum' => $created->checksum,
        ], $finalizedAt);

        return $created;
    }

    private function document(Model $model, string $number, string $status, mixed $date, mixed $time, ?string $currency, string $channel): array
    {
        return [
            'id' => $model->getKey(),
            'number' => $number,
            'status' => $status,
            'date' => $date instanceof Carbon ? $date->toDateString() : $date,
            'time' => $this->timestamp($time, $model->company ?? null),
            'currency' => $currency,
            'sales_channel' => $channel,
        ];
    }

    private function company(?Company $company, ?Branch $branch): array
    {
        if (! $company) {
            return [];
        }

        return [
            'id' => $company->id,
            'name' => $company->name,
            'legal_name' => $company->legal_name,
            'registration_number' => $company->registration_number,
            'tax_number' => $company->tax_number,
            'email' => $company->email,
            'address' => $company->address,
            'city' => $company->city,
            'country' => $company->country,
            'phone' => $company->phone,
            'timezone' => $company->timezone,
            'receipt' => $this->receiptProfileResolver->resolve($company, $branch ?? new Branch),
        ];
    }

    private function branch(?Branch $branch): array
    {
        return $branch ? [
            'id' => $branch->id,
            'name' => $branch->name,
            'code' => $branch->code,
            'phone' => $branch->phone,
            'email' => $branch->email,
            'address' => $branch->address,
            'city' => $branch->city,
            'currency' => $branch->currency,
            'secondary_currency' => $branch->secondary_currency,
            'secondary_currency_rate' => $branch->secondary_currency_rate,
        ] : [];
    }

    private function warehouse(?Warehouse $warehouse): array
    {
        return $warehouse ? ['id' => $warehouse->id, 'name' => $warehouse->name, 'code' => $warehouse->code, 'address' => $warehouse->address] : [];
    }

    private function customer(?Customer $customer): array
    {
        return $customer ? ['id' => $customer->id, 'code' => $customer->code, 'name' => $customer->name, 'phone' => $customer->phone, 'email' => $customer->email, 'registration_number' => $customer->registration_number, 'tax_number' => $customer->tax_number, 'address' => $customer->address, 'city' => $customer->city, 'is_walk_in' => $customer->is_walk_in] : ['name' => 'Walk-in Customer'];
    }

    private function supplier(?Supplier $supplier): array
    {
        return $supplier ? ['id' => $supplier->id, 'code' => $supplier->code, 'name' => $supplier->name, 'legal_name' => $supplier->legal_name, 'contact_person' => $supplier->contact_person, 'phone' => $supplier->phone, 'email' => $supplier->email, 'registration_number' => $supplier->registration_number, 'tax_number' => $supplier->tax_number, 'address' => $supplier->address, 'city' => $supplier->city, 'country' => $supplier->country] : [];
    }

    private function user(?User $user): array
    {
        return $user ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email] : [];
    }

    private function saleLine(?Product $product, Model $item): array
    {
        return $this->line($product, $item, 'quantity', 'unit_price', 'unit_cost');
    }

    private function purchaseLine(?Product $product, Model $item): array
    {
        $quantityField = $item->getAttribute('quantity') !== null ? 'quantity' : 'ordered_quantity';
        $line = $this->line($product, $item, $quantityField, 'unit_cost', 'unit_cost');
        $line['received_quantity'] = $this->decimal($item->received_quantity ?? null);

        return $line;
    }

    private function line(?Product $product, Model $item, string $quantityField, string $priceField, string $costField): array
    {
        return [
            'product_id' => $item->product_id,
            'name' => $item->description ?? $product?->name,
            'sku' => $product?->sku,
            'barcode' => $product?->primaryBarcode?->barcode,
            'unit' => ['name' => $product?->unit?->name, 'short_name' => $product?->unit?->short_name, 'precision' => $product?->unit?->precision],
            'quantity' => $this->decimal($item->{$quantityField}),
            'unit_price' => $this->decimal($item->{$priceField}),
            'unit_cost' => $this->decimal($item->{$costField}),
            'discount_amount' => $this->decimal($item->discount_amount ?? 0),
            'tax_rate' => $this->decimal($item->tax_rate ?? 0),
            'tax_amount' => $this->decimal($item->tax_amount ?? 0),
            'line_total' => $this->decimal($item->line_total),
        ];
    }

    private function totals(Model $model): array
    {
        return [
            'subtotal' => $this->decimal($model->subtotal ?? 0),
            'discount_total' => $this->decimal($model->discount_total ?? 0),
            'tax_total' => $this->decimal($model->tax_total ?? 0),
            'shipping_total' => $this->decimal($model->shipping_total ?? 0),
            'other_cost_total' => $this->decimal($model->other_cost_total ?? 0),
            'grand_total' => $this->decimal($model->grand_total ?? 0),
            'paid_total' => $this->decimal($model->paid_total ?? 0),
            'balance_due' => $this->decimal($model->balance_due ?? 0),
        ];
    }

    private function payment(Model $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_method' => $payment->payment_method,
            'currency' => $payment->currency,
            'exchange_rate' => (string) ($payment->exchange_rate ?? '1.00000000'),
            'currency_amount' => $this->decimal($payment->currency_amount ?? $payment->amount),
            'amount' => $this->decimal($payment->amount),
            'amount_tendered' => $payment->amount_tendered === null ? null : $this->decimal($payment->amount_tendered),
            'change_due' => $this->decimal($payment->change_due ?? 0),
            'reference' => $payment->reference,
            'notes' => $payment->notes,
            'paid_at' => $this->timestamp($payment->paid_at, null),
        ];
    }

    private function reference(?Model $document): ?array
    {
        if (! $document) {
            return null;
        }

        $snapshot = FinancialDocumentSnapshot::query()->where('document_type', $document::class)->where('document_id', $document->getKey())->where('version', 1)->first();

        return ['document_type' => $document::class, 'document_id' => $document->getKey(), 'snapshot_checksum' => $snapshot?->checksum];
    }

    private function checksum(array $snapshot, int $keyVersion): string
    {
        $key = config('document-snapshots.signing_key');

        if (! is_string($key) || $key === '') {
            throw new LogicException('DOCUMENT_SNAPSHOT_SIGNING_KEY must be configured before financial documents can be finalized.');
        }

        return hash_hmac('sha256', $this->canonicalJson(['key_version' => $keyVersion, 'snapshot' => $snapshot]), $key);
    }

    private function canonicalJson(mixed $value): string
    {
        if (is_array($value)) {
            if (! array_is_list($value)) {
                ksort($value);
            }

            foreach ($value as $key => $item) {
                $value[$key] = json_decode($this->canonicalJson($item), true, 512, JSON_THROW_ON_ERROR);
            }
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function decimal(mixed $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 4, '.', '');
    }

    private function timestamp(mixed $value, ?Company $company): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->setTimezone($company?->timezone ?: config('app.timezone'))->toIso8601String();
    }
}
