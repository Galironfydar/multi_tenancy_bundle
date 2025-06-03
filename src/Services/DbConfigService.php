<?php

namespace Hakam\MultiTenancyBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;

/**
 * @author Ramy Hakam <pencilsoft1@gmail.com>
 */
class DbConfigService
{
    private ObjectRepository $entityRepository;

    public function __construct(EntityManagerInterface $entityManager, private string $dbClassName, private string $dbIdentifier)
    {
        $this->entityRepository = $entityManager->getRepository($dbClassName);
    }

    public function findDbConfig(?string $identifier): TenantDbConfigurationInterface
    {
        if ($identifier) {
            $dbConfigObject = $this->entityRepository->findOneBy([
                $this->dbIdentifier => $identifier,
            ]);

            // Fallback to dbName search if not found
            if (null === $dbConfigObject) {
                $dbConfigObject = $this->entityRepository->findOneBy(['dbName' => $identifier]);
            }
        } else {
            $dbConfigObject = $this->entityRepository->findOneBy([
                'databaseStatus' => DatabaseStatusEnum::DATABASE_MIGRATED,
            ]);
        }

        if (null === $dbConfigObject) {
            throw new \RuntimeException(sprintf(
                'Tenant db repository "%s" returns NULL for identifier "%s = %s" ',
                get_class($this->entityRepository),
                $this->dbIdentifier,
                $identifier
            ));
        }

        if (!$dbConfigObject instanceof TenantDbConfigurationInterface) {
            throw new \LogicException(sprintf('The tenant db entity  " %s ". Should implement " Hakam\MultiTenancyBundle\TenantDbConfigurationInterface " ', get_class($dbConfigObject)));
        }

        return $dbConfigObject;
    }
}
