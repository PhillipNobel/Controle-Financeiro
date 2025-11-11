<?php

namespace App\Filament\Resources\WalletResource\RelationManagers;

use App\Enums\TransactionType;
use App\Enums\ExpenseType;
use App\Enums\PaymentMethod;
use App\Enums\StatusTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Leandrocfe\FilamentPtbrFormFields\Money;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Form $form): Form
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
                Money::make('value')
                    ->label('Valor')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options(TransactionType::class)
                    ->required(),
                Forms\Components\Select::make('expense_type')
                    ->label('Tipo de Despesa')
                    ->options(ExpenseType::class)
                    ->required(fn (\Closure $get) => $get('type') === TransactionType::EXPENSE->value),
                Forms\Components\Select::make('payment_method')
                    ->label('Método de Pagamento')
                    ->options(PaymentMethod::class)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(StatusTransaction::class)
                    ->required(),
                Forms\Components\Toggle::make('is_recurring')
                    ->label('Recorrente?')
                    ->reactive(),
                Forms\Components\Select::make('recurring_type')
                    ->label('Tipo de Recorrência')
                    ->options(\App\Enums\RecurringType::class)
                    ->visible(fn (\Closure $get) => $get('is_recurring')),
                Forms\Components\DatePicker::make('recurring_end_date')
                    ->label('Data Final da Recorrência')
                    ->visible(fn (\Closure $get) => $get('is_recurring')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if (is_string($state)) {
                            return TransactionType::tryFrom($state)?->getLabel() ?? $state;
                        }
                        return $state->getLabel();
                    })
                    ->color(function ($state): string {
                        $value = is_string($state) ? $state : $state->value;
                        return match ($value) {
                            TransactionType::INCOME->value => 'success',
                            TransactionType::EXPENSE->value => 'danger',
                            default => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('expense_type')
                    ->label('Tipo de Despesa')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if (is_string($state)) {
                            return ExpenseType::tryFrom($state)?->getLabel() ?? $state;
                        }
                        return $state->getLabel();
                    })
                    ->visible(fn ($record) => $record && $record->type === TransactionType::EXPENSE->value),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pagamento')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if (is_string($state)) {
                            return PaymentMethod::tryFrom($state)?->getLabel() ?? $state;
                        }
                        return $state->getLabel();
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if (is_string($state)) {
                            return StatusTransaction::tryFrom($state)?->getLabel() ?? $state;
                        }
                        return $state->getLabel();
                    })
                    ->color(function ($state): string {
                        $value = is_string($state) ? $state : $state->value;
                        return match ($value) {
                            StatusTransaction::PAID->value => 'success',
                            StatusTransaction::PENDING->value => 'warning',
                            StatusTransaction::OVERDUE->value => 'danger',
                            default => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(TransactionType::class),
                Tables\Filters\SelectFilter::make('expense_type')
                    ->label('Tipo de Despesa')
                    ->options(ExpenseType::class),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pagamento')
                    ->options(PaymentMethod::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusTransaction::class),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('De'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Até'),
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
            ->headerActions([])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}