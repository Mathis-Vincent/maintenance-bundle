<?php

namespace MathisVincent\MaintenanceBundle\Tests\Unit;

use MathisVincent\MaintenanceBundle\Security\SecurityRoleProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\InMemoryUser;

class SecurityRoleProviderTest extends TestCase
{
    public function testNoUserHasNoRoles(): void
    {
        self::assertSame([], $this->provider(null)->getRoles(new Request()));
    }

    public function testUserRolesAreExpandedThroughHierarchy(): void
    {
        $roles = $this->provider(new InMemoryUser('u', null, ['ROLE_ADMIN']))->getRoles(new Request());

        self::assertEqualsCanonicalizing(['ROLE_ADMIN', 'ROLE_SUPPORT'], $roles);
    }

    private function provider(?InMemoryUser $user): SecurityRoleProvider
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return new SecurityRoleProvider($security, new RoleHierarchy(['ROLE_ADMIN' => ['ROLE_SUPPORT']]));
    }
}
