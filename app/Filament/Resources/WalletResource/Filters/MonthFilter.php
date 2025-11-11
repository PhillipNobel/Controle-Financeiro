<?php

namespace App\Filament\Resources\WalletResource\Filters;

use Filament\Tables\Filters\BaseFilter;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class MonthFilter extends BaseFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->form([
            Select::make('month')
                ->label('Mês')
                ->options($this->getMonthOptions())
                ->default(now()->month),
            Select::make('year')
                ->label('Ano')
                ->options($this->getYearOptions())
                ->default(now()->year),
        ]);
    }

    public function apply(Builder $query, array $data = []): Builder
    {
        return $query;
    }

    protected function getMonthOptions(): array
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];
    }

    protected function getYearOptions(): array
    {
        $currentYear = now()->year;
        $years = [];
        
        for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
            $years[$i] = $i;
        }
        
        return $years;
    }
}