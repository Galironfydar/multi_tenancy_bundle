<?php

namespace Hakam\MultiTenancyBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;

class JwtTenantResolver implements TenantResolverInterface
{
    public function __construct(private string $claim = 'tenant_id')
    {
    }

    public function resolveTenant(Request $request): ?string
    {
        $header = $request->headers->get('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }
        $token = substr($header, 7);
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return null;
        }
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        return $payload[$this->claim] ?? null;
    }
}
