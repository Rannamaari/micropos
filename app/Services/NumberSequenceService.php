<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class NumberSequenceService
{
    private const PREFIXES = [
        'purchase' => 'PUR',
        'sale' => 'SAL',
        'purchase_return' => 'PRN',
        'sale_return' => 'SRN',
    ];

    public function next(string $companyId, string $type): string
    {
        return DB::transaction(function () use ($companyId, $type): string {
            $sequence = DocumentSequence::query()
                ->where('company_id', $companyId)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = DocumentSequence::query()->create([
                    'company_id' => $companyId,
                    'type' => $type,
                    'current_number' => 0,
                ]);

                $sequence = $sequence->fresh();
            }

            $sequence->current_number++;
            $sequence->save();

            $prefix = self::PREFIXES[$type] ?? strtoupper(substr($type, 0, 3));

            return sprintf('%s-%06d', $prefix, $sequence->current_number);
        });
    }
}
