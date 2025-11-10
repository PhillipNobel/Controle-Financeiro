<?php

namespace App\Enums;

enum ExpenseType: string
{
    case FIXED = 'fixed';
    case VARIABLE = 'variable';

    public function getLabel(): string
    {
        return match ($this) {
            self::FIXED => 'Fixa',
            self::VARIABLE => 'Variável',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FIXED => 'primary',
            self::VARIABLE => 'warning',
        };
    }
}