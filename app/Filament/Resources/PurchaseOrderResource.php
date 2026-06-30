<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\PurchaseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Purchasing';
    protected static ?string $navigationLabel = 'Purchase Order';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi PO')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('outlet_id')
                            ->label('Outlet')
                            ->relationship('outlet', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang Tujuan')
                            ->relationship('warehouse', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(3),
                Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produk')
                                    ->relationship('product', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('unit_id')
                                    ->label('Satuan')
                                    ->relationship('unit', 'nama')
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->required()
                                    ->default(1),
                                Forms\Components\TextInput::make('harga_satuan')
                                    ->label('Harga Satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->default(0),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->createItemButtonLabel('Tambah Item'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No. PO')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.nama')
                    ->label('Supplier')
                    ->sortable(),
                Tables\Columns\TextColumn::make('outlet.nama')
                    ->label('Outlet')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('warehouse.nama')
                    ->label('Gudang'),
                Tables\Columns\TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'ordered' => 'info',
                        'partial' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'ordered' => 'Ordered',
                        'partial' => 'Partial',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (PurchaseOrder $record): bool => $record->status === 'draft'),
                Tables\Actions\Action::make('receiveGoods')
                    ->label('Terima Barang')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form(function (PurchaseOrder $record) {
                        return $record->items->map(function (PurchaseOrderItem $item) {
                            $remaining = (float) $item->qty - $item->totalReceived();
                            return Forms\Components\Section::make("{$item->product->nama} ({$item->unit->nama}) — Sisa: {$remaining}")
                                ->schema([
                                    Forms\Components\Hidden::make("items.{$item->id}.purchase_order_item_id")
                                        ->default($item->id),
                                    Forms\Components\TextInput::make("items.{$item->id}.qty_received")
                                        ->label('Qty Diterima')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue($remaining)
                                        ->default($remaining)
                                        ->required(),
                                    Forms\Components\TextInput::make("items.{$item->id}.batch_number")
                                        ->label('No. Batch')
                                        ->nullable(),
                                    Forms\Components\DatePicker::make("items.{$item->id}.expired_date")
                                        ->label('Expired Date')
                                        ->nullable(),
                                ])->columns(3);
                        })->toArray();
                    })
                    ->action(function (PurchaseOrder $record, array $data, PurchaseService $service) {
                        $items = collect($data['items'] ?? [])
                            ->filter(fn ($item) => ($item['qty_received'] ?? 0) > 0)
                            ->values()
                            ->toArray();

                        if (empty($items)) {
                            Notification::make()->title('Tidak ada item yang diterima')->warning()->send();
                            return;
                        }

                        $service->receiveGoods($record, $items);
                        Notification::make()->title('Barang berhasil diterima')->success()->send();
                    })
                    ->visible(fn (PurchaseOrder $record): bool => in_array($record->status, ['ordered', 'partial'])),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
