<?php

namespace MathisVincent\MaintenanceBundle\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Tells the bundle which roles the current requester holds, to match against `allowed_roles`.
 */
interface MaintenanceRoleProviderInterface
{
    /**
     * @return string[]
     */
    public function getRoles(Request $request): array;
}
