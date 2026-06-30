<?php

namespace App\Filament\Widgets;

use App\Models\CashRegister;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ShiftStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.shift-status-widget';

    protected int|string|array $columnSpan = 12;

    public ?CashRegister $activeShift = null;
    public float $expectedBalance = 0;
    public float $totalSales = 0;
    public int $totalTransactions = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $this->activeShift = CashRegister::where('user_id', $user->id)
            ->where('outlet_id', $user->outlet_id)
            ->where('status', 'open')
            ->first();

        if ($this->activeShift) {
            $this->totalSales = $this->activeShift->totalSales();
            $this->expectedBalance = $this->activeShift->expectedBalance();
            $this->totalTransactions = $this->activeShift->sales()
                ->where('status', 'completed')
                ->count();
        }
    }

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()->getId() === 'kasir';
    }

    public static function getSort(): int
    {
        return 0;
    }
}
