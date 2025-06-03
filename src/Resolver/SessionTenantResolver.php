<?php

namespace Hakam\MultiTenancyBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;

class SessionTenantResolver implements TenantResolverInterface
{
    public function __construct(private string $attribute = 'tenant_id')
    {
    }

    public function resolveTenant(Request $request): ?string
    {
        return $request->getSession()?->get($this->attribute);
    }
}
