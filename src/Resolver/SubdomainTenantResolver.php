<?php

namespace Hakam\MultiTenancyBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;

class SubdomainTenantResolver implements TenantResolverInterface
{
    public function __construct(private string $baseHost)
    {
    }

    public function resolveTenant(Request $request): ?string
    {
        $host = $request->getHost();
        if (!str_ends_with($host, $this->baseHost)) {
            return null;
        }
        $sub = substr($host, 0, -strlen($this->baseHost));
        $sub = rtrim($sub, '.');
        return $sub ?: null;
    }
}
