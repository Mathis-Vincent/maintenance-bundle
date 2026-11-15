<?php

namespace MathisVincent\MaintenanceBundle\Tests\Functional;

use MathisVincent\MaintenanceBundle\MaintenanceBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Host application without SecurityBundle. Variants: "ip" (IP bypass only), "roles" (allowed_roles, no provider), "provider" (allowed_roles with a custom role_provider).
 */
class NoSecurityKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(private readonly string $variant)
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new TwigBundle(), new MaintenanceBundle()];
    }

    public function getProjectDir(): string
    {
        return sys_get_temp_dir().'/maintenance-bundle-tests';
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->variant;
    }

    public function home(): Response
    {
        return new Response('Home');
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', ['secret' => 'test-secret', 'test' => true]);
        $container->extension('twig', ['default_path' => __DIR__.'/templates']);

        $level = ['template' => 'maintenance.html.twig', 'allowed_ip' => ['10.0.0.0/24']];
        $config = ['levels' => ['default' => $level]];
        if ('roles' === $this->variant || 'provider' === $this->variant) {
            $config['levels']['default']['allowed_roles'] = ['ROLE_CUSTOM'];
        }
        if ('provider' === $this->variant) {
            $container->services()->set('custom_provider', StubRoleProvider::class);
            $config['role_provider'] = 'custom_provider';
        }
        $container->extension('maintenance', $config);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('home', '/')->controller([$this, 'home']);
    }
}
