<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBranchPrice extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id', 'branch_id', 'product_id', 'currency', 'cost_price', 'selling_price', 'wholesale_price',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'wholesale_price' => 'decimal:4',
        ];
    }

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
