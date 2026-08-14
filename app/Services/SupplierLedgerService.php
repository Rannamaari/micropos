<?php

namespace App\Services;

use App\Enums\SupplierTransactionType;
use App\Exceptions\TransactionException;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Support\Carbon;

class SupplierLedgerService
{
    public function currentPayable(string $supplierId): string
    {
        $total = SupplierTransaction::query()
            ->where('supplier_id', $supplierId)
            ->sum('amount');

        return $this->formatDecimal($total);
    }

    public function recordTransaction(
        string $companyId,
        string $supplierId,
        SupplierTransactionType $type,
        float|string $amount,
        array $attributes = [],
    ): SupplierTransaction {
        $supplier = Supplier::query()
            ->where('company_id', $companyId)
            ->find($supplierId);

        if (! $supplier) {
            throw new TransactionException('Supplier does not belong to the selected company.');
        }

        if (! is_numeric($amount)) {
            throw new TransactionException('Supplier transaction amount must be numeric.');
        }

        return SupplierTransaction::query()->create([
            'company_id' => $companyId,
            'supplier_id' => $supplierId,
            'type' => $type,
            'amount' => $this->formatDecimal($amount),
            'reference_type' => $attributes['reference_type'] ?? null,
            'reference_id' => $attributes['reference_id'] ?? null,
            'reference_number' => $attributes['reference_number'] ?? null,
            'description' => $attributes['description'] ?? null,
            'created_by' => $attributes['created_by'] ?? null,
            'occurred_at' => $attributes['occurred_at'] ?? Carbon::now(),
        ]);
    }

    private function formatDecimal(float|string|int $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
