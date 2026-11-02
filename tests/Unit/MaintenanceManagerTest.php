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
        @unlink($this->projectDir.'/var/maintenance_announcement.json');
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

        $state = $this->manager()->getState();
        self::assertSame(['active' => true, 'level' => 'admin'], array_diff_key($state, ['id' => 1]));
        $this->manager()->activate('admin');
        self::assertNotSame($state['id'], $this->manager()->getState()['id']);
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

    public function testDurationStoresEndDate(): void
    {
        $this->manager()->activate('admin', 60);

        self::assertEqualsWithDelta(time() + 60, $this->manager()->getState()['ends_at'] ?? null, 5);
    }

    public function testStateIsNullOnceDurationHasElapsed(): void
    {
        $this->manager()->activate('admin', -1);

        self::assertNull($this->manager()->getState());
    }

    public function testAnnouncementIsReturnedUntilItsDate(): void
    {
        $startsAt = time() + 60;
        $this->manager()->announce($startsAt);

        self::assertSame($startsAt, $this->manager()->getAnnouncement());

        $this->manager()->announce(time() - 1);

        self::assertNull($this->manager()->getAnnouncement());
    }

    public function testCancelAnnouncement(): void
    {
        $this->manager()->announce(time() + 60);
        $this->manager()->cancelAnnouncement();

        self::assertNull($this->manager()->getAnnouncement());
    }

    public function testActivatingRemovesAnnouncement(): void
    {
        $this->manager()->announce(time() + 60);
        $this->manager()->activate('admin');

        self::assertNull($this->manager()->getAnnouncement());
    }

    private function manager(): MaintenanceManager
    {
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDir);

        return new MaintenanceManager($kernel);
    }
}
