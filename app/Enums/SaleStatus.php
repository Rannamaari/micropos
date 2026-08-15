<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Draft = 'draft';
    case Held = 'held';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Voided = 'voided';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
}
