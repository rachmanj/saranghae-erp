<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\FontFamily;
use Illuminate\Support\HtmlString;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'Purchasing';
    
    protected static ?int $navigationSort = 1;

    protected static array $unitMeasureOptions = [
        'pcs' => 'Pieces',
        'kg' => 'Kilogram',
        'g' => 'Gram',
        'l' => 'Liter',
        'ml' => 'Milliliter',
        'box' => 'Box',
        'pack' => 'Pack',
        'set' => 'Set',
        'unit' => 'Unit',
        'roll' => 'Roll',
        'sheet' => 'Sheet',
        'meter' => 'Meter',
        'cm' => 'Centimeter',
        'mm' => 'Millimeter',
    ];

    public static function getNavigationLabel(): string
    {
        return 'Purchase Orders';
    }

    public static function getPluralLabel(): string
    {
        return 'Purchase Orders';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Purchase Order Information')
                            ->schema([
                                Forms\Components\Select::make('partner_id')
                                    ->label('Vendor')
                                    ->options(
                                        Partner::where('type', Partner::TYPE_VENDOR)->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->required(),
                                Forms\Components\TextInput::make('po_number')
                                    ->label('Purchase Order Number')
                                    ->default(function () {
                                        return 'PO-' . date('Ymd') . '-' . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT);
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                Forms\Components\DatePicker::make('order_date')
                                    ->label('Order Date')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\DatePicker::make('expected_delivery_date')
                                    ->label('Expected Delivery Date'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        PurchaseOrder::STATUS_DRAFT => 'Draft',
                                        PurchaseOrder::STATUS_SENT => 'Sent',
                                        PurchaseOrder::STATUS_PARTIALLY_RECEIVED => 'Partially Received',
                                        PurchaseOrder::STATUS_FULLY_RECEIVED => 'Fully Received',
                                        PurchaseOrder::STATUS_CANCELLED => 'Cancelled',
                                    ])
                                    ->default(PurchaseOrder::STATUS_DRAFT)
                                    ->disabled(),
                                Forms\Components\Select::make('payment_status')
                                    ->options([
                                        PurchaseOrder::PAYMENT_UNPAID => 'Unpaid',
                                        PurchaseOrder::PAYMENT_PARTIALLY_PAID => 'Partially Paid',
                                        PurchaseOrder::PAYMENT_PAID => 'Paid',
                                    ])
                                    ->default(PurchaseOrder::PAYMENT_UNPAID)
                                    ->disabled(),
                                Forms\Components\Textarea::make('shipping_address')
                                    ->label('Shipping Address')
                                    ->rows(3),
                            ])
                            ->columns(2),
                            
                        Forms\Components\Section::make('Order Items')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('inventory_id')
                                            ->label('Inventory Item')
                                            ->relationship('inventory', 'name')
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->minValue(0.01)
                                            ->required()
                                            ->live(onBlur: true),
                                        Forms\Components\Select::make('unit')
                                            ->label('Unit')
                                            ->options(static::$unitMeasureOptions)
                                            ->searchable(),
                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->minValue(0)
                                            ->prefix('Rp')
                                            ->suffix(' IDR')
                                            ->required()
                                            ->live(onBlur: true),
                                        Forms\Components\TextInput::make('tax_rate')
                                            ->label('Tax Rate (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->live(onBlur: true),
                                        Forms\Components\TextInput::make('discount_percent')
                                            ->label('Discount (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->live(onBlur: true),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2),
                                        Forms\Components\TextInput::make('subtotal')
                                            ->label('Subtotal')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->suffix(' IDR')
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('tax_amount')
                                            ->label('Tax Amount')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->suffix(' IDR')
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('discount_amount')
                                            ->label('Discount Amount')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->suffix(' IDR')
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('total')
                                            ->label('Total')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->suffix(' IDR')
                                            ->disabled()
                                            ->dehydrated(),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(1)
                                    ->required()
                                    ->minItems(1)
                                    ->live(true)
                                    ->afterStateUpdated(function (Forms\Components\Repeater $component, Forms\Get $get, Forms\Set $set) {
                                        self::calculateTotals(
                                            $component, 
                                            function ($key) use ($get) {
                                                return $get($key);
                                            }, 
                                            function ($key, $value) use ($set) {
                                                $set($key, $value);
                                            }
                                        );
                                    })
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        // Calculate item values
                                        $itemSubtotal = $data['quantity'] * $data['unit_price'];
                                        $itemTaxAmount = $itemSubtotal * ($data['tax_rate'] ?? 0) / 100;
                                        $itemDiscountAmount = $itemSubtotal * ($data['discount_percent'] ?? 0) / 100;
                                        $itemTotal = $itemSubtotal + $itemTaxAmount - $itemDiscountAmount;
                                        
                                        $data['subtotal'] = $itemSubtotal;
                                        $data['tax_amount'] = $itemTaxAmount;
                                        $data['discount_amount'] = $itemDiscountAmount;
                                        $data['total'] = $itemTotal;
                                        
                                        return $data;
                                    })
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                        // Calculate item values
                                        $itemSubtotal = $data['quantity'] * $data['unit_price'];
                                        $itemTaxAmount = $itemSubtotal * ($data['tax_rate'] ?? 0) / 100;
                                        $itemDiscountAmount = $itemSubtotal * ($data['discount_percent'] ?? 0) / 100;
                                        $itemTotal = $itemSubtotal + $itemTaxAmount - $itemDiscountAmount;
                                        
                                        $data['subtotal'] = $itemSubtotal;
                                        $data['tax_amount'] = $itemTaxAmount;
                                        $data['discount_amount'] = $itemDiscountAmount;
                                        $data['total'] = $itemTotal;
                                        
                                        return $data;
                                    }),
                            ]),
                    ])
                    ->columnSpanFull(),
                    
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Notes')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(3),
                            ]),
                            
                        Forms\Components\Section::make('Amounts')
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->suffix(' IDR')
                                    ->disabled(),
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('Tax Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->suffix(' IDR')
                                    ->disabled(),
                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Discount Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->suffix(' IDR')
                                    ->disabled(),
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->suffix(' IDR')
                                    ->disabled(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function calculateTotals(Forms\Components\Repeater $component, $getData, $setData): void
    {
        $items = is_callable($getData) ? $getData('items') : $getData('items');
        $items = $items ?? [];
        $totalSubtotal = 0;
        $totalTaxAmount = 0;
        $totalDiscountAmount = 0;
        
        foreach ($items as $itemKey => $item) {
            if (!isset($item['quantity']) || !isset($item['unit_price'])) {
                continue;
            }
            
            $quantity = floatval($item['quantity'] ?? 0);
            $unitPrice = floatval($item['unit_price'] ?? 0);
            $taxRate = floatval($item['tax_rate'] ?? 0);
            $discountPercent = floatval($item['discount_percent'] ?? 0);
            
            $subtotal = $quantity * $unitPrice;
            $taxAmount = $subtotal * ($taxRate / 100);
            $discountAmount = $subtotal * ($discountPercent / 100);
            $total = $subtotal + $taxAmount - $discountAmount;
            
            // Update the individual item
            if (is_callable($setData)) {
                $setData("items.{$itemKey}.subtotal", round($subtotal, 2));
                $setData("items.{$itemKey}.tax_amount", round($taxAmount, 2));
                $setData("items.{$itemKey}.discount_amount", round($discountAmount, 2));
                $setData("items.{$itemKey}.total", round($total, 2));
            } else {
                $setData("items.{$itemKey}.subtotal", round($subtotal, 2));
                $setData("items.{$itemKey}.tax_amount", round($taxAmount, 2));
                $setData("items.{$itemKey}.discount_amount", round($discountAmount, 2));
                $setData("items.{$itemKey}.total", round($total, 2));
            }
            
            $totalSubtotal += $subtotal;
            $totalTaxAmount += $taxAmount;
            $totalDiscountAmount += $discountAmount;
        }
        
        $totalAmount = $totalSubtotal + $totalTaxAmount - $totalDiscountAmount;
        
        // Update the order totals
        if (is_callable($setData)) {
            $setData('subtotal', round($totalSubtotal, 2));
            $setData('tax_amount', round($totalTaxAmount, 2));
            $setData('discount_amount', round($totalDiscountAmount, 2));
            $setData('total_amount', round($totalAmount, 2));
        } else {
            $setData('subtotal', round($totalSubtotal, 2));
            $setData('tax_amount', round($totalTaxAmount, 2));
            $setData('discount_amount', round($totalDiscountAmount, 2));
            $setData('total_amount', round($totalAmount, 2));
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Order Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PurchaseOrder::STATUS_DRAFT => 'gray',
                        PurchaseOrder::STATUS_SENT => 'yellow',
                        PurchaseOrder::STATUS_PARTIALLY_RECEIVED => 'blue',
                        PurchaseOrder::STATUS_FULLY_RECEIVED => 'green',
                        PurchaseOrder::STATUS_CANCELLED => 'red',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PurchaseOrder::PAYMENT_UNPAID => 'red',
                        PurchaseOrder::PAYMENT_PARTIALLY_PAID => 'yellow',
                        PurchaseOrder::PAYMENT_PAID => 'green',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        PurchaseOrder::STATUS_DRAFT => 'Draft',
                        PurchaseOrder::STATUS_SENT => 'Sent',
                        PurchaseOrder::STATUS_PARTIALLY_RECEIVED => 'Partially Received',
                        PurchaseOrder::STATUS_FULLY_RECEIVED => 'Fully Received',
                        PurchaseOrder::STATUS_CANCELLED => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        PurchaseOrder::PAYMENT_UNPAID => 'Unpaid',
                        PurchaseOrder::PAYMENT_PARTIALLY_PAID => 'Partially Paid',
                        PurchaseOrder::PAYMENT_PAID => 'Paid',
                    ]),
                Tables\Filters\Filter::make('order_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (PurchaseOrder $record): bool => $record->status === PurchaseOrder::STATUS_DRAFT),
                    Tables\Actions\Action::make('send')
                        ->label('Mark as Sent')
                        ->icon('heroicon-o-paper-airplane')
                        ->visible(fn (PurchaseOrder $record): bool => $record->status === PurchaseOrder::STATUS_DRAFT)
                        ->action(function (PurchaseOrder $record): void {
                            $record->update(['status' => PurchaseOrder::STATUS_SENT]);
                        }),
                    Tables\Actions\Action::make('pdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (PurchaseOrder $record): string => route('purchase-orders.pdf', $record))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()->can('delete', PurchaseOrder::class)),
                ]),
            ]);
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Purchase Order Details')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('po_number')
                                ->label('PO Number'),
                            Infolists\Components\TextEntry::make('order_date')
                                ->label('Order Date')
                                ->date(),
                            Infolists\Components\TextEntry::make('expected_delivery_date')
                                ->label('Expected Delivery Date')
                                ->date(),
                        ])->columns(3),
                        
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('vendor.name')
                                ->label('Vendor'),
                            Infolists\Components\TextEntry::make('vendor.phone')
                                ->label('Phone'),
                            Infolists\Components\TextEntry::make('vendor.email')
                                ->label('Email'),
                        ])->columns(3),
                        
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    PurchaseOrder::STATUS_DRAFT => 'gray',
                                    PurchaseOrder::STATUS_SENT => 'yellow',
                                    PurchaseOrder::STATUS_PARTIALLY_RECEIVED => 'blue',
                                    PurchaseOrder::STATUS_FULLY_RECEIVED => 'green',
                                    PurchaseOrder::STATUS_CANCELLED => 'red',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('payment_status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    PurchaseOrder::PAYMENT_UNPAID => 'red',
                                    PurchaseOrder::PAYMENT_PARTIALLY_PAID => 'yellow',
                                    PurchaseOrder::PAYMENT_PAID => 'green',
                                    default => 'gray',
                                }),
                        ])->columns(2),
                    ]),
                    
                Infolists\Components\Section::make('Shipping Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('shipping_address')
                            ->label('Shipping Address')
                            ->columnSpanFull(),
                    ]),
                    
                Infolists\Components\Section::make('Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('inventory.name')
                                    ->label('Item'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Quantity'),
                                Infolists\Components\TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('tax_rate')
                                    ->label('Tax Rate')
                                    ->suffix('%'),
                                Infolists\Components\TextEntry::make('discount_percent')
                                    ->label('Discount')
                                    ->suffix('%'),
                                Infolists\Components\TextEntry::make('total')
                                    ->label('Total')
                                    ->money('IDR'),
                            ])
                            ->columns(6),
                    ]),
                    
                Infolists\Components\Section::make('Totals')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('subtotal')
                                ->label('Subtotal')
                                ->money('IDR'),
                            Infolists\Components\TextEntry::make('tax_amount')
                                ->label('Tax Amount')
                                ->money('IDR'),
                            Infolists\Components\TextEntry::make('discount_amount')
                                ->label('Discount Amount')
                                ->money('IDR'),
                            Infolists\Components\TextEntry::make('total_amount')
                                ->label('Total Amount')
                                ->money('IDR')
                                ->weight(FontWeight::Bold),
                        ])->columns(4),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
        ];
    }
} 