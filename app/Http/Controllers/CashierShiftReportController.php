<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use Illuminate\Http\Request;

class CashierShiftReportController extends Controller
{
    public function __invoke(Request $request, CashierShift $cashierShift)
    {
        $user = $request->user();

        abort_unless($user, 403);
        abort_unless($user->hasRole('super-admin') || $cashierShift->company_id === $user->company_id, 404);
        abort_unless($user->can('reports.view') || $cashierShift->cashier_id === $user->id, 403);
        abort_unless($cashierShift->status === 'closed', 422, 'An EOD report is available after the shift is closed.');

        $cashierShift->load(['company', 'branch', 'warehouse', 'cashier']);

        return view('reports.cashier-shift-a4', compact('cashierShift'));
    }
}
