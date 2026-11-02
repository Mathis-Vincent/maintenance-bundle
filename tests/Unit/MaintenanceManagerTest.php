<?php

namespace MathisVincent\MaintenanceBundle\Tests\Unit;

use MathisVincent\MaintenanceBundle\Event\MaintenanceActivatedEvent;
use MathisVincent\MaintenanceBundle\Event\MaintenanceAnnouncedEvent;
use MathisVincent\MaintenanceBundle\Event\MaintenanceDeactivatedEvent;
use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\KernelInterface;

class MaintenanceManagerTest extends TestCase
{
    private string $projectDir;
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
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

    public function testDispatchesEvents(): void
    {
        $events = [];
        foreach ([MaintenanceActivatedEvent::class, MaintenanceDeactivatedEvent::class, MaintenanceAnnouncedEvent::class] as $class) {
            $this->dispatcher->addListener($class, static function (object $event) use (&$events): void {
                $events[] = $event;
            });
        }

        $this->manager()->announce(123);
        $this->manager()->activate('admin', 60);
        $this->manager()->deactivate();
        $this->manager()->deactivate();

        self::assertCount(3, $events);
        self::assertSame(123, $events[0]->startsAt);
        self::assertSame('admin', $events[1]->level);
        self::assertEqualsWithDelta(time() + 60, $events[1]->endsAt, 2);
        self::assertSame('admin', $events[2]->level);
    }

    private function manager(): MaintenanceManager
    {
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDir);

        return new MaintenanceManager($kernel, $this->dispatcher);
    }
}
