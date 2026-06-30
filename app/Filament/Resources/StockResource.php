<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Stock;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Stok Saldo';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.nama')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('warehouse.nama')
                    ->label('Gudang')
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.outlet.nama')
                    ->label('Outlet'),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Stok')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn (float $state): ?string => $state <= 0 ? 'danger' : null)
                    ->weight(fn (float $state): ?string => $state <= 0 ? 'bold' : null),
            ])
            ->defaultSort('qty', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->relationship('warehouse', 'nama'),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),
        ];
    }
}
