<?php

namespace App\Support;

class InventoryStatus
{
    public const IN_STOCK = 'IN STOCK';

    public const LOW_STOCK = 'LOW STOCK';

    public const OUT_OF_STOCK = 'OUT OF STOCK';

    public const NEGATIVE_STOCK = 'NEGATIVE STOCK';

    public const NON_STOCK = 'NON-STOCK';

    public static function forProduct(bool $tracksInventory, float $quantity, float $minimumStock): string
    {
        if (! $tracksInventory) {
            return self::NON_STOCK;
        }

        if ($quantity < 0) {
            return self::NEGATIVE_STOCK;
        }

        if ($quantity == 0.0) {
            return self::OUT_OF_STOCK;
        }

        if ($quantity <= $minimumStock) {
            return self::LOW_STOCK;
        }

        return self::IN_STOCK;
    }

    public static function color(string $status): string
    {
        return match ($status) {
            self::IN_STOCK => 'success',
            self::LOW_STOCK => 'warning',
            self::OUT_OF_STOCK, self::NEGATIVE_STOCK => 'danger',
            default => 'gray',
        };
    }
}
