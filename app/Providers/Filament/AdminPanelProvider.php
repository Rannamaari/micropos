<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\BestSellersTable;
use App\Filament\Widgets\BranchSalesTable;
use App\Filament\Widgets\DailyBranchSalesChart;
use App\Filament\Widgets\LowStockTable;
use App\Filament\Widgets\OperationsOverview;
use App\Http\Middleware\SetApplicationLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label(fn (): string => __('nav.catalog')),
                NavigationGroup::make()->label(fn (): string => __('nav.inventory')),
                NavigationGroup::make()->label(fn (): string => __('nav.purchasing')),
                NavigationGroup::make()->label(fn (): string => __('nav.customers')),
                NavigationGroup::make()->label(fn (): string => __('nav.sales')),
                NavigationGroup::make()->label(fn (): string => __('nav.reports')),
                NavigationGroup::make()->label(fn (): string => __('nav.administration')),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                OperationsOverview::class,
                DailyBranchSalesChart::class,
                BranchSalesTable::class,
                BestSellersTable::class,
                LowStockTable::class,
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): View => view('filament.partials.locale-toggle'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetApplicationLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
