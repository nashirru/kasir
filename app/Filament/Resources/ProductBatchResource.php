<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductBatchResource\Pages;
use App\Models\ProductBatch;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductBatchResource extends Resource
{
    protected static ?string $model = ProductBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Batch';
    protected static ?int $navigationSort = 2;

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
                Tables\Columns\TextColumn::make('warehouse.nama')
                    ->label('Gudang')
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('No. Batch')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expired_date')
                    ->label('Expired')
                    ->date()
                    ->sortable()
                    ->color(function (?string $state): ?string {
                        if (! $state) return null;
                        $days = now()->diffInDays(\Carbon\Carbon::parse($state), false);
                        if ($days < 0) return 'danger';
                        if ($days < 30) return 'warning';
                        return null;
                    }),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->defaultSort('expired_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->relationship('warehouse', 'nama'),
                Tables\Filters\Filter::make('expired')
                    ->label('Hampir Expired')
                    ->query(fn ($query) => $query->whereNotNull('expired_date')->where('expired_date', '<=', now()->addDays(30))),
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
            'index' => Pages\ListProductBatches::route('/'),
        ];
    }
}
