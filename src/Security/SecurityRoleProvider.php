<?php

namespace MathisVincent\MaintenanceBundle\Security;

use MathisVincent\MaintenanceBundle\Service\MaintenanceRoleProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Default provider: the roles of the logged-in user, role hierarchy included.
 */
final class SecurityRoleProvider implements MaintenanceRoleProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RoleHierarchyInterface $roleHierarchy
    ) {
    }

    #[\Override]
    public function getRoles(Request $request): array
    {
        $user = $this->security->getUser();

        return $user ? $this->roleHierarchy->getReachableRoleNames($user->getRoles()) : [];
    }
}
