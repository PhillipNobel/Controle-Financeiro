<?php

namespace App\Services;

class EnvironmentDetector
{
    /**
     * Detect the current environment based on APP_ENV and hostname
     */
    public function detectEnvironment(): string
    {
        // First priority: APP_ENV environment variable
        $appEnv = $this->getAppEnv();
        if ($appEnv) {
            return $this->normalizeEnvironment($appEnv);
        }

        // Second priority: hostname-based detection
        $hostname = $this->getHostname();
        
        if ($this->isLocalEnvironment($hostname)) {
            return 'local';
        }
        
        if ($this->isStagingEnvironment($hostname)) {
            return 'staging';
        }
        
        // Fallback to production for security (safe default)
        return 'production';
    }

    /**
     * Get APP_ENV value, checking both Laravel env() and direct getenv()
     */
    protected function getAppEnv(): ?string
    {
        // First try direct environment variable (for testing)
        $directEnv = getenv('APP_ENV');
        if ($directEnv !== false && $directEnv !== '') {
            return $directEnv;
        }

        // Then try Laravel's env() helper if available
        if (function_exists('env')) {
            $laravelEnv = env('APP_ENV');
            if ($laravelEnv && $laravelEnv !== 'testing') {
                return $laravelEnv;
            }
        }

        return null;
    }

    /**
     * Check if current environment is local development
     */
    public function isLocal(): bool
    {
        return $this->detectEnvironment() === 'local';
    }

    /**
     * Check if current environment is staging
     */
    public function isStaging(): bool
    {
        return $this->detectEnvironment() === 'staging';
    }

    /**
     * Check if current environment is production
     */
    public function isProduction(): bool
    {
        return $this->detectEnvironment() === 'production';
    }

    /**
     * Check if current environment is testing
     */
    public function isTesting(): bool
    {
        return $this->detectEnvironment() === 'testing';
    }

    /**
     * Get the current hostname
     */
    protected function getHostname(): string
    {
        // Try different methods to get hostname
        if (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }
        
        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }
        
        return gethostname() ?: 'unknown';
    }

    /**
     * Check if hostname indicates local environment
     */
    protected function isLocalEnvironment(string $hostname): bool
    {
        $localPatterns = [
            'localhost',
            '127.0.0.1',
            '::1',
            '.local',
            '.test',
            '.dev',
        ];

        foreach ($localPatterns as $pattern) {
            if (str_contains($hostname, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if hostname indicates staging environment
     */
    protected function isStagingEnvironment(string $hostname): bool
    {
        $stagingPatterns = [
            'staging',
            'stage',
            'dev.',
            'test.',
        ];

        foreach ($stagingPatterns as $pattern) {
            if (str_contains($hostname, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize environment name to standard values
     */
    protected function normalizeEnvironment(string $env): string
    {
        $env = strtolower(trim($env));
        
        // Map common variations to standard names
        $mappings = [
            'dev' => 'local',
            'development' => 'local',
            'stage' => 'staging',
            'prod' => 'production',
            'testing' => 'testing', // Keep testing as-is for unit tests
        ];

        return $mappings[$env] ?? $env;
    }

    /**
     * Get environment-specific configuration
     */
    public function getEnvironmentConfig(): array
    {
        $environment = $this->detectEnvironment();
        
        return match ($environment) {
            'local' => $this->getLocalConfig(),
            'staging' => $this->getStagingConfig(),
            'production' => $this->getProductionConfig(),
            'testing' => $this->getTestingConfig(),
            default => $this->getProductionConfig(), // Safe fallback
        };
    }

    /**
     * Get local development configuration
     */
    protected function getLocalConfig(): array
    {
        return [
            'debug' => true,
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'driver' => 'mysql',
                        'host' => '127.0.0.1',
                        'port' => '3306',
                        'database' => 'controle_financeiro_local',
                    ],
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => 'database/database.sqlite',
                    ],
                ],
            ],
            'cache' => [
                'default' => 'file',
            ],
            'session' => [
                'driver' => 'file',
            ],
            'logging' => [
                'level' => 'debug',
                'channels' => ['single', 'stderr'],
            ],
        ];
    }

    /**
     * Get staging configuration
     */
    protected function getStagingConfig(): array
    {
        return [
            'debug' => false,
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'driver' => 'mysql',
                        'host' => $this->getEnvValue('DB_HOST', 'mysql'),
                        'port' => $this->getEnvValue('DB_PORT', '3306'),
                        'database' => $this->getEnvValue('DB_DATABASE', 'controle_financeiro_staging'),
                    ],
                ],
            ],
            'cache' => [
                'default' => 'redis',
            ],
            'session' => [
                'driver' => 'redis',
            ],
            'logging' => [
                'level' => 'info',
                'channels' => ['daily', 'stderr'],
            ],
        ];
    }

    /**
     * Get production configuration
     */
    protected function getProductionConfig(): array
    {
        return [
            'debug' => false,
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'driver' => 'mysql',
                        'host' => $this->getEnvValue('DB_HOST', 'localhost'),
                        'port' => $this->getEnvValue('DB_PORT', '3306'),
                        'database' => $this->getEnvValue('DB_DATABASE', 'controle_financeiro'),
                    ],
                ],
            ],
            'cache' => [
                'default' => 'redis',
            ],
            'session' => [
                'driver' => 'redis',
            ],
            'logging' => [
                'level' => 'error',
                'channels' => ['daily'],
            ],
        ];
    }

    /**
     * Get testing configuration
     */
    protected function getTestingConfig(): array
    {
        return [
            'debug' => true,
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
            'cache' => [
                'default' => 'array',
            ],
            'session' => [
                'driver' => 'array',
            ],
            'logging' => [
                'level' => 'debug',
                'channels' => ['single'],
            ],
        ];
    }

    /**
     * Get database path with fallback for testing
     */
    protected function getDatabasePath(string $filename): string
    {
        // Try Laravel's database_path helper first
        if (function_exists('database_path')) {
            try {
                return database_path($filename);
            } catch (\Exception $e) {
                // Fall through to manual path construction
            }
        }
        
        // Fallback for testing or when Laravel is not fully booted
        $basePath = __DIR__ . '/../../database/';
        return $basePath . $filename;
    }

    /**
     * Get environment variable value with fallback for testing
     */
    protected function getEnvValue(string $key, string $default = ''): string
    {
        if (function_exists('env')) {
            try {
                return env($key, $default);
            } catch (\Exception $e) {
                // Fall through to getenv
            }
        }
        
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}