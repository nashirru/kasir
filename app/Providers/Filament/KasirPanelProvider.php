<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CashRegisterShift;
use App\Filament\Pages\KasirDashboard;
use App\Filament\Pages\PointOfSale;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentTransactionsWidget;
use App\Filament\Widgets\ShiftStatusWidget;
use App\Filament\Widgets\TodaySalesWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class KasirPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('kasir')
            ->path('kasir')
            ->login()
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->brandName('Kasir')
            ->navigationGroups([
                NavigationGroup::make('Transaksi')
                    ->icon('heroicon-o-shopping-cart'),
            ])
            ->pages([
                KasirDashboard::class,
                PointOfSale::class,
                CashRegisterShift::class,
            ])
            ->widgets([
                ShiftStatusWidget::class,
                TodaySalesWidget::class,
                RecentTransactionsWidget::class,
                QuickActionsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
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
