<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Production Optimizations
    |--------------------------------------------------------------------------
    |
    | This configuration file contains production-specific optimizations
    | for the financial control system.
    |
    */

    'cache' => [
        /*
         * Enable configuration caching in production
         */
        'config' => env('CACHE_CONFIG', true),

        /*
         * Enable route caching in production
         */
        'routes' => env('CACHE_ROUTES', true),

        /*
         * Enable view caching in production
         */
        'views' => env('CACHE_VIEWS', true),

        /*
         * Enable event caching in production
         */
        'events' => env('CACHE_EVENTS', true),
    ],

    'performance' => [
        /*
         * Enable OPcache optimizations
         */
        'opcache' => [
            'enabled' => env('OPCACHE_ENABLED', true),
            'validate_timestamps' => env('OPCACHE_VALIDATE_TIMESTAMPS', false),
            'max_accelerated_files' => env('OPCACHE_MAX_FILES', 10000),
            'memory_consumption' => env('OPCACHE_MEMORY', 256),
        ],

        /*
         * Database query optimizations
         */
        'database' => [
            'strict_mode' => env('DB_STRICT_MODE', true),
            'connection_timeout' => env('DB_TIMEOUT', 60),
            'query_log' => env('DB_QUERY_LOG', false),
        ],

        /*
         * Session optimizations
         */
        'session' => [
            'gc_probability' => env('SESSION_GC_PROBABILITY', 1),
            'gc_divisor' => env('SESSION_GC_DIVISOR', 1000),
            'gc_maxlifetime' => env('SESSION_GC_MAXLIFETIME', 7200),
        ],
    ],

    'monitoring' => [
        /*
         * Enable application monitoring
         */
        'enabled' => env('MONITORING_ENABLED', true),

        /*
         * Log slow queries (in milliseconds)
         */
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000),

        /*
         * Monitor memory usage
         */
        'memory_threshold' => env('MEMORY_THRESHOLD', '128M'),

        /*
         * Health check endpoints
         */
        'health_check' => [
            'enabled' => env('HEALTH_CHECK_ENABLED', true),
            'database' => env('HEALTH_CHECK_DB', true),
            'cache' => env('HEALTH_CHECK_CACHE', true),
        ],
    ],

    'backup' => [
        /*
         * Enable automatic backups
         */
        'enabled' => env('BACKUP_ENABLED', true),

        /*
         * Backup schedule (cron format)
         */
        'schedule' => env('BACKUP_SCHEDULE', '0 2 * * *'), // Daily at 2 AM

        /*
         * Backup retention (days)
         */
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),

        /*
         * Backup storage path
         */
        'storage_path' => env('BACKUP_STORAGE_PATH', storage_path('backups')),

        /*
         * Include files in backup
         */
        'include_files' => env('BACKUP_INCLUDE_FILES', false),
    ],

    'security' => [
        /*
         * Force HTTPS in production
         */
        'force_https' => env('FORCE_HTTPS', true),

        /*
         * Security headers
         */
        'headers' => [
            'hsts' => env('SECURITY_HSTS', true),
            'content_type_nosniff' => env('SECURITY_NOSNIFF', true),
            'frame_options' => env('SECURITY_FRAME_OPTIONS', 'DENY'),
            'xss_protection' => env('SECURITY_XSS_PROTECTION', true),
        ],

        /*
         * Rate limiting
         */
        'rate_limiting' => [
            'api' => env('RATE_LIMIT_API', 60), // requests per minute
            'web' => env('RATE_LIMIT_WEB', 1000), // requests per minute
        ],
    ],

];