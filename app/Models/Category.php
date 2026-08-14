<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'parent_id',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            if (! $category->parent_id) {
                return;
            }

            if ($category->parent_id === $category->id) {
                throw new InvalidArgumentException('Category cannot be its own parent.');
            }

            $parent = Category::query()->find($category->parent_id);

            if (! $parent || $parent->company_id !== $category->company_id) {
                throw new InvalidArgumentException('Category parent must belong to the same company.');
            }

            $ancestor = $parent;

            while ($ancestor) {
                if ($ancestor->id === $category->id) {
                    throw new InvalidArgumentException('Category hierarchy cannot be circular.');
                }

                $ancestor = $ancestor->parent;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
