<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adicionar índices críticos para melhorar o desempenho das consultas
        $this->addCriticalIndexesToTransactions();
        $this->addCriticalIndexesToWallets();
        $this->addCriticalIndexesToUsers();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover os índices adicionados
        $this->removeCriticalIndexesFromTransactions();
        $this->removeCriticalIndexesFromWallets();
        $this->removeCriticalIndexesFromUsers();
    }

    /**
     * Adicionar índices críticos na tabela transactions
     */
    private function addCriticalIndexesToTransactions(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Índice composto para consultas por tipo e data (muito comum)
            if (!$this->indexExists('transactions', 'transactions_type_date_index')) {
                $table->index(['type', 'date']);
            }

            // Índice composto para consultas por status e data
            if (!$this->indexExists('transactions', 'transactions_status_date_index')) {
                $table->index(['status', 'date']);
            }

            // Índice composto para consultas por carteira, tipo e data
            if (!$this->indexExists('transactions', 'transactions_wallet_id_type_date_index')) {
                $table->index(['wallet_id', 'type', 'date']);
            }

            // Índice para método de pagamento (filtros comuns)
            if (!$this->indexExists('transactions', 'transactions_payment_method_index')) {
                $table->index('payment_method');
            }

            // Índice para tipo de despesa
            if (!$this->indexExists('transactions', 'transactions_expense_type_index')) {
                $table->index('expense_type');
            }

            // Índice composto para transações recorrentes
            if (!$this->indexExists('transactions', 'transactions_is_recurring_recurring_type_index')) {
                $table->index(['is_recurring', 'recurring_type']);
            }
        });
    }

    /**
     * Adicionar índices críticos na tabela wallets
     */
    private function addCriticalIndexesToWallets(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Índice para orçamento (consultas de ordenação)
            if (!$this->indexExists('wallets', 'wallets_budget_index')) {
                $table->index('budget');
            }

            // Índice composto para nome e orçamento
            if (!$this->indexExists('wallets', 'wallets_name_budget_index')) {
                $table->index(['name', 'budget']);
            }
        });
    }

    /**
     * Adicionar índices críticos na tabela users
     */
    private function addCriticalIndexesToUsers(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Índice composto para role e created_at (relatórios administrativos)
            if (!$this->indexExists('users', 'users_role_created_at_index')) {
                $table->index(['role', 'created_at']);
            }

            // Índice para nome (buscas)
            if (!$this->indexExists('users', 'users_name_index')) {
                $table->index('name');
            }
        });
    }

    /**
     * Remover índices da tabela transactions
     */
    private function removeCriticalIndexesFromTransactions(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndexIfExists('transactions_type_date_index');
            $table->dropIndexIfExists('transactions_status_date_index');
            $table->dropIndexIfExists('transactions_wallet_id_type_date_index');
            $table->dropIndexIfExists('transactions_payment_method_index');
            $table->dropIndexIfExists('transactions_expense_type_index');
            $table->dropIndexIfExists('transactions_is_recurring_recurring_type_index');
        });
    }

    /**
     * Remover índices da tabela wallets
     */
    private function removeCriticalIndexesFromWallets(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropIndexIfExists('wallets_budget_index');
            $table->dropIndexIfExists('wallets_name_budget_index');
        });
    }

    /**
     * Remover índices da tabela users
     */
    private function removeCriticalIndexesFromUsers(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndexIfExists('users_role_created_at_index');
            $table->dropIndexIfExists('users_name_index');
        });
    }

    /**
     * Verificar se um índice já existe
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        return $connection->selectOne(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        )->count > 0;
    }
};