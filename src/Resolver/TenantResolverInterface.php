<?php

namespace Hakam\MultiTenancyBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;

interface TenantResolverInterface
{
    public function resolveTenant(Request $request): ?string;
}
