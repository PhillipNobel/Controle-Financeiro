<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ExpenseVsRevenueWidget extends ChartWidget
{
    protected static ?string $heading = 'Receitas vs Despesas';

    protected static ?int $sort = 1;

    // Alterando de 'full' para 2/3 da largura
    protected int | string | array $columnSpan = 2;

    // Adicionando altura personalizada em pixels
    protected static ?string $maxHeight = '300px';

    public ?string $filter = 'month';

    protected function getData(): array
    {
        $period = $this->getPeriodDates();

        $transactions = Transaction::whereBetween('date', [$period['start'], $period['end']])
            ->get();

        $revenues = (float) $transactions->where('type', 'income')->sum('value');
        $expenses = (float) $transactions->where('type', 'expense')->sum('value');

        return [
            'datasets' => [
                [
                    'label' => 'Valores (R$)',
                    'data' => [$revenues, $expenses],
                    'backgroundColor' => [
                        '#22c55e',
                        '#ef4444',
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
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        switch ($this->filter) {
            case 'today':
                $start = Carbon::today();
                $end = Carbon::today();
                break;
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;
            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            case 'quarter':
                $start = Carbon::now()->startOfQuarter();
                $end = Carbon::now()->endOfQuarter();
                break;
            case 'year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }
}
