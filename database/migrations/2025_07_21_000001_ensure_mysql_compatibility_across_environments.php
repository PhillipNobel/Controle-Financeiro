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
        // Ensure all tables are compatible across environments
        $this->ensureTableCompatibility();
        $this->addEnvironmentSpecificIndexes();
        $this->configureCharsetAndCollation();
        $this->optimizeForEnvironment();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove environment-specific optimizations if needed
        $this->removeEnvironmentSpecificIndexes();
    }

    /**
     * Ensure table compatibility across all environments
     */
    private function ensureTableCompatibility(): void
    {
        $tables = ['users', 'wallets', 'transactions', 'companies', 'cache', 'jobs', 'personal_access_tokens'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->optimizeTableForEnvironment($table);
            }
        }
    }

    /**
     * Optimize specific table for current environment
     */
    private function optimizeTableForEnvironment(string $table): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $environment = app()->environment();
        
        // Set table engine to InnoDB for all environments
        DB::statement("ALTER TABLE {$table} ENGINE=InnoDB");
        
        // Set charset and collation
        DB::statement("ALTER TABLE {$table} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Environment-specific optimizations
        switch ($environment) {
            case 'local':
                $this->optimizeForLocal($table);
                break;
            case 'staging':
                $this->optimizeForStaging($table);
                break;
            case 'production':
                $this->optimizeForProduction($table);
                break;
        }
    }

    /**
     * Optimize table for local development
     */
    private function optimizeForLocal(string $table): void
    {
        // Local optimizations - prioritize development speed
        // Less strict settings for easier development
        
        // Add basic indexes for common queries
        $this->addBasicIndexes($table);
    }

    /**
     * Optimize table for staging environment
     */
    private function optimizeForStaging(string $table): void
    {
        // Staging optimizations - production-like with monitoring
        
        // Add comprehensive indexes
        $this->addBasicIndexes($table);
        $this->addPerformanceIndexes($table);
    }

    /**
     * Optimize table for production environment
     */
    private function optimizeForProduction(string $table): void
    {
        // Production optimizations - maximum performance
        
        // Add all indexes for optimal performance
        $this->addBasicIndexes($table);
        $this->addPerformanceIndexes($table);
        $this->addAdvancedIndexes($table);
    }

    /**
     * Add basic indexes for common queries
     */
    private function addBasicIndexes(string $table): void
    {
        switch ($table) {
            case 'users':
                $this->addIndexIfNotExists($table, 'email', 'users_email_index');
                $this->addIndexIfNotExists($table, 'created_at', 'users_created_at_index');
                break;
                
            case 'wallets':
                $this->addIndexIfNotExists($table, 'name', 'wallets_name_index');
                $this->addIndexIfNotExists($table, 'created_at', 'wallets_created_at_index');
                break;
                
            case 'transactions':
                $this->addIndexIfNotExists($table, 'wallet_id', 'transactions_wallet_id_index');
                $this->addIndexIfNotExists($table, 'date', 'transactions_date_index');
                $this->addIndexIfNotExists($table, 'created_at', 'transactions_created_at_index');
                break;
                
            case 'companies':
                $this->addIndexIfNotExists($table, 'cnpj', 'companies_cnpj_index');
                $this->addIndexIfNotExists($table, 'name', 'companies_name_index');
                break;
        }
    }

    /**
     * Add performance indexes for better query performance
     */
    private function addPerformanceIndexes(string $table): void
    {
        switch ($table) {
            case 'users':
                $this->addCompositeIndexIfNotExists($table, ['role', 'created_at'], 'users_role_created_index');
                break;
                
            case 'transactions':
                $this->addCompositeIndexIfNotExists($table, ['wallet_id', 'date'], 'transactions_wallet_date_index');
                $this->addCompositeIndexIfNotExists($table, ['date', 'value'], 'transactions_date_value_index');
                break;
        }
    }

    /**
     * Add advanced indexes for production optimization
     */
    private function addAdvancedIndexes(string $table): void
    {
        switch ($table) {
            case 'transactions':
                $this->addCompositeIndexIfNotExists($table, ['wallet_id', 'date', 'value'], 'transactions_wallet_date_value_index');
                $this->addIndexIfNotExists($table, 'value', 'transactions_value_index');
                break;
                
            case 'companies':
                $this->addIndexIfNotExists($table, 'email', 'companies_email_index');
                break;
        }
    }

    /**
     * Add environment-specific indexes
     */
    private function addEnvironmentSpecificIndexes(): void
    {
        $environment = app()->environment();
        
        if ($environment === 'production') {
            // Add production-specific indexes for maximum performance
            $this->addProductionSpecificIndexes();
        }
    }

    /**
     * Add production-specific indexes
     */
    private function addProductionSpecificIndexes(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Add covering indexes for common queries
        if (Schema::hasTable('transactions')) {
            $this->addCompositeIndexIfNotExists('transactions', 
                ['wallet_id', 'date', 'item', 'value'], 
                'transactions_covering_index');
        }
    }

    /**
     * Remove environment-specific indexes
     */
    private function removeEnvironmentSpecificIndexes(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Remove production-specific indexes if they exist
        if (Schema::hasTable('transactions')) {
            $this->dropIndexIfExists('transactions', 'transactions_covering_index');
        }
    }

    /**
     * Configure charset and collation for all tables
     */
    private function configureCharsetAndCollation(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Set database charset and collation
        $database = config('database.connections.' . config('database.default') . '.database');
        DB::statement("ALTER DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Optimize database settings for current environment
     */
    private function optimizeForEnvironment(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $environment = app()->environment();
        
        switch ($environment) {
            case 'local':
                // Development-friendly settings
                DB::statement("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
                break;
                
            case 'staging':
            case 'production':
                // Production settings
                DB::statement("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
                break;
        }
        
        // Set charset for session
        DB::statement("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Add index if it doesn't exist
     */
    private function addIndexIfNotExists(string $table, string $column, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            DB::statement("CREATE INDEX {$indexName} ON {$table} ({$column})");
        }
    }

    /**
     * Add composite index if it doesn't exist
     */
    private function addCompositeIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            $columnList = implode(', ', $columns);
            DB::statement("CREATE INDEX {$indexName} ON {$table} ({$columnList})");
        }
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement("DROP INDEX {$indexName} ON {$table}");
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
        
        return false;
    }
};