<?php

namespace App\Filament\Widgets;

use App\Models\CashRegister;
use App\Models\Sale;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentTransactionsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 8;

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $register = CashRegister::where('user_id', $user->id)
            ->where('outlet_id', $user->outlet_id)
            ->where('status', 'open')
            ->first();

        return $table
            ->query(
                Sale::where('cash_register_id', $register?->id ?? 0)
                    ->where('status', 'completed')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('H:i')
                    ->color('gray'),
            ])
            ->heading('Transaksi Terbaru')
            ->emptyStateHeading('Belum ada transaksi')
            ->emptyStateDescription('Transaksi akan muncul di sini setelah kamu melakukan penjualan.')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()->getId() === 'kasir';
    }

    public static function getSort(): int
    {
        return 2;
    }
}
