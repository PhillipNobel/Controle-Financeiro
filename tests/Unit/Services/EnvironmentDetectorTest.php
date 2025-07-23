<?php

namespace Tests\Unit\Services;

use App\Services\EnvironmentDetector;
use PHPUnit\Framework\TestCase;

class EnvironmentDetectorTest extends TestCase
{
    protected EnvironmentDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new EnvironmentDetector();
    }

    public function test_detects_local_environment_from_app_env()
    {
        // Clear any existing APP_ENV and set to local
        putenv('APP_ENV');
        putenv('APP_ENV=local');
        
        $environment = $this->detector->detectEnvironment();
        
        $this->assertEquals('local', $environment);
        $this->assertTrue($this->detector->isLocal());
        $this->assertFalse($this->detector->isStaging());
        $this->assertFalse($this->detector->isProduction());
        
        // Cleanup
        putenv('APP_ENV');
    }

    public function test_detects_staging_environment_from_app_env()
    {
        putenv('APP_ENV');
        putenv('APP_ENV=staging');
        
        $environment = $this->detector->detectEnvironment();
        
        $this->assertEquals('staging', $environment);
        $this->assertFalse($this->detector->isLocal());
        $this->assertTrue($this->detector->isStaging());
        $this->assertFalse($this->detector->isProduction());
        
        putenv('APP_ENV');
    }

    public function test_detects_production_environment_from_app_env()
    {
        putenv('APP_ENV');
        putenv('APP_ENV=production');
        
        $environment = $this->detector->detectEnvironment();
        
        $this->assertEquals('production', $environment);
        $this->assertFalse($this->detector->isLocal());
        $this->assertFalse($this->detector->isStaging());
        $this->assertTrue($this->detector->isProduction());
        
        putenv('APP_ENV');
    }

    public function test_normalizes_environment_names()
    {
        $testCases = [
            'dev' => 'local',
            'development' => 'local',
            'stage' => 'staging',
            'prod' => 'production',
            'LOCAL' => 'local',
            'STAGING' => 'staging',
        ];

        foreach ($testCases as $input => $expected) {
            putenv('APP_ENV');
            putenv("APP_ENV={$input}");
            $this->assertEquals($expected, $this->detector->detectEnvironment());
        }
        
        putenv('APP_ENV');
    }

    public function test_detects_local_from_hostname_patterns()
    {
        putenv('APP_ENV'); // Clear APP_ENV to test hostname detection
        
        $localHostnames = [
            'localhost',
            '127.0.0.1',
            'app.local',
            'myapp.test',
            'project.dev',
        ];

        foreach ($localHostnames as $hostname) {
            $_SERVER['HTTP_HOST'] = $hostname;
            $this->assertTrue($this->detector->isLocal(), "Failed for hostname: {$hostname}");
        }
        
        unset($_SERVER['HTTP_HOST']);
    }

    public function test_detects_staging_from_hostname_patterns()
    {
        putenv('APP_ENV');
        
        $stagingHostnames = [
            'staging.example.com',
            'stage.myapp.com',
            'dev.example.com',
            'test.myapp.com',
        ];

        foreach ($stagingHostnames as $hostname) {
            $_SERVER['HTTP_HOST'] = $hostname;
            $this->assertTrue($this->detector->isStaging(), "Failed for hostname: {$hostname}");
        }
        
        unset($_SERVER['HTTP_HOST']);
    }

    public function test_falls_back_to_production_by_default()
    {
        // Clear all environment variables that could affect detection
        putenv('APP_ENV=');
        putenv('APP_ENV');
        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['SERVER_NAME']);
        
        // Mock gethostname to return a non-local hostname
        $detector = new class extends EnvironmentDetector {
            protected function getHostname(): string
            {
                return 'production-server.com';
            }
        };
        
        $environment = $detector->detectEnvironment();
        
        $this->assertEquals('production', $environment);
        $this->assertTrue($detector->isProduction());
    }

    public function test_app_env_takes_priority_over_hostname()
    {
        putenv('APP_ENV=production');
        $_SERVER['HTTP_HOST'] = 'localhost'; // Would normally indicate local
        
        $environment = $this->detector->detectEnvironment();
        
        $this->assertEquals('production', $environment);
        
        putenv('APP_ENV');
        unset($_SERVER['HTTP_HOST']);
    }

    public function test_gets_local_environment_config()
    {
        putenv('APP_ENV=local');
        
        $config = $this->detector->getEnvironmentConfig();
        
        $this->assertTrue($config['debug']);
        $this->assertEquals('mysql', $config['database']['default']); // Local uses MySQL, not SQLite
        $this->assertEquals('file', $config['cache']['default']);
        $this->assertEquals('file', $config['session']['driver']);
        $this->assertEquals('debug', $config['logging']['level']);
        
        putenv('APP_ENV');
    }

    public function test_gets_staging_environment_config()
    {
        putenv('APP_ENV=staging');
        
        $config = $this->detector->getEnvironmentConfig();
        
        $this->assertFalse($config['debug']);
        $this->assertEquals('mysql', $config['database']['default']);
        $this->assertEquals('redis', $config['cache']['default']);
        $this->assertEquals('redis', $config['session']['driver']);
        $this->assertEquals('info', $config['logging']['level']);
        
        putenv('APP_ENV');
    }

    public function test_gets_production_environment_config()
    {
        putenv('APP_ENV=production');
        
        $config = $this->detector->getEnvironmentConfig();
        
        $this->assertFalse($config['debug']);
        $this->assertEquals('mysql', $config['database']['default']);
        $this->assertEquals('redis', $config['cache']['default']);
        $this->assertEquals('redis', $config['session']['driver']);
        $this->assertEquals('error', $config['logging']['level']);
        
        putenv('APP_ENV');
    }
}