<?php

namespace MathisVincent\MaintenanceBundle\Tests\Functional;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MaintenanceTwigExtensionTest extends KernelTestCase
{
    private const TEMPLATE = '{{ maintenance_is_active() ? "on" : "off" }}|{{ maintenance_level() }}|{{ maintenance_banner() }}';

    protected function tearDown(): void
    {
        $this->manager()->deactivate();
        parent::tearDown();
    }

    public function testInactive(): void
    {
        self::assertSame('off|default|', $this->render());
    }

    public function testActiveLevelWithBanner(): void
    {
        $this->manager()->activate('support');

        self::assertSame("on|support|<div class=\"banner\">Maintenance banner: ROLE_ADMIN, ROLE_SUPPORT</div>\n", $this->render());
    }

    public function testBannerStatusForLevelWithOnlyAllowedIps(): void
    {
        $this->manager()->activate('ip_only');

        self::assertSame("on|ip_only|<div class=\"banner\">Maintenance banner: allowed IPs</div>\n", $this->render());
    }

    public function testBannerStatusForLevelWithoutAccess(): void
    {
        $this->manager()->activate('closed');

        self::assertSame("on|closed|<div class=\"banner\">Maintenance banner: nobody</div>\n", $this->render());
    }

    public function testBundleTranslationsCanBeOverriddenByTheProject(): void
    {
        $translator = static::getContainer()->get('translator');

        self::assertSame('tout le monde', $translator->trans('maintenance.status.page.everyone', [], 'maintenance', 'fr'));
        self::assertSame('project override', $translator->trans('maintenance.status.page.nobody', [], 'maintenance', 'fr'));
    }

    public function testActiveLevelWithoutBanner(): void
    {
        $this->manager()->activate('admin');

        self::assertSame('on|admin|', $this->render());
    }

    public function testBrokenBannerTemplateRendersNothing(): void
    {
        $this->manager()->activate('broken');

        self::assertSame('on|broken|', $this->render());
    }

    private function render(): string
    {
        $twig = static::getContainer()->get('twig');

        return $twig->createTemplate(self::TEMPLATE)->render();
    }

    private function manager(): MaintenanceManager
    {
        return static::getContainer()->get(MaintenanceManager::class);
    }
}
