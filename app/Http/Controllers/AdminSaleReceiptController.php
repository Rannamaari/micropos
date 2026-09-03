<?php

namespace App\Http\Controllers;

use App\Models\ReceiptPrintEvent;
use App\Models\Sale;
use App\Services\ReceiptProfileResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSaleReceiptController extends Controller
{
    public function __invoke(Request $request, Sale $sale, string $format, ReceiptProfileResolver $receiptProfileResolver)
    {
        abort_unless(in_array($format, ['thermal', 'a4'], true), 404);
        abort_unless($request->user()?->can('sales.view'), 403);
        abort_unless($request->user()?->hasRole('super-admin') || $sale->company_id === $request->user()?->company_id, 404);

        $printEvent = DB::transaction(function () use ($request, $sale, $format): ReceiptPrintEvent {
            $lockedSale = Sale::query()->where('company_id', $sale->company_id)->lockForUpdate()->findOrFail($sale->id);
            $nextNumber = (int) ReceiptPrintEvent::query()->where('sale_id', $lockedSale->id)->max('reprint_number') + 1;

            return ReceiptPrintEvent::query()->create([
                'company_id' => $lockedSale->company_id,
                'sale_id' => $lockedSale->id,
                'printed_by' => $request->user()?->id,
                'reprint_number' => $nextNumber,
                'format' => $format,
                'printed_at' => now(),
            ]);
        });

        $sale->load(['company', 'branch', 'customer', 'creator', 'items.product', 'payments']);
        $receipt = $sale->receipt_snapshot ?: $receiptProfileResolver->resolve($sale->company, $sale->branch);

        return view('sales.admin-receipt', compact('sale', 'receipt', 'printEvent', 'format'));
    }
}
