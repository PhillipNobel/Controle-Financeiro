<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends Controller
{
    /**
     * Perform comprehensive health check
     */
    public function index(Request $request): JsonResponse
    {
        $checks = [];
        $overallStatus = 'healthy';

        // Check if health checks are enabled
        if (!config('production.monitoring.health_check.enabled', true)) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Health checks are disabled'
            ], 503);
        }

        // Database health check
        if (config('production.monitoring.health_check.database', true)) {
            $checks['database'] = $this->checkDatabase();
            if ($checks['database']['status'] !== 'healthy') {
                $overallStatus = 'unhealthy';
            }
        }

        // Cache health check
        if (config('production.monitoring.health_check.cache', true)) {
            $checks['cache'] = $this->checkCache();
            if ($checks['cache']['status'] !== 'healthy') {
                $overallStatus = 'degraded';
            }
        }

        // Application health check
        $checks['application'] = $this->checkApplication();
        if ($checks['application']['status'] !== 'healthy') {
            $overallStatus = 'unhealthy';
        }

        // System resources check
        $checks['system'] = $this->checkSystemResources();
        if ($checks['system']['status'] !== 'healthy') {
            $overallStatus = 'degraded';
        }

        $response = [
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ];

        // Log unhealthy status
        if ($overallStatus !== 'healthy') {
            Log::warning('Health check failed', $response);
        }

        $statusCode = $overallStatus === 'healthy' ? 200 : 503;

        return response()->json($response, $statusCode);
    }

    /**
     * Simple health check endpoint
     */
    public function simple(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test database connection
            DB::connection()->getPdo();
            
            // Test a simple query
            $result = DB::select('SELECT 1 as test');
            
            $responseTime = (microtime(true) - $startTime) * 1000;

            if (empty($result) || $result[0]->test !== 1) {
                throw new \Exception('Database query returned unexpected result');
            }

            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'connection' => config('database.default'),
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            $testKey = 'health_check_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);

            // Test cache write
            Cache::put($testKey, $testValue, 60);

            // Test cache read
            $retrievedValue = Cache::get($testKey);

            // Test cache delete
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($retrievedValue !== $testValue) {
                throw new \Exception('Cache read/write test failed');
            }

            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('cache.default'),
            ];
        }
    }

    /**
     * Check application status
     */
    private function checkApplication(): array
    {
        try {
            $checks = [];

            // Check if application is in maintenance mode
            if (app()->isDownForMaintenance()) {
                return [
                    'status' => 'maintenance',
                    'message' => 'Application is in maintenance mode',
                ];
            }

            // Check storage directory permissions
            $storagePath = storage_path();
            if (!is_writable($storagePath)) {
                $checks['storage_writable'] = false;
            } else {
                $checks['storage_writable'] = true;
            }

            // Check cache directory permissions
            $cachePath = storage_path('framework/cache');
            if (!is_writable($cachePath)) {
                $checks['cache_writable'] = false;
            } else {
                $checks['cache_writable'] = true;
            }

            // Check logs directory permissions
            $logsPath = storage_path('logs');
            if (!is_writable($logsPath)) {
                $checks['logs_writable'] = false;
            } else {
                $checks['logs_writable'] = true;
            }

            $hasErrors = in_array(false, $checks, true);

            return [
                'status' => $hasErrors ? 'unhealthy' : 'healthy',
                'checks' => $checks,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check system resources
     */
    private function checkSystemResources(): array
    {
        try {
            $checks = [];

            // Memory usage
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseMemorySize(ini_get('memory_limit'));
            $memoryPercent = ($memoryUsage / $memoryLimit) * 100;

            $checks['memory'] = [
                'usage_bytes' => $memoryUsage,
                'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                'usage_percent' => round($memoryPercent, 2),
                'status' => $memoryPercent > 90 ? 'critical' : ($memoryPercent > 75 ? 'warning' : 'ok'),
            ];

            // Disk space (storage directory)
            $storagePath = storage_path();
            $diskFree = disk_free_space($storagePath);
            $diskTotal = disk_total_space($storagePath);
            $diskUsedPercent = (($diskTotal - $diskFree) / $diskTotal) * 100;

            $checks['disk'] = [
                'free_bytes' => $diskFree,
                'free_mb' => round($diskFree / 1024 / 1024, 2),
                'total_mb' => round($diskTotal / 1024 / 1024, 2),
                'used_percent' => round($diskUsedPercent, 2),
                'status' => $diskUsedPercent > 95 ? 'critical' : ($diskUsedPercent > 85 ? 'warning' : 'ok'),
            ];

            // Overall system status
            $systemStatus = 'healthy';
            if ($checks['memory']['status'] === 'critical' || $checks['disk']['status'] === 'critical') {
                $systemStatus = 'unhealthy';
            } elseif ($checks['memory']['status'] === 'warning' || $checks['disk']['status'] === 'warning') {
                $systemStatus = 'degraded';
            }

            return [
                'status' => $systemStatus,
                'checks' => $checks,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
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