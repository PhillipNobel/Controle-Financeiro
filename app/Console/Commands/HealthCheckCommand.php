<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Exception;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check 
                            {--detailed : Show detailed health information}
                            {--json : Output in JSON format}
                            {--no-interaction : Run without interaction}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform comprehensive health checks on the application';

    /**
     * Health check results
     *
     * @var array
     */
    protected $results = [];

    /**
     * Overall health status
     *
     * @var bool
     */
    protected $healthy = true;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting health check...');
        
        // Perform all health checks
        $this->checkDatabase();
        $this->checkRedis();
        $this->checkCache();
        $this->checkStorage();
        $this->checkEnvironment();
        
        if ($this->option('detailed')) {
            $this->checkSystemResources();
            $this->checkConfiguration();
        }

        // Output results
        if ($this->option('json')) {
            $this->outputJson();
        } else {
            $this->outputText();
        }

        // Exit with appropriate code
        return $this->healthy ? 0 : 1;
    }

    /**
     * Check database connectivity
     */
    protected function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            $this->addResult('database', true, 'Database connection successful');
            
            // Test a simple query
            $result = DB::select('SELECT 1 as test');
            if ($result[0]->test === 1) {
                $this->addResult('database_query', true, 'Database query test successful');
            } else {
                $this->addResult('database_query', false, 'Database query test failed');
            }
        } catch (Exception $e) {
            $this->addResult('database', false, 'Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Check Redis connectivity
     */
    protected function checkRedis()
    {
        try {
            $redis = Redis::connection();
            $pong = $redis->ping();
            
            if ($pong === 'PONG' || $pong === true) {
                $this->addResult('redis', true, 'Redis connection successful');
                
                // Test Redis operations
                $testKey = 'health_check_' . time();
                $redis->set($testKey, 'test_value', 'EX', 10);
                $value = $redis->get($testKey);
                $redis->del($testKey);
                
                if ($value === 'test_value') {
                    $this->addResult('redis_operations', true, 'Redis operations test successful');
                } else {
                    $this->addResult('redis_operations', false, 'Redis operations test failed');
                }
            } else {
                $this->addResult('redis', false, 'Redis ping failed');
            }
        } catch (Exception $e) {
            $this->addResult('redis', false, 'Redis connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Check cache functionality
     */
    protected function checkCache()
    {
        try {
            $testKey = 'health_check_cache_' . time();
            $testValue = 'test_cache_value';
            
            Cache::put($testKey, $testValue, 10);
            $retrievedValue = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrievedValue === $testValue) {
                $this->addResult('cache', true, 'Cache operations successful');
            } else {
                $this->addResult('cache', false, 'Cache operations failed');
            }
        } catch (Exception $e) {
            $this->addResult('cache', false, 'Cache test failed: ' . $e->getMessage());
        }
    }

    /**
     * Check storage accessibility
     */
    protected function checkStorage()
    {
        try {
            $storagePath = storage_path();
            $logsPath = storage_path('logs');
            $cachePath = storage_path('framework/cache');
            
            $checks = [
                'storage_readable' => is_readable($storagePath),
                'storage_writable' => is_writable($storagePath),
                'logs_writable' => is_writable($logsPath),
                'cache_writable' => is_writable($cachePath),
            ];
            
            $allGood = true;
            foreach ($checks as $check => $result) {
                if (!$result) {
                    $allGood = false;
                    $this->addResult($check, false, ucfirst(str_replace('_', ' ', $check)) . ' failed');
                }
            }
            
            if ($allGood) {
                $this->addResult('storage', true, 'Storage permissions are correct');
            }
        } catch (Exception $e) {
            $this->addResult('storage', false, 'Storage check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check environment configuration
     */
    protected function checkEnvironment()
    {
        $env = app()->environment();
        $debug = config('app.debug');
        $key = config('app.key');
        
        $this->addResult('environment', true, "Environment: {$env}");
        
        if (empty($key)) {
            $this->addResult('app_key', false, 'Application key is not set');
        } else {
            $this->addResult('app_key', true, 'Application key is set');
        }
        
        if ($env === 'production' && $debug) {
            $this->addResult('debug_mode', false, 'Debug mode should be disabled in production');
        } else {
            $this->addResult('debug_mode', true, 'Debug mode configuration is appropriate');
        }
    }

    /**
     * Check system resources (detailed mode only)
     */
    protected function checkSystemResources()
    {
        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseBytes(ini_get('memory_limit'));
        $memoryPercent = ($memoryUsage / $memoryLimit) * 100;
        
        $this->addResult('memory_usage', true, sprintf(
            'Memory usage: %s / %s (%.1f%%)',
            $this->formatBytes($memoryUsage),
            $this->formatBytes($memoryLimit),
            $memoryPercent
        ));
        
        // Disk space
        $diskFree = disk_free_space(storage_path());
        $diskTotal = disk_total_space(storage_path());
        $diskUsedPercent = (($diskTotal - $diskFree) / $diskTotal) * 100;
        
        if ($diskUsedPercent > 90) {
            $this->addResult('disk_space', false, sprintf(
                'Disk space critical: %.1f%% used',
                $diskUsedPercent
            ));
        } else {
            $this->addResult('disk_space', true, sprintf(
                'Disk space: %.1f%% used (%s free)',
                $diskUsedPercent,
                $this->formatBytes($diskFree)
            ));
        }
    }

    /**
     * Check configuration (detailed mode only)
     */
    protected function checkConfiguration()
    {
        $checks = [
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
            'mail_driver' => config('mail.default'),
        ];
        
        foreach ($checks as $key => $value) {
            $this->addResult("config_{$key}", true, ucfirst(str_replace('_', ' ', $key)) . ": {$value}");
        }
    }

    /**
     * Add a result to the results array
     */
    protected function addResult(string $check, bool $status, string $message)
    {
        $this->results[] = [
            'check' => $check,
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];
        
        if (!$status) {
            $this->healthy = false;
        }
    }

    /**
     * Output results in text format
     */
    protected function outputText()
    {
        $this->line('');
        $this->line('=== Health Check Results ===');
        $this->line('Timestamp: ' . now()->toDateTimeString());
        $this->line('Environment: ' . app()->environment());
        $this->line('');
        
        foreach ($this->results as $result) {
            $status = $result['status'] ? '✓' : '✗';
            $color = $result['status'] ? 'green' : 'red';
            
            $this->line("<fg={$color}>[{$status}] {$result['message']}</>");
        }
        
        $this->line('');
        if ($this->healthy) {
            $this->info('Overall Status: HEALTHY');
        } else {
            $this->error('Overall Status: UNHEALTHY');
        }
    }

    /**
     * Output results in JSON format
     */
    protected function outputJson()
    {
        $output = [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'healthy' => $this->healthy,
            'checks' => $this->results,
        ];
        
        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    /**
     * Parse bytes from PHP ini format
     */
    protected function parseBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Format bytes for human reading
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}