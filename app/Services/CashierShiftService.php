<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Exceptions\TransactionException;
use App\Models\CashierShift;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CashierShiftService
{
    public function __construct(
        private readonly NumberSequenceService $numberSequenceService,
        private readonly FinancialDocumentSnapshotService $financialDocumentSnapshotService,
    ) {}

    public function activeFor(array $context, string $cashierId): ?CashierShift
    {
        return CashierShift::query()
            ->where('company_id', $context['company_id'])
            ->where('branch_id', $context['branch_id'])
            ->where('warehouse_id', $context['warehouse_id'])
            ->where('cashier_id', $cashierId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function open(array $context, User $cashier, array $openingCashByCurrency, ?string $notes = null): CashierShift
    {
        return DB::transaction(function () use ($context, $cashier, $openingCashByCurrency, $notes): CashierShift {
            $existing = CashierShift::query()
                ->where('company_id', $context['company_id'])
                ->where('branch_id', $context['branch_id'])
                ->where('warehouse_id', $context['warehouse_id'])
                ->where('cashier_id', $cashier->id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $cashByCurrency = $this->normalizeCashCounts($context, $openingCashByCurrency);
            $primaryCurrency = strtoupper($context['branch']->currency);

            return CashierShift::query()->create([
                'company_id' => $context['company_id'],
                'branch_id' => $context['branch_id'],
                'warehouse_id' => $context['warehouse_id'],
                'cashier_id' => $cashier->id,
                'shift_number' => $this->numberSequenceService->next($context['company_id'], 'cashier_shift'),
                'currency' => $context['branch']->currency,
                'status' => 'open',
                'opening_cash' => $cashByCurrency[$primaryCurrency],
                'opening_cash_by_currency' => $cashByCurrency,
                'opening_notes' => $notes,
                'opened_at' => now(),
            ]);
        });
    }

    public function close(CashierShift $shift, array $context, User $cashier, array $closingCashByCurrency, ?string $notes = null): CashierShift
    {
        return DB::transaction(function () use ($shift, $context, $cashier, $closingCashByCurrency, $notes): CashierShift {
            $shift = CashierShift::query()->lockForUpdate()->findOrFail($shift->id);

            if ($shift->status !== 'open') {
                throw new TransactionException('This cashier shift has already been closed.');
            }

            if ($shift->cashier_id !== $cashier->id || $shift->company_id !== $context['company_id'] || $shift->branch_id !== $context['branch_id'] || $shift->warehouse_id !== $context['warehouse_id']) {
                throw new TransactionException('This shift does not belong to the current POS assignment.');
            }

            $saleStatuses = [SaleStatus::Completed->value, SaleStatus::PartiallyRefunded->value, SaleStatus::Refunded->value];
            $sales = Sale::query()
                ->where('cashier_shift_id', $shift->id)
                ->whereIn('status', $saleStatuses)
                ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(subtotal), 0) as subtotal, COALESCE(SUM(discount_total), 0) as discount_total, COALESCE(SUM(tax_total), 0) as tax_total, COALESCE(SUM(grand_total), 0) as grand_total, COALESCE(SUM(paid_total), 0) as paid_total, COALESCE(SUM(balance_due), 0) as balance_due')
                ->first();

            $payments = SalePayment::query()
                ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
                ->where('sales.cashier_shift_id', $shift->id)
                ->whereIn('sales.status', $saleStatuses)
                ->selectRaw('sale_payments.payment_method, sale_payments.currency, COALESCE(SUM(sale_payments.amount), 0) as amount, COALESCE(SUM(COALESCE(sale_payments.currency_amount, sale_payments.amount)), 0) as currency_amount, COALESCE(SUM(sale_payments.amount_tendered), 0) as tendered, COALESCE(SUM(sale_payments.change_due), 0) as change_due')
                ->groupBy('sale_payments.payment_method', 'sale_payments.currency')
                ->orderBy('sale_payments.payment_method')
                ->orderBy('sale_payments.currency')
                ->get()
                ->map(fn ($payment): array => [
                    'method' => $payment->payment_method,
                    'currency' => $payment->currency,
                    'amount' => $this->decimal((float) $payment->amount),
                    'currency_amount' => $this->decimal((float) $payment->currency_amount),
                    'tendered' => $this->decimal((float) $payment->tendered),
                    'change_due' => $this->decimal((float) $payment->change_due),
                ])->all();

            $cashReceivedByCurrency = collect($payments)
                ->where('method', 'cash')
                ->mapWithKeys(fn (array $payment): array => [$payment['currency'] => $payment['currency_amount']])
                ->all();
            $returns = SaleReturn::query()
                ->where('company_id', $shift->company_id)
                ->where('warehouse_id', $shift->warehouse_id)
                ->where('created_by', $shift->cashier_id)
                ->whereBetween('created_at', [$shift->opened_at, now()])
                ->selectRaw('COUNT(*) as returns_count, COALESCE(SUM(grand_total), 0) as returns_total')
                ->first();
            $openingCashByCurrency = $shift->opening_cash_by_currency ?? [$shift->currency => $shift->opening_cash];
            $closingCashByCurrency = $this->normalizeCashCounts($context, $closingCashByCurrency);
            $expectedCashByCurrency = [];
            $cashVarianceByCurrency = [];

            foreach ($closingCashByCurrency as $currency => $closingCash) {
                $expectedCashByCurrency[$currency] = $this->decimal(
                    (float) ($openingCashByCurrency[$currency] ?? 0) + (float) ($cashReceivedByCurrency[$currency] ?? 0),
                );
                $cashVarianceByCurrency[$currency] = $this->decimal((float) $closingCash - (float) $expectedCashByCurrency[$currency]);
            }

            $primaryCurrency = strtoupper($context['branch']->currency);
            $expectedCash = $expectedCashByCurrency[$primaryCurrency];
            $closingCash = $closingCashByCurrency[$primaryCurrency];
            $variance = $cashVarianceByCurrency[$primaryCurrency];
            $closedAt = now();

            $snapshot = [
                'generated_at' => $closedAt->toIso8601String(),
                'sales_count' => (int) ($sales->sales_count ?? 0),
                'subtotal' => $this->decimal((float) ($sales->subtotal ?? 0)),
                'discount_total' => $this->decimal((float) ($sales->discount_total ?? 0)),
                'tax_total' => $this->decimal((float) ($sales->tax_total ?? 0)),
                'grand_total' => $this->decimal((float) ($sales->grand_total ?? 0)),
                'paid_total' => $this->decimal((float) ($sales->paid_total ?? 0)),
                'balance_due' => $this->decimal((float) ($sales->balance_due ?? 0)),
                'payments' => $payments,
                'cash_received' => $this->decimal((float) ($cashReceivedByCurrency[$primaryCurrency] ?? 0)),
                'cash_received_by_currency' => $cashReceivedByCurrency,
                'returns_count' => (int) ($returns->returns_count ?? 0),
                'returns_total' => $this->decimal((float) ($returns->returns_total ?? 0)),
                'refund_note' => 'Refund payment methods are not recorded yet, so refunds are shown separately and are not deducted from expected cash.',
            ];

            $shift->forceFill([
                'status' => 'closed',
                'expected_cash' => $this->decimal($expectedCash),
                'expected_cash_by_currency' => $expectedCashByCurrency,
                'closing_cash' => $this->decimal($closingCash),
                'closing_cash_by_currency' => $closingCashByCurrency,
                'cash_variance' => $this->decimal($variance),
                'cash_variance_by_currency' => $cashVarianceByCurrency,
                'closing_notes' => $notes,
                'report_snapshot' => $snapshot,
                'closed_at' => $closedAt,
            ])->save();

            $this->financialDocumentSnapshotService->captureCashierShift($shift, $cashier->id);

            return $shift->fresh(['company', 'branch', 'warehouse', 'cashier']);
        });
    }

    private function decimal(float $value): string
    {
        return number_format($value, 4, '.', '');
    }

    /** @return array<string, string> */
    private function normalizeCashCounts(array $context, array $cashByCurrency): array
    {
        $primaryCurrency = strtoupper($context['branch']->currency);
        $secondaryCurrency = $context['branch']->secondary_currency
            ? strtoupper($context['branch']->secondary_currency)
            : null;
        $currencies = array_values(array_filter([$primaryCurrency, $secondaryCurrency]));
        $cashByCurrency = collect($cashByCurrency)
            ->mapWithKeys(fn ($amount, $currency): array => [strtoupper((string) $currency) => $amount])
            ->all();
        $unexpected = array_diff(array_keys($cashByCurrency), $currencies);

        if ($unexpected !== []) {
            throw new TransactionException('Cash was entered for a currency that is not configured for this branch.');
        }

        $normalized = [];

        foreach ($currencies as $currency) {
            $value = $cashByCurrency[$currency] ?? 0;

            if (! is_numeric($value) || (float) $value < 0) {
                throw new TransactionException("{$currency} cash must be zero or greater.");
            }

            $normalized[$currency] = $this->decimal((float) $value);
        }

        return $normalized;
    }
}
