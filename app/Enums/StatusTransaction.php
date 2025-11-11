<?php

namespace App\Enums;

enum StatusTransaction: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Em Aberto',
            self::PAID => 'Paga',
            self::OVERDUE => 'Atrasada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::OVERDUE => 'danger',
        };
    }

    public static function options(): array
    {
        return [
            self::PENDING->value => self::PENDING->getLabel(),
            self::PAID->value => self::PAID->getLabel(),
            self::OVERDUE->value => self::OVERDUE->getLabel(),
        ];
    }
}