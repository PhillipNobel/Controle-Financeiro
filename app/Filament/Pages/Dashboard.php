<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExpenseVsRevenueWidget;
use App\Filament\Widgets\FinancialSummaryWidget;
use App\Filament\Widgets\MostExpensiveWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament-panels::pages.dashboard';
    
    public function getWidgets(): array
    {
        return [
            FinancialSummaryWidget::class,
            ExpenseVsRevenueWidget::class,
            MostExpensiveWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return 2;
    }
}