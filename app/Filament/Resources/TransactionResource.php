<?php

namespace App\Filament\Resources;

use App\Enums\TransactionType;
use App\Enums\ExpenseType;
use App\Enums\RecurringType;
use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

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
                    ->maxLength(255),
                    
                Forms\Components\DatePicker::make('date')
                    ->label('Data')
                    ->required()
                    ->default(now()),
                    
                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options([
                        TransactionType::EXPENSE->value => 'Despesa',
                        TransactionType::INCOME->value => 'Receita',
                    ])
                    ->default(TransactionType::EXPENSE->value)
                    ->required()
                    ->live(),
                    
                Forms\Components\Select::make('expense_type')
                    ->label('Tipo de Despesa')
                    ->options([
                        ExpenseType::FIXED->value => 'Fixa',
                        ExpenseType::VARIABLE->value => 'Variável',
                    ])
                    ->visible(fn (Forms\Get $get) => $get('type') === TransactionType::EXPENSE->value)
                    ->required(fn (Forms\Get $get) => $get('type') === TransactionType::EXPENSE->value),
                    
                Forms\Components\TextInput::make('value')
                    ->label('Valor')
                    ->numeric()
                    ->required()
                    ->prefix('R$'),
                    
                Forms\Components\Toggle::make('is_recurring')
                    ->label('Transação Recorrente?')
                    ->live()
                    ->default(false),
                    
                Forms\Components\Select::make('recurring_type')
                    ->label('Tipo de Recorrência')
                    ->options([
                        RecurringType::WEEKLY->value => 'Semanal',
                        RecurringType::MONTHLY->value => 'Mensal',
                        RecurringType::YEARLY->value => 'Anual',
                    ])
                    ->visible(fn (Forms\Get $get) => $get('is_recurring') === true)
                    ->required(fn (Forms\Get $get) => $get('is_recurring') === true),
                    
                Forms\Components\DatePicker::make('recurring_end_date')
                    ->label('Data Final da Recorrência')
                    ->visible(fn (Forms\Get $get) => $get('is_recurring') === true)
                    ->required(fn (Forms\Get $get) => $get('is_recurring') === true),
                    
                Forms\Components\Select::make('wallet_id')
                    ->label('Carteira')
                    ->relationship('wallet', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
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
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (TransactionType $state): string => $state->getLabel())
                    ->color(fn (TransactionType $state): string => $state->getColor()),
                    
                Tables\Columns\TextColumn::make('expense_type')
                    ->label('Tipo de Despesa')
                    ->badge()
                    ->formatStateUsing(fn (?ExpenseType $state): ?string => $state?->getLabel())
                    ->color(fn (?ExpenseType $state): ?string => $state?->getColor())
                    ->visible(fn (?Transaction $record): bool => $record?->type === TransactionType::EXPENSE),
                    
                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Recorrente')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('recurring_type')
                    ->label('Recorrência')
                    ->badge()
                    ->formatStateUsing(fn (?RecurringType $state): ?string => $state?->getLabel())
                    ->color(fn (?RecurringType $state): ?string => $state?->getColor())
                    ->visible(fn (?Transaction $record): bool => $record?->is_recurring === true),
                    
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->money('BRL')
                    ->color(fn (Transaction $record): string => match ($record->type) {
                        TransactionType::INCOME => 'success',
                        TransactionType::EXPENSE => 'danger',
                    })
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
                    
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        TransactionType::EXPENSE->value => 'Despesa',
                        TransactionType::INCOME->value => 'Receita',
                    ]),
                    
                SelectFilter::make('expense_type')
                    ->label('Tipo de Despesa')
                    ->options([
                        ExpenseType::FIXED->value => 'Fixa',
                        ExpenseType::VARIABLE->value => 'Variável',
                    ])
                    ->visible(fn (Builder $query): bool => true),
                    
                SelectFilter::make('is_recurring')
                    ->label('Transação Recorrente')
                    ->options([
                        '1' => 'Sim',
                        '0' => 'Não',
                    ]),
                    
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