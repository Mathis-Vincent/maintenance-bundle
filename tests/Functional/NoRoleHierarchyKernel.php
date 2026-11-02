<?php

namespace MathisVincent\MaintenanceBundle\Tests\Functional;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * SecurityBundle enabled without any role_hierarchy in security.yaml.
 */
final class NoRoleHierarchyKernel extends TestKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/no_role_hierarchy';
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
            'providers' => ['users' => ['memory' => ['users' => ['admin' => ['password' => 'admin', 'roles' => ['ROLE_ADMIN']]]]]],
            'firewalls' => ['main' => ['lazy' => true, 'provider' => 'users']],
        ]);
        $container->extension('twig', ['default_path' => __DIR__.'/templates']);
        $container->extension('maintenance', [
            'levels' => ['admin' => ['template' => 'maintenance.html.twig', 'allowed_roles' => ['ROLE_ADMIN']]],
        ]);
    }
}
