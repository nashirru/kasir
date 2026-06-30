<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CashRegisterShift;
use App\Filament\Pages\PointOfSale;
use App\Models\CashRegister;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions-widget';

    protected int|string|array $columnSpan = 4;

    public bool $hasActiveShift = false;
    public string $posUrl = '';
    public string $shiftUrl = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->hasActiveShift = CashRegister::where('user_id', $user->id)
            ->where('outlet_id', $user->outlet_id)
            ->where('status', 'open')
            ->exists();

        $this->posUrl = PointOfSale::getUrl();
        $this->shiftUrl = CashRegisterShift::getUrl();
    }

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()->getId() === 'kasir';
    }

    public static function getSort(): int
    {
        return 3;
    }
}
