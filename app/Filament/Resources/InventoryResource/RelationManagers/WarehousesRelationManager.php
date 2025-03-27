<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarehousesRelationManager extends RelationManager
{
    protected static string $relationship = 'warehouseStocks';

    protected static ?string $recordTitleAttribute = 'warehouse';

    protected static ?string $title = 'Warehouse Stocks';

    public function form(Form $form): Form
    {
        // Get list of warehouses from the database
        $warehouses = Warehouse::where('is_active', true)
            ->get()
            ->pluck('wh_desc', 'wh_code')
            ->toArray();

        return $form
            ->schema([
                Forms\Components\Select::make('warehouse')
                    ->label('Warehouse')
                    ->options($warehouses)
                    ->required()
                    ->searchable()
                    ->disabledOn('edit'),
                Forms\Components\TextInput::make('stock_quantity')
                    ->label('Stock Quantity')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('stock_value')
                    ->label('Stock Value')
                    ->numeric()
                    ->prefix('Rp')
                    ->suffix(' IDR'),
                Forms\Components\TextInput::make('location_in_warehouse')
                    ->label('Location in Warehouse')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warehouse')
            ->columns([
                Tables\Columns\TextColumn::make('warehouse')
                    ->label('Warehouse')
                    ->formatStateUsing(function ($state) {
                        $warehouse = Warehouse::where('wh_code', $state)->first();
                        return $warehouse ? "{$warehouse->wh_desc} ({$state})" : $state;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Value')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('location_in_warehouse')
                    ->label('Location')
                    ->searchable(),
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
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 