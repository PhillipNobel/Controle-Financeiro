<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinancialSummaryWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    
    protected static ?int $sort = 0;
    
    protected function getStats(): array
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();
        
        // Current month data
        $currentTransactions = Transaction::whereBetween('date', [
            $currentMonth->startOfMonth()->copy(),
            $currentMonth->endOfMonth()->copy()
        ])->get();
        
        // Previous month data for comparison
        $previousTransactions = Transaction::whereBetween('date', [
            $previousMonth->startOfMonth()->copy(),
            $previousMonth->endOfMonth()->copy()
        ])->get();
        
        $currentRevenues = $currentTransactions->where('value', '>', 0)->sum('value');
        $currentExpenses = abs($currentTransactions->where('value', '<', 0)->sum('value'));
        $currentBalance = $currentRevenues - $currentExpenses;
        
        $previousRevenues = $previousTransactions->where('value', '>', 0)->sum('value');
        $previousExpenses = abs($previousTransactions->where('value', '<', 0)->sum('value'));
        $previousBalance = $previousRevenues - $previousExpenses;
        
        // Calculate percentage changes
        $revenueChange = $this->calculatePercentageChange($previousRevenues, $currentRevenues);
        $expenseChange = $this->calculatePercentageChange($previousExpenses, $currentExpenses);
        $balanceChange = $this->calculatePercentageChange($previousBalance, $currentBalance);
        
        $totalWallets = Wallet::count();
        $totalTransactions = Transaction::count();
        
        $stats = [];
        
        if ($totalTransactions > 0) {
            $stats = [
                Stat::make('Receitas do Mês', 'R$ ' . number_format($currentRevenues, 2, ',', '.'))
                    ->description($this->getChangeDescription($revenueChange, 'receitas'))
                    ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($revenueChange >= 0 ? 'success' : 'danger'),
                    
                Stat::make('Despesas do Mês', 'R$ ' . number_format($currentExpenses, 2, ',', '.'))
                    ->description($this->getChangeDescription($expenseChange, 'despesas'))
                    ->descriptionIcon($expenseChange <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                    ->color($expenseChange <= 0 ? 'success' : 'warning'),
                    
                Stat::make('Saldo do Mês', 'R$ ' . number_format($currentBalance, 2, ',', '.'))
                    ->description($this->getChangeDescription($balanceChange, 'saldo'))
                    ->descriptionIcon($balanceChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($currentBalance >= 0 ? 'success' : 'danger'),
                    
                Stat::make('Total de Carteiras', $totalWallets)
                    ->description('Carteiras cadastradas')
                    ->descriptionIcon('heroicon-m-wallet')
                    ->color('info'),
            ];
        } else {
            $stats = [
                Stat::make('Bem-vindo!', 'Nenhuma transação')
                    ->description('Comece cadastrando suas primeiras transações')
                    ->descriptionIcon('heroicon-m-plus-circle')
                    ->color('gray'),
                    
                Stat::make('Carteiras', $totalWallets)
                    ->description($totalWallets > 0 ? 'Carteiras cadastradas' : 'Crie sua primeira carteira')
                    ->descriptionIcon('heroicon-m-wallet')
                    ->color($totalWallets > 0 ? 'info' : 'gray'),
            ];
        }
        
        return $stats;
    }
    
    private function calculatePercentageChange(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return (($current - $previous) / abs($previous)) * 100;
    }
    
    private function getChangeDescription(float $change, string $type): string
    {
        $absChange = abs($change);
        $direction = $change >= 0 ? 'aumento' : 'redução';
        
        if ($absChange < 1) {
            return "Sem alteração significativa em {$type}";
        }
        
        return sprintf('%s de %.1f%% em %s', ucfirst($direction), $absChange, $type);
    }
}