<?php

namespace Hakam\MultiTenancyBundle\Tests\Functional;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Doctrine\Common\Annotations\AnnotationReader;
use Hakam\MultiTenancyBundle\HakamMultiTenancyBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class MultiTenancyBundleTestingKernel extends Kernel
{
    private array $multiTenancyConfig;

    public function __construct(array $multiTenancyConfig = [])
    {
        parent::__construct('test', true);
        $this->multiTenancyConfig = $multiTenancyConfig;
    }

    public function registerBundles(): array
    {
        return [
            new DoctrineBundle(),
            new DoctrineMigrationsBundle(),
            new HakamMultiTenancyBundle()
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->register('annotation_reader', AnnotationReader::class);
            
            // Configure Doctrine
            $container->loadFromExtension('doctrine', [
                'dbal' => [
                    'default_connection' => 'default',
                    'connections' => [
                        'default' => [
                            'driver' => 'pdo_sqlite',
                            'path' => '%kernel.cache_dir%/test_default.db'
                        ],
                        'tenant' => [
                            'driver' => 'pdo_sqlite',
                            'wrapper_class' => 'Hakam\\MultiTenancyBundle\\Doctrine\\DBAL\\TenantConnection',
                            'path' => '%kernel.cache_dir%/test_tenant.db',
                            'url' => 'sqlite:///%kernel.cache_dir%/test_tenant.db'
                        ]
                    ]
                ],
                'orm' => [
                    'auto_generate_proxy_classes' => true,
                    'default_entity_manager' => 'default',
                    'entity_managers' => [
                        'default' => [
                            'connection' => 'default',
                            'mappings' => [
                                'Test' => [
                                    'is_bundle' => false,
                                    'type' => 'attribute',
                                    'dir' => '%kernel.project_dir%/tests',
                                    'prefix' => 'Test'
                                ]
                            ]
                        ],
                        'tenant' => [
                            'connection' => 'tenant',
                            'mappings' => [
                                'Tenant' => [
                                    'is_bundle' => false,
                                    'type' => 'attribute',
                                    'dir' => '%kernel.project_dir%/tests',
                                    'prefix' => 'Tenant'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            
            // Configure the bundle
            $container->loadFromExtension('hakam_multi_tenancy', array_merge([
                'tenant_connection' => [
                    'driver' => 'pdo_sqlite',
                    'path' => '%kernel.cache_dir%/test_tenant.db'
                ],
                'tenant_database_className' => TestTenantDbConfig::class,
                'tenant_database_identifier' => 'id'
            ], $this->multiTenancyConfig));
        });
    }
}
