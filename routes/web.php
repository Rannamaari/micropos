<?php

use App\Http\Controllers\AdminSaleReceiptController;
use App\Http\Controllers\CashierShiftReportController;
use App\Http\Controllers\PosApiController;
use App\Http\Controllers\PosPageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/locale/{locale}', function (Request $request, string $locale) {
    abort_unless(in_array($locale, ['en', 'dv'], true), 404);
    $request->session()->put('locale', $locale);

    return back();
})->name('locale.switch');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('pos.index'));
    })->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::get('/pos', PosPageController::class)->name('pos.index');
    Route::get('/admin/sales/{sale}/receipt/{format}', AdminSaleReceiptController::class)->name('admin.sales.receipt');
    Route::get('/reports/cashier-shifts/{cashierShift}/print', CashierShiftReportController::class)->name('cashier-shifts.print');

    Route::prefix('/pos/api')->group(function (): void {
        Route::get('/products/search', [PosApiController::class, 'searchProducts'])->name('pos.products.search');
        Route::get('/products/barcode/{barcode}', [PosApiController::class, 'barcodeLookup'])->name('pos.products.barcode');
        Route::post('/shifts/open', [PosApiController::class, 'openShift'])->name('pos.shifts.open');
        Route::post('/shifts/{cashierShift}/close', [PosApiController::class, 'closeShift'])->name('pos.shifts.close');

        Route::get('/customers/search', [PosApiController::class, 'searchCustomers'])->name('pos.customers.search');
        Route::post('/customers', [PosApiController::class, 'createCustomer'])->name('pos.customers.store');

        Route::get('/held-sales', [PosApiController::class, 'heldSales'])->name('pos.held-sales.index');
        Route::get('/sales', [PosApiController::class, 'listSales'])->name('pos.sales.index');
        Route::get('/sales/search', [PosApiController::class, 'searchSales'])->name('pos.sales.search');
        Route::get('/sales/{sale}/resume', [PosApiController::class, 'resumeHeldSale'])->name('pos.sales.resume');
        Route::get('/sales/{sale}', [PosApiController::class, 'showSale'])->name('pos.sales.show');
        Route::post('/sales', [PosApiController::class, 'completeSale'])->name('pos.sales.store');
        Route::post('/sales/hold', [PosApiController::class, 'holdSale'])->name('pos.sales.hold');
        Route::post('/sales/{sale}/hold', [PosApiController::class, 'reholdSale'])->name('pos.sales.rehold');
        Route::post('/sales/{sale}/complete', [PosApiController::class, 'completeHeldSale'])->name('pos.sales.complete');
        Route::post('/sales/{sale}/cancel-held', [PosApiController::class, 'cancelHeldSale'])->name('pos.sales.cancel-held');
        Route::post('/sales/{sale}/returns', [PosApiController::class, 'returnSale'])->name('pos.sales.return');
    });
});
