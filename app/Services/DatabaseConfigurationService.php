<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;

class DatabaseConfigurationService
{
    /**
     * Configure database connection based on environment
     */
    public function configureForEnvironment(): void
    {
        $environment = app()->environment();
        
        switch ($environment) {
            case 'local':
                $this->configureLocalDatabase();
                break;
            case 'staging':
                $this->configureStagingDatabase();
                break;
            case 'production':
                $this->configureProductionDatabase();
                break;
            default:
                $this->configureDefaultDatabase();
                break;
        }
    }

    /**
     * Configure database for local development
     */
    private function configureLocalDatabase(): void
    {
        // Use MySQL via MAMP for local development
        Config::set('database.default', 'mysql_local');
        
        // Set local-specific optimizations
        Config::set('database.connections.mysql_local.strict', false);
        Config::set('database.connections.mysql_local.engine', 'InnoDB');
        
        // Development-friendly settings
        Config::set('database.connections.mysql_local.options.' . PDO::ATTR_TIMEOUT, 30);
        Config::set('database.connections.mysql_local.options.' . PDO::ATTR_EMULATE_PREPARES, true);
        Config::set('database.connections.mysql_local.options.' . PDO::ATTR_PERSISTENT, false);
        
        // Enable query logging for development
        if (config('app.debug')) {
            DB::enableQueryLog();
        }
    }

    /**
     * Configure database for staging environment
     */
    private function configureStagingDatabase(): void
    {
        // Use MySQL with Docker for staging
        Config::set('database.default', 'mysql_staging');
        
        // Set staging-specific optimizations
        Config::set('database.connections.mysql_staging.strict', true);
        Config::set('database.connections.mysql_staging.engine', 'InnoDB');
        
        // Configure connection settings for staging
        Config::set('database.connections.mysql_staging.options.' . PDO::ATTR_PERSISTENT, false);
        Config::set('database.connections.mysql_staging.options.' . PDO::ATTR_TIMEOUT, 60);
        Config::set('database.connections.mysql_staging.options.' . PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        
        // Enable SSL for staging
        if (env('DB_SSL_MODE') === 'REQUIRED') {
            Config::set('database.connections.mysql_staging.options.' . PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT, false);
        }
    }

    /**
     * Configure database for production environment
     */
    private function configureProductionDatabase(): void
    {
        // Use optimized MySQL for production
        Config::set('database.default', 'mysql_production');
        
        // Set production-specific optimizations
        Config::set('database.connections.mysql_production.strict', true);
        Config::set('database.connections.mysql_production.engine', 'InnoDB');
        
        // Enable connection pooling for production
        Config::set('database.connections.mysql_production.options.' . PDO::ATTR_PERSISTENT, true);
        Config::set('database.connections.mysql_production.options.' . PDO::ATTR_TIMEOUT, 60);
        Config::set('database.connections.mysql_production.options.' . PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        
        // Enable SSL with certificate verification for production
        if (env('DB_SSL_MODE') === 'REQUIRED') {
            Config::set('database.connections.mysql_production.options.' . PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT, true);
        }
        
        // Set production SQL mode
        Config::set('database.connections.mysql_production.options.' . PDO::MYSQL_ATTR_INIT_COMMAND, 
            "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }

    /**
     * Configure default database (fallback to production-like settings)
     */
    private function configureDefaultDatabase(): void
    {
        $this->configureProductionDatabase();
    }

    /**
     * Test database connection
     */
    public function testConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get database connection info
     */
    public function getConnectionInfo(): array
    {
        $config = config('database.connections.' . config('database.default'));
        
        return [
            'driver' => $config['driver'] ?? 'unknown',
            'host' => $config['host'] ?? 'unknown',
            'port' => $config['port'] ?? 'unknown',
            'database' => $config['database'] ?? 'unknown',
            'username' => $config['username'] ?? 'unknown',
            'engine' => $config['engine'] ?? 'unknown',
            'strict' => $config['strict'] ?? false,
        ];
    }

    /**
     * Optimize database configuration for current environment
     */
    public function optimizeForEnvironment(): void
    {
        $environment = app()->environment();
        
        switch ($environment) {
            case 'local':
                $this->optimizeForLocal();
                break;
            case 'staging':
                $this->optimizeForStaging();
                break;
            case 'production':
                $this->optimizeForProduction();
                break;
        }
    }

    /**
     * Optimize for local development
     */
    private function optimizeForLocal(): void
    {
        // Fast development settings
        Config::set('database.connections.mysql.options.' . PDO::ATTR_TIMEOUT, 30);
        Config::set('database.connections.mysql.options.' . PDO::ATTR_EMULATE_PREPARES, true);
    }

    /**
     * Optimize for staging environment
     */
    private function optimizeForStaging(): void
    {
        // Production-like settings with debugging capabilities
        Config::set('database.connections.mysql.options.' . PDO::ATTR_TIMEOUT, 60);
        Config::set('database.connections.mysql.options.' . PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }

    /**
     * Optimize for production environment
     */
    private function optimizeForProduction(): void
    {
        // Maximum performance settings
        Config::set('database.connections.mysql.options.' . PDO::ATTR_TIMEOUT, 60);
        Config::set('database.connections.mysql.options.' . PDO::ATTR_PERSISTENT, true);
        Config::set('database.connections.mysql.options.' . PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
}