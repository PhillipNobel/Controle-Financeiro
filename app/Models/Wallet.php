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
     * Get the remaining budget for this wallet.
     */
    protected function remainingBudget(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->budget - $this->getTotalValue(),
        );
    }
}
