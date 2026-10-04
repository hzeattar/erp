<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'HR';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('latitude')
                ->required()
                ->numeric()
                ->minValue(-90)
                ->maxValue(90),
            TextInput::make('longitude')
                ->required()
                ->numeric()
                ->minValue(-180)
                ->maxValue(180),
            TextInput::make('allowed_radius_in_meters')
                ->label('Allowed radius (meters)')
                ->required()
                ->numeric()
                ->integer()
                ->minValue(1)
                ->default(50),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('latitude')->numeric(decimalPlaces: 7),
                TextColumn::make('longitude')->numeric(decimalPlaces: 7),
                TextColumn::make('allowed_radius_in_meters')
                    ->label('Radius (m)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('employees_count')
                    ->counts('employees')
                    ->label('Employees')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
