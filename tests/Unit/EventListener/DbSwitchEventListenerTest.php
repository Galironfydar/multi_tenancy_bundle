<?php

namespace Hakam\MultiTenancyBundle\Tests\Unit\EventListener;

use Hakam\MultiTenancyBundle\Doctrine\DBAL\TenantConnection;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;
use Hakam\MultiTenancyBundle\Services\TenantDbConfigurationInterface;
use PHPUnit\Framework\TestCase;
use Hakam\MultiTenancyBundle\EventListener\DbSwitchEventListener;
use Hakam\MultiTenancyBundle\Event\SwitchDbEvent;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Hakam\MultiTenancyBundle\Services\DbConfigService;
use Hakam\MultiTenancyBundle\Enum\DriverTypeEnum;
use Psr\Log\LoggerInterface;

class DbSwitchEventListenerTest extends TestCase
{
    public function testOnHakamMultiTenancyBundleEventSwitchDbEvent()
    {
        // mock the necessary dependencies
        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockDbConfigService = $this->createMock(DbConfigService::class);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->exactly(2))
            ->method('info');

        // create a test instance of the listener
        $listener = new DbSwitchEventListener($mockContainer, $mockDbConfigService, 'test_database_url', $mockLogger);

        // create a test event
        $testDbIndex = 1;
        $testEvent = new SwitchDbEvent($testDbIndex);

        // mock the expected behavior of the DbConfigService and ContainerInterface
        $mockDbConfig = new DbConfig();
        $mockDbConfig->setDbName('test_db_name');
        $mockDbConfig->setDbUsername('test_username');
        $mockDbConfig->setDbPassword('test_password');
        $mockDbConfig->setDbHost('127.0.0.1');
        $mockDbConfig->setDbPort('3306');
        $mockDbConfigService->expects($this->once())
            ->method('findDbConfig')
            ->with($testDbIndex)
            ->willReturn($mockDbConfig);
        $mockTenantConnection = $this->createMock(TenantConnection::class);
        $mockTenantConnection->expects($this->once())
            ->method('switchConnection');
        $mockDoctrine = $this->createMock(ManagerRegistry::class);
        $mockDoctrine->expects($this->once())
            ->method('getConnection')
            ->with('tenant')
            ->willReturn($mockTenantConnection);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($mockDoctrine);

        // trigger the event and test the result
        $listener->onHakamMultiTenancyBundleEventSwitchDbEvent($testEvent);
    }

    public function testLogsDoNotExposeSensitiveInformation(): void
    {
        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockDbConfigService = $this->createMock(DbConfigService::class);

        $captured = [];
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function ($message, array $context = []) use (&$captured) {
                $captured[] = ['message' => $message, 'context' => $context];
            });

        $listener = new DbSwitchEventListener(
            $mockContainer,
            $mockDbConfigService,
            'mysql://main_user:main_pass@127.0.0.1:3306/db',
            $mockLogger
        );

        $testEvent = new SwitchDbEvent(1);
        $mockDbConfig = new DbConfig();
        $mockDbConfig->setDbName('tenant_db');
        $mockDbConfig->setDbUsername('tenant_user');
        $mockDbConfig->setDbPassword('tenant_pass');
        $mockDbConfig->setDbHost('localhost');
        $mockDbConfig->setDbPort('3306');

        $mockDbConfigService->expects($this->once())
            ->method('findDbConfig')
            ->willReturn($mockDbConfig);

        $mockTenantConnection = $this->createMock(TenantConnection::class);
        $mockTenantConnection->expects($this->once())
            ->method('switchConnection');

        $mockDoctrine = $this->createMock(ManagerRegistry::class);
        $mockDoctrine->expects($this->once())
            ->method('getConnection')
            ->with('tenant')
            ->willReturn($mockTenantConnection);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($mockDoctrine);

        $listener->onHakamMultiTenancyBundleEventSwitchDbEvent($testEvent);

        $this->assertCount(2, $captured);
        foreach ($captured as $log) {
            $logStr = json_encode($log['context']);
            $this->assertStringNotContainsString('tenant_user', $logStr);
            $this->assertStringNotContainsString('tenant_pass', $logStr);
            $this->assertStringNotContainsString('main_user', $logStr);
            $this->assertStringNotContainsString('main_pass', $logStr);
        }
    }
}

class DbConfig implements TenantDbConfigurationInterface
{
    private string $dbName;
    private string $dbUsername;
    private ?string $dbPassword;
    private ?string $dbPort;
    private ?string $dbHost;
    private DriverTypeEnum $driverType = DriverTypeEnum::MYSQL;

    public function getDbName(): string
    {
        return $this->dbName;
    }

    public function setDbName(string $dbName): void
    {
        $this->dbName = $dbName;
    }

    public function getDbUsername(): string
    {
        return $this->dbUsername;
    }

    public function setDbUsername(string $dbUsername): void
    {
        $this->dbUsername = $dbUsername;
    }

    public function getDbPassword(): ?string
    {
        return $this->dbPassword;
    }

    public function setDbPassword(?string $dbPassword): void
    {
        $this->dbPassword = $dbPassword;
    }

    public function getId(): ?int
    {
        return 1;
    }

    public function getDatabaseStatus(): DatabaseStatusEnum
    {
         return DatabaseStatusEnum::DATABASE_CREATED;
    }

    public function setDatabaseStatus(DatabaseStatusEnum $databaseStatus): TenantDbConfigurationInterface
    {
       return $this;
    }



    /**
     * Get the value of dbPort
     */ 
    public function getDbPort(): null|string
    {
        return $this->dbPort;
    }

    /**
     * Set the value of dbPort
     *
     * @return  self
     */ 
    public function setDbPort($dbPort)
    {
        $this->dbPort = $dbPort;

        return $this;
    }

    /**
     * Get the value of dbHost
     */ 
    public function getDbHost(): null|string
    {
        return $this->dbHost;
    }

    /**
     * Set the value of dbHost
     *
     * @return  self
     */ 
    public function setDbHost($dbHost)
    {
        $this->dbHost = $dbHost;

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
        if ($this->driverType === DriverTypeEnum::SQLITE) {
            return sprintf('sqlite:///%s', $this->getDbName());
        }

        $dbHost = $this->getDbHost() ?: '127.0.0.1';
        $dbPort = $this->getDbPort() ?: '3306';
        $dbUsername = $this->getDbUsername();
        $dbPassword = $this->getDbPassword() ? ':' . $this->getDbPassword() : '';
        return sprintf('%s://%s%s@%s:%s', $this->driverType->value, $dbUsername, $dbPassword, $dbHost, $dbPort);
    }
}
