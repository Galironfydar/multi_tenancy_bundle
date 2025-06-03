<?php

namespace Hakam\MultiTenancyBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;
use Hakam\MultiTenancyBundle\Services\DbConfigService;
use Hakam\MultiTenancyBundle\Services\DbService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AsCommand(
    name: 'tenant:database:drop',
    description: 'Drop a tenant database by its identifier or name.',
)]
final class DropDatabaseCommand extends Command
{
    use CommandTrait;

    public function __construct(
        private readonly ManagerRegistry          $registry,
        private readonly ContainerInterface       $container,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DbService                $dbService,
        private readonly DbConfigService          $dbConfigService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setAliases(['t:d:d'])
            ->addArgument('dbIdentifier', InputArgument::REQUIRED, 'Tenant database identifier or name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dbIdentifier = $input->getArgument('dbIdentifier');

        try {
            $dbConfig = $this->dbConfigService->findDbConfig($dbIdentifier);
            $this->dbService->dropDatabase($dbConfig->getDbName());
            $dbConfig->setDatabaseStatus(DatabaseStatusEnum::DATABASE_NOT_CREATED);
            $this->registry->getManager()->persist($dbConfig);
            $this->registry->getManager()->flush();
            $output->writeln(sprintf('Database %s dropped successfully.', $dbConfig->getDbName()));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }
    }
}
