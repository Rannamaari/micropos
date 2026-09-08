<?php

namespace App\Models;

use App\Services\ProductSearchService;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'category_id',
        'brand_id',
        'unit_id',
        'sku',
        'name',
        'description',
        'cost_price',
        'selling_price',
        'wholesale_price',
        'tax_rate',
        'minimum_stock',
        'allow_negative_stock',
        'track_inventory',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'wholesale_price' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'minimum_stock' => 'decimal:4',
            'allow_negative_stock' => 'boolean',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product): void {
            // Check the database state again at deletion time, not an earlier catalog count.
            if ($reason = $product->fresh()?->deletionBlockReason()) {
                throw new LogicException($reason);
            }

            // Store-specific prices are configuration, not transaction history. Remove them before
            // deleting so older databases with a restrictive foreign key can delete unused products.
            $product->branchPrices()->delete();
        });

        static::saving(function (Product $product): void {
            foreach (['cost_price', 'selling_price', 'wholesale_price', 'tax_rate', 'minimum_stock'] as $field) {
                $value = $product->{$field};

                if ($value !== null && (float) $value < 0) {
                    throw new InvalidArgumentException("Product {$field} cannot be negative.");
                }
            }

            if ($product->category_id) {
                $category = Category::query()->find($product->category_id);

                if (! $category || $category->company_id !== $product->company_id) {
                    throw new InvalidArgumentException('Product category must belong to the same company.');
                }
            }

            if ($product->brand_id) {
                $brand = Brand::query()->find($product->brand_id);

                if (! $brand || $brand->company_id !== $product->company_id) {
                    throw new InvalidArgumentException('Product brand must belong to the same company.');
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function primaryBarcode(): HasOne
    {
        return $this->hasOne(ProductBarcode::class)->where('is_primary', true);
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function branchPrices(): HasMany
    {
        return $this->hasMany(ProductBranchPrice::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockCountItems(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function deletionBlockReason(): ?string
    {
        if ($this->hasRelatedRecords('saleItems')) {
            return 'This product has sales history and cannot be deleted.';
        }

        if ($this->hasRelatedRecords('purchaseItems')) {
            return 'This product has purchase history and cannot be deleted.';
        }

        if ($this->hasRelatedRecords('stockMovements') || $this->hasRelatedRecords('stockCountItems') || $this->hasRelatedRecords('inventoryBalances')) {
            return 'This product has inventory history and cannot be deleted.';
        }

        return null;
    }

    private function hasRelatedRecords(string $relation): bool
    {
        $countAttribute = Str::snake($relation).'_count';

        if (array_key_exists($countAttribute, $this->attributes)) {
            return (int) $this->getAttribute($countAttribute) > 0;
        }

        return $this->{$relation}()->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function searchForCompany(string $companyId, string $term, array $filters = []): Builder
    {
        return app(ProductSearchService::class)->search($companyId, $term, $filters);
    }

    public static function findByBarcode(string $companyId, string $barcode): ?self
    {
        return app(ProductSearchService::class)->findByBarcode($companyId, $barcode);
    }

    public static function findBySku(string $companyId, string $sku): ?self
    {
        return app(ProductSearchService::class)->findBySku($companyId, $sku);
    }

    public static function searchByName(string $companyId, string $query, array $filters = []): Builder
    {
        return app(ProductSearchService::class)->searchByName($companyId, $query, $filters);
    }
}
