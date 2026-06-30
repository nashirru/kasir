<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard;

class KasirDashboard extends Dashboard
{
    protected static ?string $title = 'Dashboard Kasir';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    protected static string $routePath = '/';

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return Filament::getWidgets();
    }

    public function getColumns(): int | string | array
    {
        return 12;
    }
}
