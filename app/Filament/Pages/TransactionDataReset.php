<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminSupport;
use App\Services\TransactionDataResetService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TransactionDataReset extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Test Data Reset';

    protected static ?string $title = 'Reset Test Transaction Data';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected string $view = 'filament.pages.transaction-data-reset';

    public string $confirmation = '';

    public static function canAccess(): bool
    {
        return (bool) AdminSupport::user()?->hasRole('super-admin');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess() && AdminSupport::companyId(), 403);
    }

    public function resetSales(TransactionDataResetService $resetService): void
    {
        $this->validate(['confirmation' => ['required', 'in:RESET SALES']]);
        $result = $resetService->resetSales(AdminSupport::companyId());
        $this->confirmation = '';

        Notification::make()->title("{$result['sales']} sales and {$result['sale_returns']} returns removed")->success()->send();
    }

    public function resetAllTransactions(TransactionDataResetService $resetService): void
    {
        $this->validate(['confirmation' => ['required', 'in:RESET ALL TRANSACTIONS']]);
        $result = $resetService->resetAllTransactions(AdminSupport::companyId());
        $this->confirmation = '';

        Notification::make()->title("All test transactions removed ({$result['sales']} sales, {$result['purchases']} purchases)")->success()->send();
    }
}
