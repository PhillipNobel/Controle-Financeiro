<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Optimize existing tables for MySQL across all environments
        $this->optimizeUsersTable();
        $this->optimizeWalletsTable();
        $this->optimizeTransactionsTable();
        $this->optimizeCompaniesTable();
        $this->addDatabaseIndexes();
        $this->configureMySQLSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove optimizations if needed
        $this->removeDatabaseIndexes();
    }

    /**
     * Optimize users table for MySQL
     */
    private function optimizeUsersTable(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add indexes for better performance
            if (!$this->indexExists('users', 'users_email_index')) {
                $table->index('email');
            }
            if (!$this->indexExists('users', 'users_role_index')) {
                $table->index('role');
            }
            if (!$this->indexExists('users', 'users_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Set table engine and charset for MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users ENGINE=InnoDB');
            DB::statement('ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }

    /**
     * Optimize wallets table for MySQL
     */
    private function optimizeWalletsTable(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Add indexes for better performance
            if (!$this->indexExists('wallets', 'wallets_name_index')) {
                $table->index('name');
            }
            if (!$this->indexExists('wallets', 'wallets_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Set table engine and charset for MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallets ENGINE=InnoDB');
            DB::statement('ALTER TABLE wallets CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }

    /**
     * Optimize transactions table for MySQL
     */
    private function optimizeTransactionsTable(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add indexes for better performance
            if (!$this->indexExists('transactions', 'transactions_date_index')) {
                $table->index('date');
            }
            if (!$this->indexExists('transactions', 'transactions_wallet_id_date_index')) {
                $table->index(['wallet_id', 'date']);
            }
            if (!$this->indexExists('transactions', 'transactions_value_index')) {
                $table->index('value');
            }
            if (!$this->indexExists('transactions', 'transactions_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Set table engine and charset for MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transactions ENGINE=InnoDB');
            DB::statement('ALTER TABLE transactions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }

    /**
     * Optimize companies table for MySQL
     */
    private function optimizeCompaniesTable(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Add indexes for better performance
            if (!$this->indexExists('companies', 'companies_name_index')) {
                $table->index('name');
            }
            if (!$this->indexExists('companies', 'companies_cnpj_index')) {
                $table->index('cnpj');
            }
            if (!$this->indexExists('companies', 'companies_email_index')) {
                $table->index('email');
            }
            if (!$this->indexExists('companies', 'companies_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Set table engine and charset for MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE companies ENGINE=InnoDB');
            DB::statement('ALTER TABLE companies CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }

    /**
     * Add database-wide indexes for performance
     */
    private function addDatabaseIndexes(): void
    {
        // Add composite indexes for common queries
        if (DB::getDriverName() === 'mysql') {
            // Transactions by wallet and date range
            if (!$this->indexExists('transactions', 'transactions_wallet_date_value_index')) {
                DB::statement('CREATE INDEX transactions_wallet_date_value_index ON transactions (wallet_id, date, value)');
            }
            
            // Users by role and creation date
            if (!$this->indexExists('users', 'users_role_created_index')) {
                DB::statement('CREATE INDEX users_role_created_index ON users (role, created_at)');
            }
        }
    }

    /**
     * Remove database indexes
     */
    private function removeDatabaseIndexes(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Remove composite indexes
            if ($this->indexExists('transactions', 'transactions_wallet_date_value_index')) {
                DB::statement('DROP INDEX transactions_wallet_date_value_index ON transactions');
            }
            
            if ($this->indexExists('users', 'users_role_created_index')) {
                DB::statement('DROP INDEX users_role_created_index ON users');
            }
        }
    }

    /**
     * Configure MySQL-specific settings
     */
    private function configureMySQLSettings(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Set MySQL-specific configurations for better performance
            $environment = app()->environment();
            
            switch ($environment) {
                case 'local':
                    // Development optimizations
                    DB::statement("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
                    break;
                    
                case 'staging':
                case 'production':
                    // Production optimizations
                    DB::statement("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
                    break;
            }
            
            // Set charset and collation for the session
            DB::statement("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'mysql') {
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            return !empty($result);
        }
        
        // For other databases, assume index doesn't exist
        return false;
    }
};