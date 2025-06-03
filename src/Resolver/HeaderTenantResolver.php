<?php

namespace Hakam\MultiTenancyBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;

class HeaderTenantResolver implements TenantResolverInterface
{
    public function __construct(private string $header = 'X-Tenant-ID')
    {
    }

    public function resolveTenant(Request $request): ?string
    {
        return $request->headers->get($this->header);
    }
}
