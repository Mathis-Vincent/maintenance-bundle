<?php

namespace MathisVincent\MaintenanceBundle\Tests\Functional;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        $this->manager()->deactivate();
        $this->manager()->cancelAnnouncement();
        parent::tearDown();
    }

    public function testActivateWithDuration(): void
    {
        $tester = $this->tester('maintenance:activate');

        self::assertSame(Command::SUCCESS, $tester->execute(['--duration' => '3600']));
        self::assertEqualsWithDelta(time() + 3600, $this->manager()->getState()['ends_at'] ?? null, 5);
        self::assertStringContainsString('end by itself', $tester->getDisplay());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidDurations')]
    public function testActivateRejectsInvalidDuration(string $duration): void
    {
        $tester = $this->tester('maintenance:activate');

        self::assertSame(Command::FAILURE, $tester->execute(['--duration' => $duration]));
        self::assertNull($this->manager()->getState());
    }

    public static function invalidDurations(): iterable
    {
        yield ['0'];
        yield ['-60'];
        yield ['2h'];
        yield [''];
    }

    public function testAnnounce(): void
    {
        $tester = $this->tester('maintenance:announce');

        self::assertSame(Command::SUCCESS, $tester->execute(['date' => '+1 day']));
        self::assertEqualsWithDelta(time() + 86400, $this->manager()->getAnnouncement(), 3700);
        self::assertNull($this->manager()->getState());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidAnnouncementDates')]
    public function testAnnounceRejectsInvalidDate(array $input): void
    {
        $tester = $this->tester('maintenance:announce');

        self::assertSame(Command::FAILURE, $tester->execute($input));
        self::assertNull($this->manager()->getAnnouncement());
    }

    public static function invalidAnnouncementDates(): iterable
    {
        yield 'missing' => [[]];
        yield 'garbage' => [['date' => 'not a date']];
        yield 'past' => [['date' => '2000-01-01']];
    }

    public function testAnnounceCancel(): void
    {
        $this->manager()->announce(time() + 60);

        self::assertSame(Command::SUCCESS, $this->tester('maintenance:announce')->execute(['--cancel' => true]));
        self::assertNull($this->manager()->getAnnouncement());
    }

    public function testStatusShowsEndDateAndAnnouncement(): void
    {
        $this->manager()->activate('admin', 60);
        $this->manager()->announce(time() + 60);
        $tester = $this->tester('maintenance:status');

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('end by itself', $tester->getDisplay());
        self::assertStringContainsString('announced for', $tester->getDisplay());
    }

    public function testActivateUsesDefaultLevel(): void
    {
        $tester = $this->tester('maintenance:activate');

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame(['active' => true, 'level' => 'default'], array_diff_key($this->manager()->getState(), ['id' => 1]));
    }

    public function testActivateGivenLevel(): void
    {
        $tester = $this->tester('maintenance:activate');

        self::assertSame(Command::SUCCESS, $tester->execute(['level' => 'admin']));
        self::assertSame('admin', $this->manager()->getState()['level'] ?? null);
    }

    public function testActivateRejectsUnknownLevel(): void
    {
        $tester = $this->tester('maintenance:activate');

        self::assertSame(Command::FAILURE, $tester->execute(['level' => 'ghost']));
        self::assertStringContainsString('"ghost" does not exist', $tester->getDisplay());
        self::assertNull($this->manager()->getState());
    }

    public function testDeactivate(): void
    {
        $this->manager()->activate('default');

        self::assertSame(Command::SUCCESS, $this->tester('maintenance:deactivate')->execute([]));
        self::assertNull($this->manager()->getState());
    }

    public function testStatusInactive(): void
    {
        $tester = $this->tester('maintenance:status');

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('inactive', $tester->getDisplay());
    }

    public function testStatusActive(): void
    {
        $tester = $this->tester('maintenance:status');
        $this->manager()->activate('admin');

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('active with level: "admin"', $tester->getDisplay());
    }

    public function testStatusFlagsUnknownLevel(): void
    {
        $tester = $this->tester('maintenance:status');
        $this->manager()->activate('ghost');

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('requests are not blocked', $tester->getDisplay());
    }

    private function tester(string $name): CommandTester
    {
        return new CommandTester((new Application(self::bootKernel()))->find($name));
    }

    private function manager(): MaintenanceManager
    {
        return static::getContainer()->get(MaintenanceManager::class);
    }
}
