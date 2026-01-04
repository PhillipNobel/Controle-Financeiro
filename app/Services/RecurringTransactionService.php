<?php

namespace App\Services;

use App\Models\Transaction;
use App\Enums\RecurringType;
use Carbon\Carbon;

class RecurringTransactionService
{
    /**
     * Cria transações recorrentes baseadas em uma transação mestre
     */
    public function createRecurringTransactions(Transaction $masterTransaction): void
    {
        if (!$masterTransaction->is_recurring || !$masterTransaction->recurring_type || !$masterTransaction->recurring_end_date) {
            return;
        }

        $startDate = Carbon::parse($masterTransaction->date);
        $endDate = Carbon::parse($masterTransaction->recurring_end_date);
        
        $currentDate = $startDate->copy();
        
        // Criar todas as cópias desde o início
        while ($currentDate->lte($endDate)) {
            // Não criar cópia para a data original (já existe)
            if (!$currentDate->eq($startDate)) {
                $this->createTransactionCopy($masterTransaction, $currentDate->copy());
            }
            $currentDate = $this->getNextDate($currentDate, $masterTransaction->recurring_type);
        }
    }

    /**
     * Obtém a próxima data baseada no tipo de recorrência
     */
    private function getNextDate(Carbon $currentDate, RecurringType $recurringType): Carbon
    {
        return match ($recurringType) {
            RecurringType::WEEKLY => $currentDate->addWeek(),
            RecurringType::MONTHLY => $currentDate->addMonth(),
            RecurringType::YEARLY => $currentDate->addYear(),
        };
    }

    /**
     * Cria uma cópia da transação com nova data
     */
    private function createTransactionCopy(Transaction $masterTransaction, Carbon $newDate): void
    {
        Transaction::create([
            'item' => $masterTransaction->item,
            'date' => $newDate,
            'value' => $masterTransaction->value,
            'quantity' => $masterTransaction->quantity,
            'type' => $masterTransaction->type,
            'expense_type' => $masterTransaction->expense_type,
            'payment_method' => $masterTransaction->payment_method,
            'status' => $masterTransaction->status,
            'is_recurring' => false, // Cópias não são recorrentes
            'recurring_type' => null,
            'recurring_end_date' => null,
            'wallet_id' => $masterTransaction->wallet_id,
        ]);
    }

    /**
     * Processa transações recorrentes que precisam ser criadas
     */
    public function processPendingRecurringTransactions(): void
    {
        $transactions = Transaction::where('is_recurring', true)
            ->where('recurring_end_date', '>=', now())
            ->get();

        foreach ($transactions as $transaction) {
            $this->createMissingRecurringTransactions($transaction);
        }
    }

    /**
     * Cria transações recorrentes que estão faltando
     */
    private function createMissingRecurringTransactions(Transaction $masterTransaction): void
    {
        $lastTransactionDate = Transaction::where('item', $masterTransaction->item)
            ->where('wallet_id', $masterTransaction->wallet_id)
            ->where('is_recurring', false) // Apenas cópias
            ->orderBy('date', 'desc')
            ->value('date');

        if (!$lastTransactionDate) {
            $this->createRecurringTransactions($masterTransaction);
            return;
        }

        $lastDate = Carbon::parse($lastTransactionDate);
        $endDate = Carbon::parse($masterTransaction->recurring_end_date);
        
        if ($lastDate->gte($endDate)) {
            return;
        }

        $currentDate = $this->getNextDate($lastDate, $masterTransaction->recurring_type);
        
        while ($currentDate->lte($endDate)) {
            $this->createTransactionCopy($masterTransaction, $currentDate->copy());
            $currentDate = $this->getNextDate($currentDate, $masterTransaction->recurring_type);
        }
    }
}