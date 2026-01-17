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
        if (!$masterTransaction->is_recurring || !$masterTransaction->recurring_type || !$masterTransaction->installments) {
            return;
        }

        $startDate = Carbon::parse($masterTransaction->date);
        $totalInstallments = $masterTransaction->installments;
        
        // A primeira parcela já é a transação mestre (criada no banco)
        // Começamos a partir da segunda parcela (i = 1)
        $currentDate = $startDate->copy();
        
        for ($i = 1; $i < $totalInstallments; $i++) {
            $currentDate = $this->getNextDate($currentDate, $masterTransaction->recurring_type);
            $this->createTransactionCopy($masterTransaction, $currentDate->copy(), $i + 1);
        }

        // Atualiza a data final da recorrência na transação mestre para fins de histórico/filtro
        $masterTransaction->updateQuietly([
            'recurring_end_date' => $currentDate
        ]);
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
    private function createTransactionCopy(Transaction $masterTransaction, Carbon $newDate, int $installmentNumber): void
    {
        Transaction::create([
            'item' => $masterTransaction->item . " ({$installmentNumber}/{$masterTransaction->installments})",
            'date' => $newDate,
            'value' => $masterTransaction->value,
            'type' => $masterTransaction->type,
            'expense_type' => $masterTransaction->expense_type,
            'payment_method' => $masterTransaction->payment_method,
            'status' => $masterTransaction->status,
            'is_recurring' => false, // Cópias não são recorrentes
            'recurring_type' => null,
            'installments' => null,
            'recurring_end_date' => null,
            'wallet_id' => $masterTransaction->wallet_id,
        ]);
    }

    /**
     * Processa transações recorrentes que precisam ser criadas
     * (Mantido por compatibilidade, mas a lógica agora foca em parcelas fixas no ato da criação)
     */
    public function processPendingRecurringTransactions(): void
    {
        // Se houver necessidade de processamento em lote no futuro, a lógica de parcelas deve ser adaptada
    }
}
