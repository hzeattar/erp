<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\StockTransfer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('from_warehouse_id')
                ->relationship('fromWarehouse', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('to_warehouse_id')
                ->relationship(
                    name: 'toWarehouse',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->where('type', 'contractor'),
                )
                ->searchable()
                ->preload()
                ->required(),
            Select::make('item_id')
                ->relationship('item', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('quantity')
                ->required()
                ->numeric()
                ->integer()
                ->minValue(1),
            DatePicker::make('transfer_date')
                ->required()
                ->default(now()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fromWarehouse.name')->label('From')->searchable(),
                TextColumn::make('toWarehouse.name')->label('To contractor')->searchable(),
                TextColumn::make('item.name')->label('Item')->searchable(),
                TextColumn::make('quantity')->numeric()->sortable(),
                TextColumn::make('transfer_date')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('to_warehouse_id')
                    ->label('Contractor warehouse')
                    ->relationship('toWarehouse', 'name'),
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
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
