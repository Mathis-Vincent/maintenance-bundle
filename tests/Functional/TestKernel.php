<?php

namespace MathisVincent\MaintenanceBundle\Tests\Functional;

use MathisVincent\MaintenanceBundle\MaintenanceBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Minimal host application: one route, an in-memory user provider and the bundle.
 */
class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new MaintenanceBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        // var/maintenance.json lives here, outside the repository
        return sys_get_temp_dir().'/maintenance-bundle-tests';
    }

    public function home(): Response
    {
        return new Response('Home');
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test-secret',
            'test' => true,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
        ]);

        $container->extension('security', [
            'password_hashers' => [InMemoryUser::class => 'plaintext'],
            'providers' => [
                'users' => [
                    'memory' => [
                        'users' => [
                            'admin' => ['password' => 'admin', 'roles' => ['ROLE_ADMIN']],
                            'support' => ['password' => 'support', 'roles' => ['ROLE_SUPPORT']],
                            'user' => ['password' => 'user', 'roles' => ['ROLE_USER']],
                        ],
                    ],
                ],
            ],
            'firewalls' => [
                'main' => ['lazy' => true, 'provider' => 'users'],
            ],
        ]);

        $container->extension('twig', [
            'default_path' => __DIR__.'/templates',
        ]);

        $container->extension('maintenance', [
            'role_lifetimes' => ['ROLE_ADMIN' => 3600],
            'levels' => [
                'default' => ['template' => 'maintenance.html.twig'],
                'admin' => [
                    'template' => 'maintenance.html.twig',
                    'allowed_ip' => ['10.0.0.0/24'],
                    'allowed_roles' => ['ROLE_ADMIN'],
                ],
                'support' => [
                    'template' => 'maintenance.html.twig',
                    'banner_template' => 'banner.html.twig',
                    'allowed_roles' => ['ROLE_ADMIN', 'ROLE_SUPPORT'],
                ],
                'broken' => ['template' => 'missing.html.twig', 'banner_template' => 'missing.html.twig'],
            ],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('home', '/')->controller([$this, 'home']);
    }
}
