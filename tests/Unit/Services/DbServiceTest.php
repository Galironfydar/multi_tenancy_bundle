<?php

namespace Hakam\MultiTenancyBundle\Tests\Unit\Services;

use Doctrine\DBAL\Driver\PDO\SQLite\Driver;
use Doctrine\DBAL\Connection;
use Hakam\MultiTenancyBundle\Doctrine\ORM\TenantEntityManager;
use Hakam\MultiTenancyBundle\Enum\DriverTypeEnum;
use Hakam\MultiTenancyBundle\Exception\MultiTenancyException;
use Hakam\MultiTenancyBundle\Services\DbService;
use Hakam\MultiTenancyBundle\Services\TenantDbConfigurationInterface;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Doctrine\ORM\EntityManagerInterface;
use org\bovigo\vfs\vfsStream;

class DbServiceTest extends TestCase
{
    private DbService $dbService;
    private string $testDir;
    
    protected function setUp(): void
    {
        // Create virtual filesystem
        vfsStream::setup('root');
        $this->testDir = vfsStream::url('root');
        
        $this->dbService = new DbService(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TenantEntityManager::class),
            $this->createMock(EntityManagerInterface::class),
            'TestEntity',
            ['db_url' => 'sqlite:///test.db']
        );
    }

    public function testCreateSqliteDatabase(): void
    {
        $dbConfig = $this->createMock(TenantDbConfigurationInterface::class);
        $dbPath = $this->testDir . '/test.db';
        
        $dbConfig->method('getDriverType')->willReturn(DriverTypeEnum::SQLITE);
        $dbConfig->method('getDbName')->willReturn($dbPath);
        
        $result = $this->dbService->createDatabase($dbConfig);
        
        $this->assertEquals(1, $result);
        $this->assertTrue(file_exists($dbPath));
    }

    public function testCreateSqliteDatabaseInNonExistentDirectory(): void
    {
        $dbConfig = $this->createMock(TenantDbConfigurationInterface::class);
        $dbPath = $this->testDir . '/subdir/test.db';
        
        $dbConfig->method('getDriverType')->willReturn(DriverTypeEnum::SQLITE);
        $dbConfig->method('getDbName')->willReturn($dbPath);
        
        $result = $this->dbService->createDatabase($dbConfig);
        
        $this->assertEquals(1, $result);
        $this->assertTrue(file_exists($dbPath));
    }

    public function testDropSqliteDatabase(): void
    {
        // Create a test SQLite file
        $dbPath = $this->testDir . '/test.db';
        touch($dbPath);
        
        // Mock the connection to return SQLite driver
        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')->willReturn(new Driver());
        
        $tenantEntityManager = $this->createMock(TenantEntityManager::class);
        $tenantEntityManager->method('getConnection')->willReturn($connection);
        
        $dbService = new DbService(
            $this->createMock(EventDispatcherInterface::class),
            $tenantEntityManager,
            $this->createMock(EntityManagerInterface::class),
            'TestEntity',
            ['db_url' => 'sqlite:///test.db']
        );
        
        $dbService->dropDatabase($dbPath);
        
        $this->assertFalse(file_exists($dbPath));
    }

    public function testDropNonExistentSqliteDatabase(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')->willReturn(new Driver());
        
        $tenantEntityManager = $this->createMock(TenantEntityManager::class);
        $tenantEntityManager->method('getConnection')->willReturn($connection);
        
        $dbService = new DbService(
            $this->createMock(EventDispatcherInterface::class),
            $tenantEntityManager,
            $this->createMock(EntityManagerInterface::class),
            'TestEntity',
            ['db_url' => 'sqlite:///test.db']
        );
        
        $this->expectException(MultiTenancyException::class);
        $this->expectExceptionMessage('SQLite database file does not exist: nonexistent.db');
        
        $dbService->dropDatabase('nonexistent.db');
    }
} 