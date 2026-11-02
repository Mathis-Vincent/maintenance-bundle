<?php

namespace MathisVincent\MaintenanceBundle\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * Decides who gets through a maintenance level, for the listener and the banner.
 */
class MaintenanceAccessChecker
{
    public const COOKIE_NAME = 'maintenance_auto_bypass';
    private const DEFAULT_LIFETIME = 2592000;

    public function __construct(
        private MaintenanceTokenSigner $tokenSigner,
        private ?Security $security = null
    ) {
    }

    public function isExcludedRoute(Request $request, array $levelConfig): bool
    {
        return \in_array($request->attributes->get('_route'), $levelConfig['excluded_routes'] ?? [], true);
    }

    public function hasValidToken(Request $request, string $level, string $id): bool
    {
        $token = $request->cookies->get(self::COOKIE_NAME);

        return $token && $this->tokenSigner->validateToken($token, $level, $id);
    }

    /**
     * Returns the bypass cookie lifetime granted by an allowed IP or role, null when neither matches.
     */
    public function getAccessLifetime(Request $request, array $levelConfig, array $roleLifetimes): ?int
    {
        $clientIp = $request->getClientIp();
        $allowedIps = $levelConfig['allowed_ip'] ?? [];
        if (null !== $clientIp && !empty($allowedIps) && IpUtils::checkIp($clientIp, $allowedIps)) {
            return self::DEFAULT_LIFETIME;
        }

        $allowedRoles = $levelConfig['allowed_roles'] ?? [];
        if (empty($allowedRoles) || !$this->security?->getUser()) {
            return null;
        }

        foreach ($allowedRoles as $role) {
            if ($this->security->isGranted($role)) {
                return $roleLifetimes[$role] ?? self::DEFAULT_LIFETIME;
            }
        }

        return null;
    }
}
