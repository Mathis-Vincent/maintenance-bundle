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
        parent::tearDown();
    }

    public function testActivateUsesDefaultLevel(): void
    {
        $tester = $this->tester('maintenance:activate');

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame(['active' => true, 'level' => 'default'], $this->manager()->getState());
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
