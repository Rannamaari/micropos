<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Opening = 'opening';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case Damage = 'damage';
    case Loss = 'loss';
    case Correction = 'correction';
    case Purchase = 'purchase';
    case PurchaseReturn = 'purchase_return';
    case Sale = 'sale';
    case SaleReturn = 'sale_return';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case StockCount = 'stock_count';
}
