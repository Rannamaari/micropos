<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\FinancialDocumentSnapshotService;
use Illuminate\Http\Request;

class AdminSaleReceiptController extends Controller
{
    public function __invoke(Request $request, Sale $sale, string $format, FinancialDocumentSnapshotService $financialDocumentSnapshotService)
    {
        abort_unless(in_array($format, ['thermal', 'a4'], true), 404);
        abort_unless($request->user()?->can('sales.view'), 403);
        abort_unless($request->user()?->hasRole('super-admin') || $sale->company_id === $request->user()?->company_id, 404);

        $printEvent = $financialDocumentSnapshotService->recordSalePrint($sale, $request->user(), $format);

        $snapshot = $financialDocumentSnapshotService->captureSale($sale, $sale->created_by, ! $sale->documentSnapshot()->exists());
        $document = $snapshot->snapshot;

        return view('sales.admin-receipt', compact('document', 'printEvent', 'format'));
    }
}
