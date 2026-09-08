<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\StockCountItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProductPurgeService
{
    public function purgeInventoryOnlyProduct(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            if ($reason = $this->blockReason($product)) {
                throw new LogicException($reason);
            }

            StockCountItem::query()->where('product_id', $product->id)->delete();
            StockMovement::query()->where('product_id', $product->id)->delete();
            InventoryBalance::query()->where('product_id', $product->id)->delete();

            $product->delete();
        });
    }

    public function blockReason(Product $product): ?string
    {
        if ($product->saleItems()->exists() || DB::table('sale_return_items')->where('product_id', $product->id)->exists()) {
            return 'Products with sales history cannot be purged.';
        }

        if ($product->purchaseItems()->exists() || DB::table('purchase_return_items')->where('product_id', $product->id)->exists()) {
            return 'Products with purchase history cannot be purged.';
        }

        return null;
    }
}
