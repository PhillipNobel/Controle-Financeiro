<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MostExpensiveWidget extends BaseWidget
{
    protected static ?string $heading = 'Maiores Despesas';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where('type', 'expense')
                    ->orderBy('value', 'desc')
                    ->limit(10)
            )
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
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Transaction $record): string => TransactionResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->paginated(false)
            ->defaultSort('value', 'asc');
    }
}