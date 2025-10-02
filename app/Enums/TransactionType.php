<?php

namespace App\Enums;

enum TransactionType: string
{
    case EXPENSE = 'expense';
    case INCOME = 'income';

    public function getLabel(): string
    {
        return match($this) {
            self::EXPENSE => 'Despesa',
            self::INCOME => 'Receita',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::EXPENSE => 'danger',
            self::INCOME => 'success',
        };
    }
}