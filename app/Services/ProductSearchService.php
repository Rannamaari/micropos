<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBarcode;
use Illuminate\Database\Eloquent\Builder;

class ProductSearchService
{
    /**
     * @return array<int, string>
     */
    private function posLookupRelations(): array
    {
        return ['unit', 'primaryBarcode'];
    }

    /**
     * @return array<int, string>
     */
    private function manualSearchRelations(): array
    {
        return ['category', 'brand', 'unit', 'primaryBarcode'];
    }

    public function findByBarcode(string $companyId, string $barcode): ?Product
    {
        $barcode = trim($barcode);

        if ($barcode === '') {
            return null;
        }

        return Product::query()
            ->select('products.*')
            ->join('product_barcodes', function ($join) use ($companyId, $barcode): void {
                $join->on('product_barcodes.product_id', '=', 'products.id')
                    ->where('product_barcodes.company_id', '=', $companyId)
                    ->where('product_barcodes.barcode', '=', $barcode);
            })
            ->with($this->posLookupRelations())
            ->where('products.company_id', $companyId)
            ->where('products.is_active', true)
            ->first();
    }

    public function findBySku(string $companyId, string $sku): ?Product
    {
        $sku = trim($sku);

        if ($sku === '') {
            return null;
        }

        return Product::query()
            ->with($this->posLookupRelations())
            ->where('company_id', $companyId)
            ->where('sku', $sku)
            ->where('is_active', true)
            ->first();
    }

    public function search(string $companyId, string $term, array $filters = []): Builder
    {
        $term = trim($term);
        $activeOnly = $filters['active_only'] ?? true;

        if ($term === '') {
            return $this->baseSearchQuery($companyId, $filters, $activeOnly);
        }

        if ($barcodeProductId = $this->barcodeMatchProductId($companyId, $term, $activeOnly)) {
            return $this->baseSearchQuery($companyId, $filters, $activeOnly)->whereKey($barcodeProductId);
        }

        if ($skuProductId = $this->skuMatchProductId($companyId, $term, $activeOnly)) {
            return $this->baseSearchQuery($companyId, $filters, $activeOnly)->whereKey($skuProductId);
        }

        return $this->searchByName($companyId, $term, $filters, $activeOnly);
    }

    public function searchByName(string $companyId, string $query, array $filters = [], bool $activeOnly = true): Builder
    {
        $query = trim($query);
        $likeTerm = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%';

        return $this->baseSearchQuery($companyId, $filters, $activeOnly)
            ->where('products.name', 'like', $likeTerm)
            ->orderBy('products.name');
    }

    private function baseSearchQuery(string $companyId, array $filters, bool $activeOnly): Builder
    {
        $query = Product::query()
            ->with($this->manualSearchRelations())
            ->where('products.company_id', $companyId);

        if ($activeOnly) {
            $query->where('products.is_active', true);
        }

        if ($categoryId = $filters['category_id'] ?? null) {
            $query->where('products.category_id', $categoryId);
        }

        if ($brandId = $filters['brand_id'] ?? null) {
            $query->where('products.brand_id', $brandId);
        }

        return $query;
    }

    private function barcodeMatchProductId(string $companyId, string $barcode, bool $activeOnly): ?string
    {
        $query = ProductBarcode::query()
            ->join('products', 'products.id', '=', 'product_barcodes.product_id')
            ->where('product_barcodes.company_id', $companyId)
            ->where('product_barcodes.barcode', $barcode)
            ->where('products.company_id', $companyId);

        if ($activeOnly) {
            $query->where('products.is_active', true);
        }

        return $query->value('products.id');
    }

    private function skuMatchProductId(string $companyId, string $sku, bool $activeOnly): ?string
    {
        $query = Product::query()
            ->where('company_id', $companyId)
            ->where('sku', $sku);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->value('id');
    }
}
