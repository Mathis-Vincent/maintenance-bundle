<?php

namespace MathisVincent\MaintenanceBundle\Tests\Unit;

use MathisVincent\MaintenanceBundle\DependencyInjection\RoleProviderPass;
use MathisVincent\MaintenanceBundle\MaintenanceBundle;
use MathisVincent\MaintenanceBundle\Security\SecurityRoleProvider;
use MathisVincent\MaintenanceBundle\Service\MaintenanceRoleProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RoleProviderPassTest extends TestCase
{
    public function testConfiguredProviderWins(): void
    {
        $container = $this->container(true);
        $container->register('my_provider', \stdClass::class);
        $container->setAlias(MaintenanceRoleProviderInterface::class, 'my_provider');
        $container->setDefinition('security.helper', new Definition());
        $container->setDefinition('security.role_hierarchy', new Definition());

        (new RoleProviderPass())->process($container);

        self::assertSame('my_provider', (string) $container->getAlias(MaintenanceRoleProviderInterface::class));
    }

    public function testDefaultsToSecurityProviderWhenSecurityIsEnabled(): void
    {
        $container = $this->container(true);
        $container->setDefinition('security.helper', new Definition());
        $container->setDefinition('security.role_hierarchy', new Definition());

        (new RoleProviderPass())->process($container);

        self::assertSame(SecurityRoleProvider::class, (string) $container->getAlias(MaintenanceRoleProviderInterface::class));
    }

    public function testRolesWithoutProviderFail(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('no role provider is available');

        (new RoleProviderPass())->process($this->container(true));
    }

    public function testNoRolesWithoutProviderCompiles(): void
    {
        $container = $this->container(false);

        (new RoleProviderPass())->process($container);

        self::assertFalse($container->hasAlias(MaintenanceRoleProviderInterface::class));
    }

    public function testBundleRegistersThePass(): void
    {
        $container = new ContainerBuilder();
        (new MaintenanceBundle())->build($container);

        $passes = array_filter($container->getCompilerPassConfig()->getBeforeOptimizationPasses(), static fn ($pass) => $pass instanceof RoleProviderPass);
        self::assertCount(1, $passes);
    }

    private function container(bool $withRoles): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('maintenance.config', ['levels' => ['a' => ['template' => 't', 'allowed_roles' => $withRoles ? ['ROLE_ADMIN'] : []]]]);

        return $container;
    }
}
