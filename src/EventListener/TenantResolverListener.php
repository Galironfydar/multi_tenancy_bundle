<?php

namespace Hakam\MultiTenancyBundle\EventListener;

use Hakam\MultiTenancyBundle\Event\SwitchDbEvent;
use Hakam\MultiTenancyBundle\Resolver\TenantResolverChain;
use Hakam\MultiTenancyBundle\Services\TenantContext;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TenantResolverListener implements EventSubscriberInterface
{
    public function __construct(
        private TenantResolverChain $resolverChain,
        private TenantContext $tenantContext,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 20]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $tenant = $this->resolverChain->resolve($request);
        if ($tenant) {
            $this->tenantContext->setTenant($tenant);
            $this->dispatcher->dispatch(new SwitchDbEvent($tenant));
        }
    }
}
