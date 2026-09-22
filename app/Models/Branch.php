<?php

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'phone',
        'email',
        'address',
        'city',
        'currency',
        'secondary_currency',
        'secondary_currency_rate',
        'receipt_shop_name',
        'receipt_tax_number',
        'receipt_gst_label',
        'receipt_header',
        'receipt_footer',
        'receipt_show_address',
        'receipt_show_phone',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'receipt_show_address' => 'boolean',
            'receipt_show_phone' => 'boolean',
            'secondary_currency_rate' => 'decimal:8',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Branch $branch): void {
            $branch->currency = strtoupper($branch->currency ?: 'MVR');
            $branch->secondary_currency = $branch->secondary_currency ? strtoupper($branch->secondary_currency) : null;

            if ($branch->secondary_currency === $branch->currency) {
                throw new InvalidArgumentException('Secondary currency must be different from the primary currency.');
            }

            if ($branch->secondary_currency && (! is_numeric($branch->secondary_currency_rate) || (float) $branch->secondary_currency_rate <= 0)) {
                throw new InvalidArgumentException('A positive exchange rate is required for the secondary currency.');
            }

            if (! $branch->secondary_currency) {
                $branch->secondary_currency_rate = null;
            }

            if ($branch->exists && $branch->isDirty(['currency', 'secondary_currency']) && CashierShift::query()->where('branch_id', $branch->id)->where('status', 'open')->exists()) {
                throw new InvalidArgumentException('Close all cashier shifts before changing branch currencies. The exchange rate may still be updated.');
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductBranchPrice::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
