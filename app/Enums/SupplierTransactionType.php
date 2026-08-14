<?php

namespace App\Enums;

enum SupplierTransactionType: string
{
    case OpeningBalance = 'opening_balance';
    case Purchase = 'purchase';
    case Payment = 'payment';
    case PurchaseReturn = 'purchase_return';
    case Adjustment = 'adjustment';
}
