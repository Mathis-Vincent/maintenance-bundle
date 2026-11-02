<?php

namespace MathisVincent\MaintenanceBundle;

use MathisVincent\MaintenanceBundle\DependencyInjection\RoleProviderPass;
use MathisVincent\MaintenanceBundle\Service\MaintenanceRoleProviderInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Main bundle class for the MathisVincent Maintenance Bundle.
 *
 * @psalm-type LevelConfig = array{template: string, banner_template?: string|null, allowed_ip?: list<string>, allowed_roles?: list<string>, excluded_routes?: list<string>}
 * @psalm-type Config = array{role_lifetimes?: array<string, int>, levels: array<string, LevelConfig>}
 *
 * @psalm-suppress DeprecatedInterface AbstractBundle implements BundleInterface, deprecated since Symfony 8.1 only
 */
final class MaintenanceBundle extends AbstractBundle
{
    /**
     * Configures the bundle options and tree structure.
     *
     * @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall the config tree builder is fluent and untyped
     */
    #[\Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->arrayNode('role_lifetimes')
            ->useAttributeAsKey('role')
            ->integerPrototype()->end()
            ->defaultValue([
                'ROLE_ADMIN' => 604800,
                'ROLE_HELP_DESK' => 2592000,
            ])
            ->end()
            ->scalarNode('role_provider')->defaultNull()->end()
            ->arrayNode('levels')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->scalarNode('template')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('banner_template')->defaultNull()->end()
            ->arrayNode('allowed_ip')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('allowed_roles')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('excluded_routes')
            ->scalarPrototype()->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    /**
     * Loads extension parameters into the container.
     */
    #[\Override]
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->setParameter('maintenance.config', $config);
        $configurator->import('../config/services.yaml');

        $provider = (string) ($config['role_provider'] ?? '');
        if ('' !== $provider) {
            $container->setAlias(MaintenanceRoleProviderInterface::class, $provider);
        }
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RoleProviderPass());
    }
}
