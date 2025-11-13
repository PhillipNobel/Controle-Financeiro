<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use App\Enums\PaymentMethod;

class MostExpensiveWidget extends BaseWidget
{
    protected static ?string $heading = 'Maiores Despesas';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        $query = Transaction::query()
            ->where('type', 'expense')
            ->whereBetween('date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->orderBy('value', 'desc')
            ->limit(10);
        
        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('item')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Carteira')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->money('BRL')
                    ->color('danger')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pagamento')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if (is_string($state)) {
                            return PaymentMethod::tryFrom($state)?->getLabel() ?? $state;
                        }
                        return $state->getLabel();
                    })
                    ->color(function ($state): string {
                        $value = is_string($state) ? $state : $state->value;
                        return match ($value) {
                            PaymentMethod::DEBIT->value => 'primary',
                            PaymentMethod::CREDIT_CARD->value => 'warning',
                            PaymentMethod::PIX->value => 'success',
                            PaymentMethod::BANK_SLIP->value => 'info',
                            default => 'gray',
                        };
                    }),
            ])

            ->paginated(false)
            ->defaultSort('value', 'asc');
    }
}