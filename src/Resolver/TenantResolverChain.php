<?php

namespace Hakam\MultiTenancyBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;

class TenantResolverChain
{
    /** @var TenantResolverInterface[] */
    private array $resolvers = [];

    public function __construct(iterable $resolvers = [])
    {
        foreach ($resolvers as $resolver) {
            $this->addResolver($resolver);
        }
    }

    public function addResolver(TenantResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    public function resolve(Request $request): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $tenant = $resolver->resolveTenant($request);
            if ($tenant) {
                return $tenant;
            }
        }
        return null;
    }
}
