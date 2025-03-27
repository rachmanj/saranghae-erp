<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Filament\Resources\PartnerResource\RelationManagers;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Business Partner';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $label = 'Business Partner';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\Select::make('type')->options([
                    'vendor' => 'Vendor',
                    'customer' => 'Customer',
                ]),
                Forms\Components\TextInput::make('address')->nullable(),
                Forms\Components\TextInput::make('city')->nullable(),
                Forms\Components\TextInput::make('province')->nullable(),
                Forms\Components\TextInput::make('postal_code')->nullable(),
                Forms\Components\TextInput::make('phone')->nullable(),
                Forms\Components\TextInput::make('email')->nullable(),
                Forms\Components\TextInput::make('website')->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('address')->wrap(),
                Tables\Columns\TextColumn::make('city')->wrap(),
                Tables\Columns\TextColumn::make('province')->wrap(),
                Tables\Columns\TextColumn::make('postal_code')->wrap(),
                Tables\Columns\TextColumn::make('phone')->wrap(),
                Tables\Columns\TextColumn::make('email')->wrap(),
                Tables\Columns\TextColumn::make('website')->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'vendor' => 'Vendor',
                    'customer' => 'Customer',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->modalDescription('Are you sure you want to delete this partner?'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
