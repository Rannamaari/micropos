<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'legal_name',
        'contact_person',
        'phone',
        'email',
        'registration_number',
        'tax_number',
        'address',
        'city',
        'country',
        'credit_limit',
        'payment_terms_days',
        'opening_balance',
        'notes',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:4',
            'opening_balance' => 'decimal:4',
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Supplier $supplier): void {
            if ((float) $supplier->opening_balance !== 0.0) {
                SupplierTransaction::query()->create([
                    'company_id' => $supplier->company_id,
                    'supplier_id' => $supplier->id,
                    'type' => \App\Enums\SupplierTransactionType::OpeningBalance,
                    'amount' => $supplier->opening_balance,
                    'reference_type' => self::class,
                    'reference_id' => $supplier->id,
                    'reference_number' => $supplier->code,
                    'description' => 'Opening balance',
                    'occurred_at' => now(),
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }
}
