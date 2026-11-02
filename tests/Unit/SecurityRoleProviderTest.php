<?php

namespace MathisVincent\MaintenanceBundle\Tests\Unit;

use MathisVincent\MaintenanceBundle\Security\SecurityRoleProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

class SecurityRoleProviderTest extends TestCase
{
    public function testNoTokenHasNoRoles(): void
    {
        self::assertSame([], $this->provider(null)->getRoles(new Request()));
    }

    public function testTokenRolesAreExpandedThroughHierarchy(): void
    {
        $roles = $this->provider(['ROLE_ADMIN'])->getRoles(new Request());

        self::assertEqualsCanonicalizing(['ROLE_ADMIN', 'ROLE_SUPPORT'], $roles);
    }

    public function testTokenOnlyRolesCount(): void
    {
        self::assertContains('ROLE_PREVIOUS_ADMIN', $this->provider(['ROLE_USER', 'ROLE_PREVIOUS_ADMIN'])->getRoles(new Request()));
    }

    /**
     * @param string[]|null $tokenRoles null means no token
     */
    private function provider(?array $tokenRoles): SecurityRoleProvider
    {
        $token = null;
        if (null !== $tokenRoles) {
            $token = $this->createStub(TokenInterface::class);
            $token->method('getRoleNames')->willReturn($tokenRoles);
        }
        $security = $this->createStub(Security::class);
        $security->method('getToken')->willReturn($token);

        return new SecurityRoleProvider($security, new RoleHierarchy(['ROLE_ADMIN' => ['ROLE_SUPPORT']]));
    }
}
