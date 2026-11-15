<?php

namespace MathisVincent\MaintenanceBundle\Tests\Functional;

use MathisVincent\MaintenanceBundle\Service\MaintenanceRoleProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final class StubRoleProvider implements MaintenanceRoleProviderInterface
{
    #[\Override]
    public function getRoles(Request $request): array
    {
        return ['ROLE_CUSTOM'];
    }
}
