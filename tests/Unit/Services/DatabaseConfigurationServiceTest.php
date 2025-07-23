<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DatabaseConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;

class DatabaseConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DatabaseConfigurationService();
    }

    public function test_configures_local_environment(): void
    {
        // Set environment to local
        app()->detectEnvironment(function () {
            return 'local';
        });

        $this->service->configureForEnvironment();

        $this->assertEquals('mysql', config('database.default'));
        $this->assertFalse(config('database.connections.mysql.strict'));
        $this->assertEquals('InnoDB', config('database.connections.mysql.engine'));
    }

    public function test_configures_staging_environment(): void
    {
        // Set environment to staging
        app()->detectEnvironment(function () {
            return 'staging';
        });

        $this->service->configureForEnvironment();

        $this->assertEquals('mysql', config('database.default'));
        $this->assertTrue(config('database.connections.mysql.strict'));
        $this->assertEquals('InnoDB', config('database.connections.mysql.engine'));
    }

    public function test_configures_production_environment(): void
    {
        // Set environment to production
        app()->detectEnvironment(function () {
            return 'production';
        });

        $this->service->configureForEnvironment();

        $this->assertEquals('mysql', config('database.default'));
        $this->assertTrue(config('database.connections.mysql.strict'));
        $this->assertEquals('InnoDB', config('database.connections.mysql.engine'));
    }

    public function test_optimizes_for_local_environment(): void
    {
        app()->detectEnvironment(function () {
            return 'local';
        });

        $this->service->optimizeForEnvironment();

        $this->assertEquals(30, config('database.connections.mysql.options.' . PDO::ATTR_TIMEOUT));
        $this->assertTrue(config('database.connections.mysql.options.' . PDO::ATTR_EMULATE_PREPARES));
    }

    public function test_optimizes_for_staging_environment(): void
    {
        app()->detectEnvironment(function () {
            return 'staging';
        });

        $this->service->optimizeForEnvironment();

        $this->assertEquals(60, config('database.connections.mysql.options.' . PDO::ATTR_TIMEOUT));
        $this->assertTrue(config('database.connections.mysql.options.' . PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));
    }

    public function test_optimizes_for_production_environment(): void
    {
        app()->detectEnvironment(function () {
            return 'production';
        });

        $this->service->optimizeForEnvironment();

        $this->assertEquals(60, config('database.connections.mysql.options.' . PDO::ATTR_TIMEOUT));
        $this->assertTrue(config('database.connections.mysql.options.' . PDO::ATTR_PERSISTENT));
        $this->assertTrue(config('database.connections.mysql.options.' . PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));
    }

    public function test_gets_connection_info(): void
    {
        $info = $this->service->getConnectionInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('driver', $info);
        $this->assertArrayHasKey('host', $info);
        $this->assertArrayHasKey('port', $info);
        $this->assertArrayHasKey('database', $info);
        $this->assertArrayHasKey('username', $info);
        $this->assertArrayHasKey('engine', $info);
        $this->assertArrayHasKey('strict', $info);
    }

    public function test_test_connection_returns_boolean(): void
    {
        $result = $this->service->testConnection();
        $this->assertIsBool($result);
    }

    public function test_fallback_to_default_configuration(): void
    {
        // Set unknown environment
        app()->detectEnvironment(function () {
            return 'unknown';
        });

        $this->service->configureForEnvironment();

        // Should fallback to production-like settings
        $this->assertEquals('mysql', config('database.default'));
        $this->assertTrue(config('database.connections.mysql.strict'));
    }

    public function test_database_connections_are_properly_configured(): void
    {
        $connections = config('database.connections');

        // Test that all required connections exist
        $this->assertArrayHasKey('mysql', $connections);
        $this->assertArrayHasKey('mysql_local', $connections);
        $this->assertArrayHasKey('mysql_staging', $connections);
        $this->assertArrayHasKey('mysql_production', $connections);

        // Test mysql_local configuration
        $localConfig = $connections['mysql_local'];
        $this->assertEquals('mysql', $localConfig['driver']);
        $this->assertEquals('127.0.0.1', $localConfig['host']);
        $this->assertEquals('8889', $localConfig['port']); // MAMP default
        $this->assertFalse($localConfig['strict']);

        // Test mysql_staging configuration
        $stagingConfig = $connections['mysql_staging'];
        $this->assertEquals('mysql', $stagingConfig['driver']);
        $this->assertEquals('mysql', $stagingConfig['host']); // Docker container
        $this->assertTrue($stagingConfig['strict']);

        // Test mysql_production configuration
        $productionConfig = $connections['mysql_production'];
        $this->assertEquals('mysql', $productionConfig['driver']);
        $this->assertEquals('mysql', $productionConfig['host']); // Docker container
        $this->assertTrue($productionConfig['strict']);
    }
}
