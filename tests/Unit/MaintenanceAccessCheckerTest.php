<?php

namespace MathisVincent\MaintenanceBundle\Tests\Unit;

use MathisVincent\MaintenanceBundle\Service\MaintenanceAccessChecker;
use MathisVincent\MaintenanceBundle\Service\MaintenanceRoleProviderInterface;
use MathisVincent\MaintenanceBundle\Service\MaintenanceTokenSigner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class MaintenanceAccessCheckerTest extends TestCase
{
    private const LEVEL = ['allowed_roles' => ['ROLE_ADMIN', 'ROLE_SUPPORT']];

    public function testRoleFromProviderGrantsItsLifetime(): void
    {
        $checker = $this->checker(['ROLE_SUPPORT']);

        self::assertSame(60, $checker->getAccessLifetime(new Request(), self::LEVEL, ['ROLE_SUPPORT' => 60]));
    }

    public function testRoleAbsentFromProviderIsDenied(): void
    {
        self::assertNull($this->checker(['ROLE_USER'])->getAccessLifetime(new Request(), self::LEVEL, []));
    }

    public function testRolesAreIgnoredWithoutProvider(): void
    {
        self::assertNull($this->checker(null)->getAccessLifetime(new Request(), self::LEVEL, []));
    }

    public function testIpStillWorksWithoutProvider(): void
    {
        $level = ['allowed_ip' => ['10.0.0.0/24']];
        $request = Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.5']);

        self::assertNotNull($this->checker(null)->getAccessLifetime($request, $level, []));
    }

    /**
     * @param string[]|null $roles null means no provider
     */
    private function checker(?array $roles): MaintenanceAccessChecker
    {
        $provider = null === $roles ? null : new class($roles) implements MaintenanceRoleProviderInterface {
            /** @param string[] $roles */
            public function __construct(private array $roles)
            {
            }

            public function getRoles(Request $request): array
            {
                return $this->roles;
            }
        };

        return new MaintenanceAccessChecker(new MaintenanceTokenSigner(new ParameterBag(['kernel.secret' => 's'])), $provider);
    }
}
