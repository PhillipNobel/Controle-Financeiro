<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Enums\ExpenseType;
use App\Enums\RecurringType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item',
        'date',
        'quantity',
        'value',
        'type',
        'expense_type',
        'payment_method',
        'is_recurring',
        'recurring_type',
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
            'quantity' => 'decimal:2',
            'value' => 'decimal:2',
            'type' => TransactionType::class,
            'expense_type' => ExpenseType::class,
            'payment_method' => \App\Enums\PaymentMethod::class,
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
