<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case DEBIT = 'debit';
    case CREDIT_CARD = 'credit_card';
    case PIX = 'pix';
    case BANK_SLIP = 'bank_slip';

    public function getLabel(): string
    {
        return match ($this) {
            self::DEBIT => 'Débito',
            self::CREDIT_CARD => 'Cartão de Crédito',
            self::PIX => 'PIX',
            self::BANK_SLIP => 'Boleto Bancário',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DEBIT => 'primary',
            self::CREDIT_CARD => 'warning',
            self::PIX => 'success',
            self::BANK_SLIP => 'info',
        };
    }
}