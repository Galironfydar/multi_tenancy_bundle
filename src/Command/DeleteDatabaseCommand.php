<?php

namespace Hakam\MultiTenancyBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Hakam\MultiTenancyBundle\Services\DbService;
use Hakam\MultiTenancyBundle\Services\TenantDbConfigurationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'tenant:database:delete',
    description: 'Deletes a tenant database or all tenant databases.',
)]
final class DeleteDatabaseCommand extends Command
{
    public function __construct(
        private ManagerRegistry $registry,
        private DbService $dbService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setAliases(['t:d:d'])
            ->addOption('all', null, InputOption::VALUE_NONE, 'Delete all tenant databases.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the deletion of the database(s).')
            ->addArgument('id', InputOption::VALUE_OPTIONAL, 'The ID of the tenant database to delete.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('force')) {
            $output->writeln('<error>You must use the --force option to delete a database.</error>');
            return Command::FAILURE;
        }

        if ($input->getOption('all')) {
            $this->deleteAllDatabases($output);
        } else {
            $id = $input->getArgument('id');
            if (!$id) {
                $output->writeln('<error>You must provide an ID or use the --all option.</error>');
                return Command::FAILURE;
            }
            $this->deleteDatabaseById($id, $output);
        }

        return Command::SUCCESS;
    }

    private function deleteAllDatabases(OutputInterface $output): void
    {
        $tenantDbs = $this->registry->getRepository(TenantDbConfigurationInterface::class)->findAll();
        foreach ($tenantDbs as $tenantDb) {
            $this->dbService->dropDatabase($tenantDb->getDbName());
            $output->writeln("Deleted database: {$tenantDb->getDbName()}");
        }
    }

    private function deleteDatabaseById(string $id, OutputInterface $output): void
    {
        $tenantDb = $this->registry->getRepository(TenantDbConfigurationInterface::class)->find($id);
        if (!$tenantDb) {
            $output->writeln('<error>Database with provided ID not found.</error>');
            return;
        }
        $this->dbService->dropDatabase($tenantDb->getDbName());
        $output->writeln("Deleted database: {$tenantDb->getDbName()}");
    }
}