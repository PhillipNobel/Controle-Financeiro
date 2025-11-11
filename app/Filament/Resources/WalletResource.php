<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Filament\Resources\WalletResource\RelationManagers;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Leandrocfe\FilamentPtbrFormFields\Money;
use App\Filament\Resources\WalletResource\Filters\MonthFilter;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    
    protected static ?string $navigationLabel = 'Carteiras';
    
    protected static ?string $modelLabel = 'Carteira';
    
    protected static ?string $pluralModelLabel = 'Carteiras';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('description')
                    ->label('Descrição')
                    ->maxLength(255),
                Money::make('budget')
                    ->label('Orçamento')
                    ->default('0,00')
                    ->helperText('Defina um orçamento para esta carteira (opcional)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable(),
                Tables\Columns\TextColumn::make('budget')
                    ->label('Orçamento')
                    ->money('BRL')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Orçamento')
                    ]),
                Tables\Columns\TextColumn::make('remaining_budget')
                    ->label('Orçamento Restante')
                    ->money('BRL')
                    ->sortable()
                    ->color(fn (string $state): string => match (true) {
                        $state < 0 => 'danger',
                        $state > 0 => 'success',
                        default => 'gray',
                    })
                    ->getStateUsing(function (Wallet $record, $livewire) {
                        $month = $livewire->tableFilters['month_filter']['month'] ?? now()->month;
                        $year = $livewire->tableFilters['month_filter']['year'] ?? now()->year;
                        
                        return $record->getRemainingBudgetForMonth((int) $year, (int) $month);
                    })
                    ->summarize([
                        Summarizer::make()
                            ->label('Total Orçamento Restante')
                            ->using(function (\Illuminate\Database\Query\Builder $query, $livewire) {
                                $month = $livewire->tableFilters['month_filter']['month'] ?? now()->month;
                                $year = $livewire->tableFilters['month_filter']['year'] ?? now()->year;
                                
                                // Calculate total remaining budget
                                $totalRemaining = \App\Models\Wallet::withSum(['transactions' => function ($q) use ($month, $year) {
                                    $q->where('type', 'expense')
                                       ->whereMonth('date', $month)
                                       ->whereYear('date', $year);
                                }], 'value')
                                ->get()
                                ->sum(function ($wallet) {
                                    return $wallet->budget - ($wallet->transactions_sum_value ?? 0);
                                });
                                
                                return $totalRemaining;
                            })
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                MonthFilter::make('month_filter')
                    ->label('Filtrar por Mês'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->contentFooter(
                view('filament.tables.footer-total', [
                    'total' => \App\Models\Wallet::sum('budget'),
                ])
            );
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
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}