<?php

namespace MathisVincent\MaintenanceBundle\Tests\Functional;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use MathisVincent\MaintenanceBundle\Service\MaintenanceRoleProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class NoSecurityTest extends TestCase
{
    private ?NoSecurityKernel $kernel = null;

    protected function tearDown(): void
    {
        $this->kernel?->getContainer()->get('test.service_container')->get(MaintenanceManager::class)->deactivate();
        $this->kernel?->shutdown();
    }

    public function testIpBypassWorksWithoutSecurityBundle(): void
    {
        $this->boot('ip');

        self::assertSame(503, $this->kernel->handle(Request::create('/'))->getStatusCode());
        self::assertSame(200, $this->kernel->handle(Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.5']))->getStatusCode());
    }

    public function testAllowedRolesWithoutProviderFailsAtCompile(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('no role provider is available');

        (new NoSecurityKernel('roles'))->boot();
    }

    public function testRoleProviderOptionPlugsACustomService(): void
    {
        $this->boot('provider');

        self::assertInstanceOf(StubRoleProvider::class, $this->kernel->getContainer()->get('test.service_container')->get(MaintenanceRoleProviderInterface::class));
        self::assertSame(200, $this->kernel->handle(Request::create('/'))->getStatusCode());
    }

    private function boot(string $variant): void
    {
        $this->kernel = new NoSecurityKernel($variant);
        $this->kernel->boot();
        $this->kernel->getContainer()->get('test.service_container')->get(MaintenanceManager::class)->activate('default');
    }
}
