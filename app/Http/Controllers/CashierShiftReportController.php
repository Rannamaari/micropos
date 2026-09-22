<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Services\FinancialDocumentSnapshotService;
use Illuminate\Http\Request;

class CashierShiftReportController extends Controller
{
    public function __invoke(Request $request, CashierShift $cashierShift, FinancialDocumentSnapshotService $financialDocumentSnapshotService)
    {
        $user = $request->user();

        abort_unless($user, 403);
        abort_unless($user->hasRole('super-admin') || $cashierShift->company_id === $user->company_id, 404);
        abort_unless($user->can('reports.view') || $cashierShift->cashier_id === $user->id, 403);
        abort_unless($cashierShift->status === 'closed', 422, 'An EOD report is available after the shift is closed.');

        $snapshot = $financialDocumentSnapshotService->captureCashierShift($cashierShift, $cashierShift->cashier_id, true);
        $financialDocumentSnapshotService->audit($cashierShift, 'printed', $user->id, ['format' => 'a4']);
        $document = $snapshot->snapshot;

        return view('reports.cashier-shift-a4', compact('document'));
    }
}
