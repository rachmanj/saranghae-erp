<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorPaymentResource\Pages;
use App\Models\Partner;
use App\Models\PurchaseOrder;
use App\Models\VendorPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class VendorPaymentResource extends Resource
{
    protected static ?string $model = VendorPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Purchasing';
    
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Vendor Payments';
    }

    public static function getPluralLabel(): string
    {
        return 'Vendor Payments';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Payment Information')
                            ->schema([
                                Forms\Components\TextInput::make('payment_number')
                                    ->label('Payment Number')
                                    ->default(function () {
                                        return 'VP-' . date('Ymd') . '-' . str_pad(VendorPayment::count() + 1, 4, '0', STR_PAD_LEFT);
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                Forms\Components\Select::make('partner_id')
                                    ->label('Vendor')
                                    ->options(
                                        Partner::where('type', Partner::TYPE_VENDOR)->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->reactive()
                                    ->required(),
                                Forms\Components\Select::make('purchase_order_id')
                                    ->label('Purchase Order')
                                    ->options(function (callable $get) {
                                        $partnerId = $get('partner_id');
                                        if (!$partnerId) {
                                            return [];
                                        }
                                        
                                        return PurchaseOrder::where('partner_id', $partnerId)
                                            ->whereIn('payment_status', [
                                                PurchaseOrder::PAYMENT_UNPAID,
                                                PurchaseOrder::PAYMENT_PARTIALLY_PAID
                                            ])
                                            ->pluck('po_number', 'id');
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->required(),
                                Forms\Components\DatePicker::make('payment_date')
                                    ->label('Payment Date')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Payment Amount')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $get, callable $set, ?string $state) {
                                        $purchaseOrderId = $get('purchase_order_id');
                                        if (!$purchaseOrderId || !$state) {
                                            return;
                                        }
                                        
                                        $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
                                        if (!$purchaseOrder) {
                                            return;
                                        }
                                        
                                        $totalPaid = $purchaseOrder->payments()->sum('amount');
                                        $remainingAmount = $purchaseOrder->total_amount - $totalPaid;
                                        
                                        if ((float) $state > $remainingAmount) {
                                            $set('amount', $remainingAmount);
                                        }
                                    }),
                                Forms\Components\Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        VendorPayment::PAYMENT_METHOD_CASH => 'Cash',
                                        VendorPayment::PAYMENT_METHOD_BANK_TRANSFER => 'Bank Transfer',
                                        VendorPayment::PAYMENT_METHOD_CHECK => 'Check',
                                        VendorPayment::PAYMENT_METHOD_CREDIT_CARD => 'Credit Card',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Reference Number'),
                            ])
                            ->columns(2),
                            
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
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Payment Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('Purchase Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        VendorPayment::PAYMENT_METHOD_CASH => 'Cash',
                        VendorPayment::PAYMENT_METHOD_BANK_TRANSFER => 'Bank Transfer',
                        VendorPayment::PAYMENT_METHOD_CHECK => 'Check',
                        VendorPayment::PAYMENT_METHOD_CREDIT_CARD => 'Credit Card',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        VendorPayment::PAYMENT_METHOD_CASH => 'Cash',
                        VendorPayment::PAYMENT_METHOD_BANK_TRANSFER => 'Bank Transfer',
                        VendorPayment::PAYMENT_METHOD_CHECK => 'Check',
                        VendorPayment::PAYMENT_METHOD_CREDIT_CARD => 'Credit Card',
                    ]),
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('pdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (VendorPayment $record): string => route('vendor-payments.pdf', $record))
                        ->openUrlInNewTab(),
                ]),
            ]);
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Payment Details')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('payment_number')
                                ->label('Payment Number'),
                            Infolists\Components\TextEntry::make('payment_date')
                                ->label('Payment Date')
                                ->date(),
                            Infolists\Components\TextEntry::make('payment_method')
                                ->label('Payment Method')
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    VendorPayment::PAYMENT_METHOD_CASH => 'Cash',
                                    VendorPayment::PAYMENT_METHOD_BANK_TRANSFER => 'Bank Transfer',
                                    VendorPayment::PAYMENT_METHOD_CHECK => 'Check',
                                    VendorPayment::PAYMENT_METHOD_CREDIT_CARD => 'Credit Card',
                                    default => $state,
                                }),
                            Infolists\Components\TextEntry::make('reference_number')
                                ->label('Reference Number'),
                        ])->columns(4),
                        
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('vendor.name')
                                ->label('Vendor'),
                            Infolists\Components\TextEntry::make('purchaseOrder.po_number')
                                ->label('Purchase Order')
                                ->url(fn (VendorPayment $record): string => PurchaseOrderResource::getUrl('view', ['record' => $record->purchaseOrder])),
                            Infolists\Components\TextEntry::make('amount')
                                ->label('Amount')
                                ->money('USD'),
                        ])->columns(3),
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
            'index' => Pages\ListVendorPayments::route('/'),
            'create' => Pages\CreateVendorPayment::route('/create'),
            'view' => Pages\ViewVendorPayment::route('/{record}'),
        ];
    }
} 