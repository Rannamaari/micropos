<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class FinancialDocumentSnapshot extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'is_reconstructed' => 'boolean',
            'finalized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Financial document snapshots are immutable. Create a correction document instead.'));
        static::deleting(fn (): never => throw new LogicException('Financial document snapshots are immutable.'));
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
