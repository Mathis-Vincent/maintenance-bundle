<?php

namespace MathisVincent\MaintenanceBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Main bundle class for the MathisVincent Maintenance Bundle.
 */
class MaintenanceBundle extends AbstractBundle
{
    /**
     * Configures the bundle options and tree structure.
     */
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
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    /**
     * Loads extension parameters into the container.
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('maintenance.config', $config);
        $container->import('../config/services.yaml');
    }
}
