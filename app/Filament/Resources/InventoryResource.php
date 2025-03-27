<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers;
use App\Models\Inventory;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationGroup = 'Inventory Management';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inventory Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('category')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('unit_price')
                            ->numeric()
                            ->prefix('Rp')
                            ->suffix(' IDR'),
                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'discontinued' => 'Discontinued',
                            ])
                            ->default('active'),
                        Forms\Components\Select::make('unit_of_measure')
                            ->options(static::$unitMeasureOptions)
                            ->searchable()
                            ->allowHtml()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('value')
                                    ->label('Unit Measure Code')
                                    ->required()
                                    ->maxLength(10),
                                Forms\Components\TextInput::make('label')
                                    ->label('Unit Measure Name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data) {
                                static::$unitMeasureOptions[$data['value']] = $data['label'];
                                return $data['value'];
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_of_measure')
                    ->formatStateUsing(fn (string $state): string => static::$unitMeasureOptions[$state] ?? $state)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'inactive',
                        'warning' => 'discontinued',
                        'success' => 'active',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'discontinued' => 'Discontinued',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PartnersRelationManager::class,
            RelationManagers\WarehousesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
