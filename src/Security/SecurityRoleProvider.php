<?php

namespace MathisVincent\MaintenanceBundle\Security;

use MathisVincent\MaintenanceBundle\Service\MaintenanceRoleProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Default provider: the roles of the security token (not only the user's), role hierarchy included.
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
        $token = $this->security->getToken();

        return $token ? $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()) : [];
    }
}
