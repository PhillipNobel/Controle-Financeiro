<?php

namespace App\Filament\Resources;

use App\Enums\TransactionType;
use App\Enums\ExpenseType;
use App\Enums\RecurringType;
use App\Enums\StatusTransaction;
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
use Leandrocfe\FilamentPtbrFormFields\Money;

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
                    
                Money::make('value')
                    ->label('Valor')
                    ->required(),
                    
                Forms\Components\Select::make('payment_method')
                    ->label('Método de Pagamento')
                    ->options([
                        \App\Enums\PaymentMethod::DEBIT->value => \App\Enums\PaymentMethod::DEBIT->getLabel(),
                        \App\Enums\PaymentMethod::CREDIT_CARD->value => \App\Enums\PaymentMethod::CREDIT_CARD->getLabel(),
                        \App\Enums\PaymentMethod::PIX->value => \App\Enums\PaymentMethod::PIX->getLabel(),
                        \App\Enums\PaymentMethod::BANK_SLIP->value => \App\Enums\PaymentMethod::BANK_SLIP->getLabel(),
                    ])
                    ->default(\App\Enums\PaymentMethod::DEBIT->value)
                    ->nullable(),
                    
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(StatusTransaction::options())
                    ->default(StatusTransaction::PENDING->value)
                    ->required(),
                    
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
                    ->label('Data da Transação')
                    ->date('j M, Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('recurring_end_date')
                    ->label('Final da Recorrência')
                    ->date('j M, Y')
                    ->visible(fn (?Transaction $record): bool => $record?->is_recurring === true),
                    
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
                    
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pagamento')
                    ->badge()
                    ->formatStateUsing(fn (?\App\Enums\PaymentMethod $state): ?string => $state?->getLabel())
                    ->color(fn (?\App\Enums\PaymentMethod $state): ?string => $state?->getColor()),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (\App\Enums\StatusTransaction $state): string => $state->getLabel())
                    ->color(fn (\App\Enums\StatusTransaction $state): string => $state->getColor()),
                    
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
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->money('BRL')
                            ->using(fn ($query) => $query->sum('value')),
                    ]),
                    
                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Carteira')
                    ->sortable()
                    ->searchable(),
                    
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
                    
                SelectFilter::make('payment_method')
                    ->label('Método de Pagamento')
                    ->options([
                        \App\Enums\PaymentMethod::DEBIT->value => \App\Enums\PaymentMethod::DEBIT->getLabel(),
                        \App\Enums\PaymentMethod::CREDIT_CARD->value => \App\Enums\PaymentMethod::CREDIT_CARD->getLabel(),
                        \App\Enums\PaymentMethod::PIX->value => \App\Enums\PaymentMethod::PIX->getLabel(),
                        \App\Enums\PaymentMethod::BANK_SLIP->value => \App\Enums\PaymentMethod::BANK_SLIP->getLabel(),
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
                    
                Filter::make('month')
                    ->label('Mês')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Mês')
                            ->options([
                                '01' => 'Janeiro',
                                '02' => 'Fevereiro', 
                                '03' => 'Março',
                                '04' => 'Abril',
                                '05' => 'Maio',
                                '06' => 'Junho',
                                '07' => 'Julho',
                                '08' => 'Agosto',
                                '09' => 'Setembro',
                                '10' => 'Outubro',
                                '11' => 'Novembro',
                                '12' => 'Dezembro',
                            ])
                            ->default(now()->format('m')),
                        Forms\Components\Select::make('year')
                            ->label('Ano')
                            ->options(fn () => array_combine(
                                $years = range(now()->year - 5, now()->year + 5),
                                $years
                            ))
                            ->default(now()->format('Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['month'] && $data['year'],
                                fn (Builder $query): Builder => $query->whereMonth('date', $data['month'])
                                    ->whereYear('date', $data['year'])
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