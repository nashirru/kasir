<?php

namespace App\Filament\Widgets;

use App\Models\CashRegister;
use App\Models\Sale;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TodaySalesWidget extends Widget
{
    protected static string $view = 'filament.widgets.today-sales-widget';

    protected int|string|array $columnSpan = 12;

    public int $totalTransactions = 0;
    public float $totalRevenue = 0;
    public float $averagePerTransaction = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $register = CashRegister::where('user_id', $user->id)
            ->where('outlet_id', $user->outlet_id)
            ->where('status', 'open')
            ->first();

        if ($register) {
            $salesQuery = Sale::where('cash_register_id', $register->id)
                ->where('status', 'completed');

            $this->totalTransactions = (clone $salesQuery)->count();
            $this->totalRevenue = (float) (clone $salesQuery)->sum('total');
            $this->averagePerTransaction = $this->totalTransactions > 0
                ? $this->totalRevenue / $this->totalTransactions
                : 0;
        }
    }

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()->getId() === 'kasir';
    }

    public static function getSort(): int
    {
        return 1;
    }
}
