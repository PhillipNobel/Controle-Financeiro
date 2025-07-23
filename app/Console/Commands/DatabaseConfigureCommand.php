<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseConfigurationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DatabaseConfigureCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:configure 
                            {--environment= : Specify environment (local, staging, production)}
                            {--test : Test database connection}
                            {--info : Show database configuration info}
                            {--optimize : Optimize database for current environment}
                            {--migrate : Run migrations after configuration}';

    /**
     * The console command description.
     */
    protected $description = 'Configure database for different environments';

    /**
     * Database configuration service
     */
    private DatabaseConfigurationService $databaseService;

    /**
     * Create a new command instance.
     */
    public function __construct(DatabaseConfigurationService $databaseService)
    {
        parent::__construct();
        $this->databaseService = $databaseService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $environment = $this->option('environment') ?: app()->environment();
        
        $this->info("🔧 Configuring database for environment: {$environment}");
        
        if ($this->option('test')) {
            return $this->testConnection();
        }
        
        if ($this->option('info')) {
            return $this->showInfo();
        }
        
        if ($this->option('optimize')) {
            return $this->optimizeDatabase();
        }
        
        return $this->configureDatabase($environment);
    }

    /**
     * Configure database for specified environment
     */
    private function configureDatabase(string $environment): int
    {
        try {
            $this->info("⚙️  Setting up database configuration for {$environment}...");
            
            // Configure database
            $this->databaseService->configureForEnvironment();
            $this->databaseService->optimizeForEnvironment();
            
            $this->info("✅ Database configured successfully for {$environment}");
            
            // Test connection
            if ($this->databaseService->testConnection()) {
                $this->info("✅ Database connection test passed");
            } else {
                $this->error("❌ Database connection test failed");
                $this->showTroubleshootingTips($environment);
                return 1;
            }
            
            // Show configuration info
            $this->showConfigurationInfo();
            
            // Ask if user wants to run migrations
            if ($this->option('migrate') || $this->confirm('Would you like to run database migrations?')) {
                $this->info("🔄 Running migrations...");
                Artisan::call('migrate', ['--force' => true]);
                $this->info("✅ Migrations completed");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to configure database: " . $e->getMessage());
            $this->showTroubleshootingTips($environment);
            return 1;
        }
    }

    /**
     * Test database connection
     */
    private function testConnection(): int
    {
        $this->info("🔍 Testing database connection...");
        
        try {
            if ($this->databaseService->testConnection()) {
                $this->info("✅ Database connection successful");
                $this->showConfigurationInfo();
                
                // Test basic operations
                $this->testBasicOperations();
                
                return 0;
            } else {
                $this->error("❌ Database connection failed");
                $this->showTroubleshootingTips(app()->environment());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Database connection error: " . $e->getMessage());
            $this->showTroubleshootingTips(app()->environment());
            return 1;
        }
    }

    /**
     * Test basic database operations
     */
    private function testBasicOperations(): void
    {
        try {
            $this->info("🧪 Testing basic database operations...");
            
            // Test table creation
            DB::statement('CREATE TEMPORARY TABLE test_table (id INT PRIMARY KEY, name VARCHAR(255))');
            $this->line("  ✓ Table creation works");
            
            // Test insert
            DB::statement("INSERT INTO test_table (id, name) VALUES (1, 'test')");
            $this->line("  ✓ Insert operation works");
            
            // Test select
            $result = DB::select('SELECT * FROM test_table WHERE id = 1');
            if (!empty($result)) {
                $this->line("  ✓ Select operation works");
            }
            
            // Test update
            DB::statement("UPDATE test_table SET name = 'updated' WHERE id = 1");
            $this->line("  ✓ Update operation works");
            
            // Test delete
            DB::statement("DELETE FROM test_table WHERE id = 1");
            $this->line("  ✓ Delete operation works");
            
            $this->info("✅ All basic operations successful");
            
        } catch (\Exception $e) {
            $this->warn("⚠️  Basic operations test failed: " . $e->getMessage());
        }
    }

    /**
     * Show database configuration info
     */
    private function showInfo(): int
    {
        $this->showConfigurationInfo();
        return 0;
    }

    /**
     * Optimize database for current environment
     */
    private function optimizeDatabase(): int
    {
        try {
            $this->info("⚡ Optimizing database for current environment...");
            
            $this->databaseService->optimizeForEnvironment();
            
            $this->info("✅ Database optimized successfully");
            
            // Run optimization migration
            if ($this->confirm('Would you like to run database optimization migration?')) {
                $this->info("🔄 Running optimization migration...");
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/2025_07_21_000001_ensure_mysql_compatibility_across_environments.php',
                    '--force' => true
                ]);
                $this->info("✅ Database optimization migration completed");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to optimize database: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show current database configuration information
     */
    private function showConfigurationInfo(): void
    {
        $info = $this->databaseService->getConnectionInfo();
        $environment = app()->environment();
        
        $this->newLine();
        $this->info("📊 Database Configuration Information");
        $this->table(
            ['Setting', 'Value'],
            [
                ['Environment', $environment],
                ['Driver', $info['driver']],
                ['Host', $info['host']],
                ['Port', $info['port']],
                ['Database', $info['database']],
                ['Username', $info['username']],
                ['Engine', $info['engine']],
                ['Strict Mode', $info['strict'] ? 'Yes' : 'No'],
            ]
        );
        
        // Show environment-specific recommendations
        $this->showEnvironmentRecommendations($environment);
    }

    /**
     * Show environment-specific recommendations
     */
    private function showEnvironmentRecommendations(string $environment): void
    {
        $this->newLine();
        $this->info("💡 Environment-specific recommendations:");
        
        switch ($environment) {
            case 'local':
                $this->line("  • Use MySQL via MAMP/XAMPP for realistic testing");
                $this->line("  • SQLite available for ultra-fast development");
                $this->line("  • File-based cache for fast development");
                $this->line("  • Query logging enabled for debugging");
                $this->line("  • Strict mode disabled for flexibility");
                break;
                
            case 'staging':
                $this->line("  • MySQL with Docker for production-like testing");
                $this->line("  • Redis for cache and sessions");
                $this->line("  • SSL connections enabled");
                $this->line("  • Detailed logging for debugging");
                $this->line("  • Performance monitoring enabled");
                break;
                
            case 'production':
                $this->line("  • Optimized MySQL with connection pooling");
                $this->line("  • Redis clustering for scalability");
                $this->line("  • SSL with certificate verification");
                $this->line("  • Minimal logging for performance");
                $this->line("  • Automated backups configured");
                break;
        }
    }

    /**
     * Show troubleshooting tips for database issues
     */
    private function showTroubleshootingTips(string $environment): void
    {
        $this->newLine();
        $this->warn("🔧 Troubleshooting Tips:");
        
        switch ($environment) {
            case 'local':
                $this->line("  • Check if MAMP/XAMPP is running");
                $this->line("  • Verify database credentials in .env.local");
                $this->line("  • Try switching to SQLite: DB_CONNECTION=sqlite");
                $this->line("  • Check if database exists: controle_financeiro_local");
                break;
                
            case 'staging':
                $this->line("  • Check if Docker containers are running");
                $this->line("  • Verify Docker network connectivity");
                $this->line("  • Check MySQL container logs: docker logs controle_financeiro_mysql_staging");
                $this->line("  • Verify environment variables in .env.staging");
                break;
                
            case 'production':
                $this->line("  • Check database server status");
                $this->line("  • Verify SSL certificates");
                $this->line("  • Check firewall rules");
                $this->line("  • Verify connection limits");
                break;
        }
        
        $this->newLine();
        $this->line("For more help, check the documentation or run: php artisan db:configure --info");
    }
}