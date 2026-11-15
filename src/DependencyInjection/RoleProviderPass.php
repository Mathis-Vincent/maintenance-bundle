<?php

namespace MathisVincent\MaintenanceBundle\DependencyInjection;

use MathisVincent\MaintenanceBundle\Security\SecurityRoleProvider;
use MathisVincent\MaintenanceBundle\Service\MaintenanceRoleProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Falls back to the security-bundle role provider when security is enabled, and fails when roles are configured without any provider.
 */
final class RoleProviderPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasAlias(MaintenanceRoleProviderInterface::class)) {
            return;
        }

        if ($container->has('security.helper') && $container->has('security.role_hierarchy')) {
            $container->register(SecurityRoleProvider::class, SecurityRoleProvider::class)->setAutowired(true);
            $container->setAlias(MaintenanceRoleProviderInterface::class, SecurityRoleProvider::class);

            return;
        }

        /** @var array{levels: array<string, array{allowed_roles?: string[]}>} $config */
        $config = $container->getParameter('maintenance.config');
        if (array_filter(array_column($config['levels'], 'allowed_roles'))) {
            throw new \LogicException('A maintenance level uses "allowed_roles" but no role provider is available: enable symfony/security-bundle or set "maintenance.role_provider" to a service implementing '.MaintenanceRoleProviderInterface::class.'.');
        }
    }
}
