<?php

namespace MathisVincent\MaintenanceBundle\Tests\Unit;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class MaintenanceManagerTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/maintenance-manager-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        @unlink($this->projectDir.'/var/maintenance.json');
        @rmdir($this->projectDir.'/var');
        @rmdir($this->projectDir);
    }

    public function testStateIsNullByDefault(): void
    {
        self::assertNull($this->manager()->getState());
    }

    public function testActivateCreatesVarDirectoryAndStoresLevel(): void
    {
        $this->manager()->activate('admin');

        self::assertSame(['active' => true, 'level' => 'admin'], $this->manager()->getState());
    }

    public function testDeactivateRemovesState(): void
    {
        $manager = $this->manager();
        $manager->activate('admin');
        $manager->deactivate();

        self::assertNull($manager->getState());
    }

    public function testDeactivateWhenInactiveDoesNothing(): void
    {
        $this->manager()->deactivate();

        self::assertNull($this->manager()->getState());
    }

    private function manager(): MaintenanceManager
    {
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDir);

        return new MaintenanceManager($kernel);
    }
}
