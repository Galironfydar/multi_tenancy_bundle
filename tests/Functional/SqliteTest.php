<?php

namespace Hakam\MultiTenancyBundle\Tests\Functional;

use Hakam\MultiTenancyBundle\Enum\DriverTypeEnum;
use Hakam\MultiTenancyBundle\Event\SwitchDbEvent;
use Hakam\MultiTenancyBundle\Services\DbService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;
use Hakam\MultiTenancyBundle\Services\TenantDbConfigurationInterface;

class SqliteTest extends TestCase
{
    private ContainerInterface $container;
    private string $testDbPath;

    protected function setUp(): void
    {
        $cacheDir = sys_get_temp_dir() . '/multi_tenancy_test';
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $this->testDbPath = $cacheDir . '/test_' . uniqid() . '.db';
        
        $config = [
            'tenant_database_identifier' => 'id',
            'tenant_database_className' => TestTenantDbConfig::class,
            'tenant_connection' => [
                'driver' => 'pdo_sqlite',
                'path' => $this->testDbPath,
                'url' => 'sqlite:///' . $this->testDbPath
            ],
            'tenant_migration' => [
                'tenant_migration_namespace' => 'Test\Application\Migrations\Tenant',
                'tenant_migration_path' => 'tests/migrations/Tenant'
            ],
            'tenant_entity_manager' => [
                'tenant_naming_strategy' => 'doctrine.orm.naming_strategy.default',
                'mapping' => [
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/tests',
                    'prefix' => 'Tenant',
                    'alias' => 'Tenant'
                ]
            ]
        ];
        
        $kernel = new MultiTenancyBundleTestingKernel($config);
        $kernel->boot();
        $this->container = $kernel->getContainer();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
        $cacheDir = dirname($this->testDbPath);
        if (file_exists($cacheDir)) {
            rmdir($cacheDir);
        }
    }

    public function testSqliteConnection(): void
    {
        /** @var DbService $dbService */
        $dbService = $this->container->get(DbService::class);
        
        // Create a test database configuration
        $dbConfig = new TestTenantDbConfig();
        $dbConfig->setDriverType(DriverTypeEnum::SQLITE);
        $dbConfig->setDbName($this->testDbPath);
        
        // Create the database
        $result = $dbService->createDatabase($dbConfig);
        $this->assertEquals(1, $result);
        $this->assertTrue(file_exists($this->testDbPath));
        
        // Test database connection
        $eventDispatcher = $this->container->get('event_dispatcher');
        $eventDispatcher->dispatch(new SwitchDbEvent($dbConfig->getId()));
        
        $connection = $this->container->get('doctrine')->getConnection('tenant');
        $connection->connect(); // Explicitly connect
        $this->assertTrue($connection->isConnected());
    }
}

class TestTenantDbConfig implements TenantDbConfigurationInterface
{
    private string $dbName;
    private DriverTypeEnum $driverType = DriverTypeEnum::SQLITE;
    private DatabaseStatusEnum $databaseStatus = DatabaseStatusEnum::DATABASE_NOT_CREATED;

    public function getId(): ?int
    {
        return 1;
    }

    public function getDbName(): string
    {
        return $this->dbName;
    }

    public function setDbName(string $dbName): self
    {
        $this->dbName = $dbName;
        return $this;
    }

    public function getDbUsername(): ?string
    {
        return null;
    }

    public function getDbPassword(): ?string
    {
        return null;
    }

    public function getDbHost(): ?string
    {
        return null;
    }

    public function getDbPort(): ?string
    {
        return null;
    }

    public function getDatabaseStatus(): DatabaseStatusEnum
    {
        return $this->databaseStatus;
    }

    public function setDatabaseStatus(DatabaseStatusEnum $databaseStatus): self
    {
        $this->databaseStatus = $databaseStatus;
        return $this;
    }

    public function getDriverType(): DriverTypeEnum
    {
        return $this->driverType;
    }

    public function setDriverType(DriverTypeEnum $driverType): self
    {
        $this->driverType = $driverType;
        return $this;
    }

    public function getDsnUrl(): string
    {
        return 'sqlite:///' . $this->getDbName();
    }
} 