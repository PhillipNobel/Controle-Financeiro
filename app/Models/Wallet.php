<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'budget',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budget' => 'decimal:2',
    ];

    /**
     * Get the transactions for the wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the total value of all transactions in this wallet.
     */
    public function getTotalValue(): float
    {
        return $this->transactions()->sum('value') ?? 0.0;
    }

    /**
     * Get the total value of open transactions (PENDING and OVERDUE) in this wallet.
     */
    public function getOpenTransactionsValue(): float
    {
        return $this->transactions()
            ->whereIn('status', [\App\Enums\StatusTransaction::PENDING->value, \App\Enums\StatusTransaction::OVERDUE->value])
            ->sum('value') ?? 0.0;
    }

    /**
     * Get the total value of open transactions (PENDING and OVERDUE) for the current month.
     */
    public function getOpenTransactionsValueForCurrentMonth(): float
    {
        $now = now();
        return $this->transactions()
            ->whereIn('status', [\App\Enums\StatusTransaction::PENDING->value, \App\Enums\StatusTransaction::OVERDUE->value])
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month)
            ->sum('value') ?? 0.0;
    }

    /**
     * Get the total value of open transactions (PENDING and OVERDUE) for a specific month.
     */
    public function getOpenTransactionsValueForMonth(int $year, int $month): float
    {
        return $this->transactions()
            ->whereIn('status', [\App\Enums\StatusTransaction::PENDING->value, \App\Enums\StatusTransaction::OVERDUE->value])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('value') ?? 0.0;
    }

    /**
     * Get the total value of expense transactions for a specific month.
     */
    public function getExpenseTransactionsValueForMonth(int $year, int $month): float
    {
        return $this->transactions()
            ->where('type', 'expense')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('value') ?? 0.0;
    }

    /**
     * Get the total value of paid transactions in this wallet.
     */
    public function getPaidTransactionsValue(): float
    {
        return $this->transactions()
            ->where('status', \App\Enums\StatusTransaction::PAID->value)
            ->sum('value') ?? 0.0;
    }

    /**
     * Get the remaining budget for this wallet.
     */
    protected function remainingBudget(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->budget - $this->getOpenTransactionsValueForCurrentMonth(),
        );
    }

    /**
     * Get the remaining budget for a specific month.
     */
    public function getRemainingBudgetForMonth(int $year, int $month): float
    {
        return $this->budget - $this->getExpenseTransactionsValueForMonth($year, $month);
    }
}
