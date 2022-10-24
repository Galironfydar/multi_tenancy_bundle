<?php

namespace Hakam\MultiTenancyBundle\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 *  Trait to add tenant database configuration to an entity.
 * @author Ramy Hakam <pencilsoft1@gmail.com>
 */

trait TenantDbConfigTrait
{
    #[ORM\Column(type: 'string', length: 255)]
    protected string $dbName;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $dbUserName;

    #[ORM\Column(type: 'string', length: 255)]
    protected ?string $dbPassword = null;

    /**
     * @return string
     */
    public function getDbName(): string
    {
        return $this->dbName;
    }

    /**
     * @param string $dbName
     * @return self
     */
    public function setDbName(string $dbName): self
    {
        $this->dbName = $dbName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDbUserName(): string
    {
        return $this->dbUserName;
    }

    /**
     * @param string $dbUser
     * @return self
     */
    public function setDbUserName(string $dbUser): self
    {
        $this->dbUserName = $dbUser;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbPassword(): ?string
    {
        return $this->dbPassword;
    }

    /**
     * @param string|null $dbPassword
     * @return self
     */
    public function setDbPassword(?string $dbPassword): self
    {
        $this->dbPassword = $dbPassword;
        return $this;
    }
}
