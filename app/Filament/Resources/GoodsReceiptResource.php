<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    
    protected static ?string $navigationGroup = 'Purchasing';
    
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Goods Receipts';
    }

    public static function getPluralLabel(): string
    {
        return 'Goods Receipts';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Goods Receipt Information')
                            ->schema([
                                Forms\Components\TextInput::make('receipt_number')
                                    ->label('Receipt Number')
                                    ->default(function () {
                                        return 'GR-' . date('Ymd') . '-' . str_pad(GoodsReceipt::count() + 1, 4, '0', STR_PAD_LEFT);
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                Forms\Components\Select::make('purchase_order_id')
                                    ->label('Purchase Order')
                                    ->options(
                                        PurchaseOrder::whereIn('status', [
                                            PurchaseOrder::STATUS_SENT,
                                            PurchaseOrder::STATUS_PARTIALLY_RECEIVED
                                        ])->pluck('po_number', 'id')
                                    )
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $purchaseOrder = PurchaseOrder::find($state);
                                            if ($purchaseOrder) {
                                                $set('partner_id', $purchaseOrder->partner_id);
                                            }
                                        }
                                    }),
                                Forms\Components\Select::make('warehouse_id')
                                    ->label('Warehouse')
                                    ->options(
                                        Warehouse::where('is_active', true)->pluck('wh_desc', 'id')
                                    )
                                    ->searchable()
                                    ->required(),
                                Forms\Components\DatePicker::make('receipt_date')
                                    ->label('Receipt Date')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\TextInput::make('delivery_note_number')
                                    ->label('Delivery Note Number'),
                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Reference Number'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
                    
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Items to Receive')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('purchase_order_item_id')
                                            ->label('Purchase Order Item')
                                            ->options(function (callable $get) {
                                                $purchaseOrderId = $get('../../purchase_order_id');
                                                if (!$purchaseOrderId) {
                                                    return [];
                                                }
                                                
                                                $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
                                                if (!$purchaseOrder) {
                                                    return [];
                                                }
                                                
                                                $options = [];
                                                foreach ($purchaseOrder->items as $item) {
                                                    $receivedQty = $item->receivedItems->sum('quantity');
                                                    $remainingQty = $item->quantity - $receivedQty;
                                                    
                                                    if ($remainingQty > 0) {
                                                        $options[$item->id] = $item->inventory->name . ' - ' . 
                                                            'Ordered: ' . $item->quantity . ', ' . 
                                                            'Remaining: ' . $remainingQty;
                                                    }
                                                }
                                                
                                                return $options;
                                            })
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $poItem = \App\Models\PurchaseOrderItem::find($state);
                                                    if ($poItem) {
                                                        $set('inventory_id', $poItem->inventory_id);
                                                        $receivedQty = $poItem->receivedItems->sum('quantity');
                                                        $remainingQty = $poItem->quantity - $receivedQty;
                                                        $set('quantity', $remainingQty);
                                                    }
                                                }
                                            })
                                            ->required(),
                                        Forms\Components\Select::make('inventory_id')
                                            ->label('Inventory Item')
                                            ->relationship('inventory', 'name')
                                            ->disabled()
                                            ->dehydrated()
                                            ->required(),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity to Receive')
                                            ->numeric()
                                            ->minValue(0.01)
                                            ->required(),
                                        Forms\Components\TextInput::make('lot_number')
                                            ->label('Lot Number'),
                                        Forms\Components\DatePicker::make('expiry_date')
                                            ->label('Expiry Date'),
                                        Forms\Components\TextInput::make('location_in_warehouse')
                                            ->label('Location in Warehouse'),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(1)
                                    ->required()
                                    ->minItems(1),
                            ]),
                            
                        Forms\Components\Section::make('Notes')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(3),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Receipt Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('Purchase Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.vendor.name')
                    ->label('Vendor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouse.wh_desc')
                    ->label('Warehouse')
                    ->sortable(),
                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('Receipt Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('receipt_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('pdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (GoodsReceipt $record): string => route('goods-receipts.pdf', $record))
                        ->openUrlInNewTab(),
                ]),
            ]);
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Goods Receipt Details')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('receipt_number')
                                ->label('Receipt Number'),
                            Infolists\Components\TextEntry::make('receipt_date')
                                ->label('Receipt Date')
                                ->date(),
                            Infolists\Components\TextEntry::make('delivery_note_number')
                                ->label('Delivery Note Number'),
                            Infolists\Components\TextEntry::make('reference_number')
                                ->label('Reference Number'),
                        ])->columns(4),
                        
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('purchaseOrder.po_number')
                                ->label('Purchase Order')
                                ->url(fn (GoodsReceipt $record): string => PurchaseOrderResource::getUrl('view', ['record' => $record->purchaseOrder])),
                            Infolists\Components\TextEntry::make('purchaseOrder.vendor.name')
                                ->label('Vendor'),
                            Infolists\Components\TextEntry::make('warehouse.wh_desc')
                                ->label('Warehouse'),
                        ])->columns(3),
                    ]),
                    
                Infolists\Components\Section::make('Received Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('inventory.name')
                                    ->label('Item'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Quantity'),
                                Infolists\Components\TextEntry::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make('lot_number')
                                    ->label('Lot Number'),
                                Infolists\Components\TextEntry::make('expiry_date')
                                    ->label('Expiry Date')
                                    ->date(),
                                Infolists\Components\TextEntry::make('location_in_warehouse')
                                    ->label('Location'),
                            ])
                            ->columns(6),
                    ]),
                    
                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
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
            'index' => Pages\ListGoodsReceipts::route('/'),
            'create' => Pages\CreateGoodsReceipt::route('/create'),
            'view' => Pages\ViewGoodsReceipt::route('/{record}'),
        ];
    }
} 