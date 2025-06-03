<?php

namespace Hakam\MultiTenancyBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;

class RequestAttributeTenantResolver implements TenantResolverInterface
{
    public function __construct(private string $attribute = 'tenant_id')
    {
    }

    public function resolveTenant(Request $request): ?string
    {
        return $request->attributes->get($this->attribute);
    }
}
