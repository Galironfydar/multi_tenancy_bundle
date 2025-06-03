<?php

namespace Hakam\MultiTenancyBundle\Services;

class TenantContext
{
    private ?string $tenant = null;

    public function setTenant(?string $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getTenant(): ?string
    {
        return $this->tenant;
    }
}
