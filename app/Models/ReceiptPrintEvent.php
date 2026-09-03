<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptPrintEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id',
        'sale_id',
        'printed_by',
        'reprint_number',
        'format',
        'printed_at',
    ];

    protected function casts(): array
    {
        return ['printed_at' => 'datetime'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
