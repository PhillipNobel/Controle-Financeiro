<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Transações';
    
    protected static ?string $modelLabel = 'Transação';
    
    protected static ?string $pluralModelLabel = 'Transações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('item')
                    ->label('Item')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Descrição do item'),
                    
                Forms\Components\DatePicker::make('date')
                    ->label('Data')
                    ->required()
                    ->default(now())
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d'),
                    
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantidade')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->default(1)
                    ->placeholder('0,00'),
                    
                Forms\Components\TextInput::make('value')
                    ->label('Valor')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->prefix('R$')
                    ->placeholder('0,00'),
                    
                Forms\Components\Select::make('wallet_id')
                    ->label('Carteira')
                    ->relationship('wallet', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->maxLength(500),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantidade')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Carteira')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('wallet_id')
                    ->label('Carteira')
                    ->relationship('wallet', 'name')
                    ->searchable()
                    ->preload(),
                    
                Filter::make('date_range')
                    ->label('Período')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Data inicial'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Data final'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}