<?php

namespace App\Enums;

enum CustomerTransactionType: string
{
    case OpeningBalance = 'opening_balance';
    case Sale = 'sale';
    case Payment = 'payment';
    case SaleReturn = 'sale_return';
    case Adjustment = 'adjustment';
}
