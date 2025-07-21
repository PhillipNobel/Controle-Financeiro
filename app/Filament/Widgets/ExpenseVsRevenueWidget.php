<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ExpenseVsRevenueWidget extends ChartWidget
{
    protected static ?string $heading = 'Receitas vs Despesas';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $filter = 'month';
    
    protected function getData(): array
    {
        $period = $this->getPeriodDates();
        
        $transactions = Transaction::whereBetween('date', [$period['start'], $period['end']])
            ->get();
            
        $revenues = $transactions->where('value', '>', 0)->sum('value');
        $expenses = abs($transactions->where('value', '<', 0)->sum('value'));
        
        return [
            'datasets' => [
                [
                    'label' => 'Valores',
                    'data' => [$revenues, $expenses],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)', // green for revenues
                        'rgb(239, 68, 68)', // red for expenses
                    ],
                    'borderColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                    ],
                ],
            ],
            'labels' => ['Receitas', 'Despesas'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hoje',
            'week' => 'Esta Semana',
            'month' => 'Este Mês',
            'quarter' => 'Este Trimestre',
            'year' => 'Este Ano',
        ];
    }
    
    private function getPeriodDates(): array
    {
        return match ($this->filter) {
            'today' => [
                'start' => Carbon::today(),
                'end' => Carbon::today(),
            ],
            'week' => [
                'start' => Carbon::now()->startOfWeek(),
                'end' => Carbon::now()->endOfWeek(),
            ],
            'month' => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],
            'quarter' => [
                'start' => Carbon::now()->startOfQuarter(),
                'end' => Carbon::now()->endOfQuarter(),
            ],
            'year' => [
                'start' => Carbon::now()->startOfYear(),
                'end' => Carbon::now()->endOfYear(),
            ],
            default => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],
        };
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}