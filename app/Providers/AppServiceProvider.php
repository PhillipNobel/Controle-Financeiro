<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DatabaseConfigurationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register production optimizations
        if ($this->app->environment('production')) {
            $this->registerProductionOptimizations();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure database for current environment
        $this->configureDatabaseForEnvironment();

        // Force HTTPS in production
        if (config('production.security.force_https', false) && $this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Setup database query logging for slow queries
        if (config('production.monitoring.enabled', false)) {
            $this->setupQueryLogging();
        }

        // Setup security headers
        if ($this->app->environment('production')) {
            $this->setupSecurityHeaders();
        }
    }

    /**
     * Configure database for current environment
     */
    private function configureDatabaseForEnvironment(): void
    {
        $databaseService = new DatabaseConfigurationService();
        $databaseService->configureForEnvironment();
        $databaseService->optimizeForEnvironment();
    }

    /**
     * Register production-specific optimizations
     */
    private function registerProductionOptimizations(): void
    {
        // Optimize configuration loading
        if (config('production.cache.config', true)) {
            $this->app->configurationIsCached();
        }

        // Register memory limit monitoring
        if (config('production.monitoring.enabled', false)) {
            register_shutdown_function(function () {
                $memoryUsage = memory_get_peak_usage(true);
                $memoryLimit = $this->parseMemorySize(ini_get('memory_limit'));
                $memoryPercent = ($memoryUsage / $memoryLimit) * 100;

                if ($memoryPercent > 90) {
                    Log::warning('High memory usage detected at shutdown', [
                        'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                        'memory_limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                        'usage_percent' => round($memoryPercent, 2),
                    ]);
                }
            });
        }
    }

    /**
     * Setup database query logging for performance monitoring
     */
    private function setupQueryLogging(): void
    {
        $slowQueryThreshold = config('production.monitoring.slow_query_threshold', 1000);

        DB::listen(function ($query) use ($slowQueryThreshold) {
            if ($query->time > $slowQueryThreshold) {
                Log::warning('Slow database query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }

    /**
     * Setup security headers for production
     */
    private function setupSecurityHeaders(): void
    {
        // This will be handled by middleware, but we can set some defaults here
        if (config('production.security.headers.hsts', true)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        if (config('production.security.headers.content_type_nosniff', true)) {
            header('X-Content-Type-Options: nosniff');
        }

        if (config('production.security.headers.frame_options', 'DENY')) {
            header('X-Frame-Options: ' . config('production.security.headers.frame_options'));
        }

        if (config('production.security.headers.xss_protection', true)) {
            header('X-XSS-Protection: 1; mode=block');
        }
    }

    /**
     * Parse memory size string to bytes
     */
    private function parseMemorySize(string $size): int
    {
        if ($size === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return (int) $size;
        }
    }
}
