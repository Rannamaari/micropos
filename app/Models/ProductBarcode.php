<?php

namespace App\Models;

use Database\Factories\ProductBarcodeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ProductBarcode extends Model
{
    /** @use HasFactory<ProductBarcodeFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'company_id',
        'barcode',
        'is_primary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProductBarcode $barcode): void {
            $product = Product::query()->find($barcode->product_id);

            if (! $product || $product->company_id !== $barcode->company_id) {
                throw new InvalidArgumentException('Barcode company must match the product company.');
            }
        });

        static::saved(function (ProductBarcode $barcode): void {
            if (! $barcode->is_primary) {
                return;
            }

            self::query()
                ->where('product_id', $barcode->product_id)
                ->whereKeyNot($barcode->id)
                ->update(['is_primary' => false]);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
