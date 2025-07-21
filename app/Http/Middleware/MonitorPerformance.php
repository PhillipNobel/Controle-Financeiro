<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MonitorPerformance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;

        // Log slow requests
        $slowQueryThreshold = config('production.monitoring.slow_query_threshold', 1000);
        if ($executionTime > $slowQueryThreshold) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time_ms' => round($executionTime, 2),
                'memory_usage_bytes' => $memoryUsage,
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Log high memory usage
        $memoryThreshold = $this->parseMemorySize(config('production.monitoring.memory_threshold', '128M'));
        if ($memoryUsage > $memoryThreshold) {
            Log::warning('High memory usage detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'execution_time_ms' => round($executionTime, 2),
                'user_id' => auth()->id(),
            ]);
        }

        // Add performance headers for debugging (only in non-production)
        if (config('app.debug')) {
            $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', round($memoryUsage / 1024 / 1024, 2) . 'MB');
        }

        return $response;
    }

    /**
     * Parse memory size string to bytes
     */
    private function parseMemorySize(string $size): int
    {
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