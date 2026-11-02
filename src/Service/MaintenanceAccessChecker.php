<?php

namespace MathisVincent\MaintenanceBundle\Service;

use MathisVincent\MaintenanceBundle\MaintenanceBundle;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * Decides who gets through a maintenance level, for the listener and the banner.
 *
 * @psalm-import-type LevelConfig from MaintenanceBundle
 */
final class MaintenanceAccessChecker
{
    public const COOKIE_NAME = 'maintenance_auto_bypass';
    private const DEFAULT_LIFETIME = 2592000;

    public function __construct(
        private MaintenanceTokenSigner $tokenSigner,
        private ?MaintenanceRoleProviderInterface $roleProvider = null
    ) {
    }

    /**
     * @param LevelConfig $levelConfig
     */
    public function isExcludedRoute(Request $request, array $levelConfig): bool
    {
        return \in_array($request->attributes->get('_route'), $levelConfig['excluded_routes'] ?? [], true);
    }

    public function hasValidToken(Request $request, string $level, string $id): bool
    {
        $token = $request->cookies->getString(self::COOKIE_NAME);

        return '' !== $token && $this->tokenSigner->validateToken($token, $level, $id);
    }

    /**
     * Returns the bypass cookie lifetime granted by an allowed IP or role, null when neither matches.
     *
     * @param LevelConfig        $levelConfig
     * @param array<string, int> $roleLifetimes
     */
    public function getAccessLifetime(Request $request, array $levelConfig, array $roleLifetimes): ?int
    {
        $clientIp = $request->getClientIp();
        $allowedIps = $levelConfig['allowed_ip'] ?? [];
        if (null !== $clientIp && [] !== $allowedIps && IpUtils::checkIp($clientIp, $allowedIps)) {
            return self::DEFAULT_LIFETIME;
        }

        $allowedRoles = $levelConfig['allowed_roles'] ?? [];
        if ([] === $allowedRoles || null === $this->roleProvider) {
            return null;
        }

        $roles = $this->roleProvider->getRoles($request);
        foreach ($allowedRoles as $role) {
            if (\in_array($role, $roles, true)) {
                return $roleLifetimes[$role] ?? self::DEFAULT_LIFETIME;
            }
        }

        return null;
    }
}
