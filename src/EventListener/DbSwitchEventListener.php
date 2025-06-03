<?php

namespace Hakam\MultiTenancyBundle\EventListener;

use Hakam\MultiTenancyBundle\Event\SwitchDbEvent;
use Hakam\MultiTenancyBundle\Services\DbConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Hakam\MultiTenancyBundle\Enum\DriverTypeEnum;
use Psr\Log\LoggerInterface;

/**
 * @author Ramy Hakam <pencilsoft1@gmail.com>
 */
class DbSwitchEventListener implements EventSubscriberInterface
{

    public function __construct(
        private ContainerInterface $container,
        private DbConfigService    $dbConfigService,
        private string             $databaseURL,
        private LoggerInterface    $logger
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return
            [
                SwitchDbEvent::class => 'onHakamMultiTenancyBundleEventSwitchDbEvent',
            ];
    }

    public function onHakamMultiTenancyBundleEventSwitchDbEvent(SwitchDbEvent $switchDbEvent): void
    {
        $dbConfig = $this->dbConfigService->findDbConfig($switchDbEvent->getDbIndex());
        $this->logger->info('Tenant switch requested', [
            'db_index' => $switchDbEvent->getDbIndex(),
            'db_name' => $dbConfig->getDbName(),
            'driver' => $dbConfig->getDriverType()->value,
        ]);

        $tenantConnection = $this->container->get('doctrine')->getConnection('tenant');
        
        // Handle SQLite differently since it doesn't use host/port/user/pass
        if ($dbConfig->getDriverType() === DriverTypeEnum::SQLITE) {
            $params = [
                'driver' => 'pdo_sqlite',
                'path' => $dbConfig->getDbName(),
                'url' => $dbConfig->getDsnUrl(),
                'dbname' => $dbConfig->getDbName()
            ];
        } else {
            $params = [
                'driver' => 'pdo_mysql',
                'dbname' => $dbConfig->getDbName(),
                'user' => $dbConfig->getDbUsername() ?? $this->parseDatabaseUrl($this->databaseURL)['user'],
                'password' => $dbConfig->getDbPassword() ?? $this->parseDatabaseUrl($this->databaseURL)['password'],
                'host' => $dbConfig->getDbHost() ?? $this->parseDatabaseUrl($this->databaseURL)['host'],
                'port' => $dbConfig->getDbPort() ?? $this->parseDatabaseUrl($this->databaseURL)['port'],
            ];
        }
        
        $tenantConnection->switchConnection($params);
        $this->logger->info('Tenant connection switched', [
            'dbname' => $params['dbname'],
            'driver' => $params['driver'],
            'host' => $params['host'] ?? null,
            'port' => $params['port'] ?? null,
        ]);
    }

    private function parseDatabaseUrl(string $databaseURL): array
    {
        $url = parse_url($databaseURL);
        return [
            'dbname' => substr($url['path'], 1),
            'user' => $url['user'],
            'password' => $url['pass'],
            'host' => $url['host'],
            'port' => $url['port'],
        ];
    }
}
