<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Enums\ExpenseType;
use App\Enums\RecurringType;
use App\Enums\StatusTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Transaction $transaction) {
            // Lógica automática para transações de cartão de crédito
            if ($transaction->payment_method === \App\Enums\PaymentMethod::CREDIT_CARD->value) {
                // Se a data atual for igual ou posterior à data da transação, marca como paga
                if (now()->startOfDay() >= $transaction->date->startOfDay()) {
                    $transaction->status = StatusTransaction::PAID;
                } else {
                    $transaction->status = StatusTransaction::PENDING;
                }
            } else {
                // Para outros métodos de pagamento, mantém o status definido pelo usuário
                // ou define como pendente se não foi definido
                if (empty($transaction->status)) {
                    $transaction->status = StatusTransaction::PENDING;
                }
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item',
        'date',
        'value',
        'type',
        'expense_type',
        'payment_method',
        'status',
        'is_recurring',
        'recurring_type',
        'installments',
        'recurring_end_date',
        'wallet_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'value' => 'decimal:2',
            'type' => TransactionType::class,
            'expense_type' => ExpenseType::class,
            'payment_method' => \App\Enums\PaymentMethod::class,
            'status' => StatusTransaction::class,
            'is_recurring' => 'boolean',
            'recurring_type' => RecurringType::class,
            'recurring_end_date' => 'date',
        ];
    }

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
