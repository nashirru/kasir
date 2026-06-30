<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Filament\Resources\StockTransferResource\RelationManagers;
use App\Models\StockTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Transfer Stok';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Transfer')
                    ->schema([
                        Forms\Components\Select::make('from_warehouse_id')
                            ->label('Gudang Asal')
                            ->relationship('fromWarehouse', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('to_warehouse_id', null);
                            }),
                        Forms\Components\Select::make('to_warehouse_id')
                            ->label('Gudang Tujuan')
                            ->relationship('toWarehouse', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (Forms\Get $get): bool => blank($get('from_warehouse_id')))
                            ->options(function (Forms\Get $get) {
                                $fromId = $get('from_warehouse_id');
                                if (blank($fromId)) {
                                    return [];
                                }
                                return \App\Models\Warehouse::where('id', '!=', $fromId)->pluck('nama', 'id');
                            }),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'received' => 'Received',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending')
                            ->visibleOn('edit'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fromWarehouse.nama')
                    ->label('Gudang Asal')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('toWarehouse.nama')
                    ->label('Gudang Tujuan')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'received' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Ditolak',
                        'received' => 'Diterima',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Diminta Oleh')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Ditolak',
                        'received' => 'Diterima',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Tables\Filters\SelectFilter::make('from_warehouse_id')
                    ->label('Gudang Asal')
                    ->relationship('fromWarehouse', 'nama')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('to_warehouse_id')
                    ->label('Gudang Tujuan')
                    ->relationship('toWarehouse', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (StockTransfer $record): bool => $record->status === 'pending'),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (StockTransfer $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()->title('Transfer stok disetujui')->success()->send();
                    })
                    ->visible(fn (StockTransfer $record): bool => $record->status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (StockTransfer $record) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()->title('Transfer stok ditolak')->danger()->send();
                    })
                    ->visible(fn (StockTransfer $record): bool => $record->status === 'pending'),
                Tables\Actions\Action::make('receive')
                    ->label('Terima')
                    ->icon('heroicon-inbox-arrow-down')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (StockTransfer $record) {
                        $record->update([
                            'status' => 'received',
                            'received_by' => auth()->id(),
                        ]);
                        Notification::make()->title('Barang berhasil diterima')->success()->send();
                    })
                    ->visible(fn (StockTransfer $record): bool => $record->status === 'approved'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (StockTransfer $record): bool => in_array($record->status, ['pending', 'rejected', 'cancelled'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->role === 'super_admin'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
