<?php

namespace App\Enums;

enum RecurringType: string
{
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    
    public function getLabel(): string
    {
        return match ($this) {
            self::WEEKLY => 'Semanal',
            self::MONTHLY => 'Mensal',
            self::YEARLY => 'Anual',
        };
    }
    
    public function getColor(): string
    {
        return match ($this) {
            self::WEEKLY => 'info',
            self::MONTHLY => 'primary',
            self::YEARLY => 'warning',
        };
    }
}