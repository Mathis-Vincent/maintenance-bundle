<?php

namespace MathisVincent\MaintenanceBundle\Twig;

use MathisVincent\MaintenanceBundle\Service\MaintenanceAccessChecker;
use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MaintenanceTwigExtension extends AbstractExtension
{
    public function __construct(
        private MaintenanceManager $maintenanceManager,
        private MaintenanceAccessChecker $accessChecker,
        private ParameterBagInterface $params,
        private RequestStack $requestStack,
        private Environment $twig
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('maintenance_is_active', [$this, 'isMaintenanceActive']),
            new TwigFunction('maintenance_level', [$this, 'getMaintenanceLevel']),
            new TwigFunction('maintenance_banner', [$this, 'renderBanner'], ['is_safe' => ['html']]),
        ];
    }

    public function isMaintenanceActive(): bool
    {
        $state = $this->maintenanceManager->getState();

        return !empty($state['active']);
    }

    public function getMaintenanceLevel(): string
    {
        $state = $this->maintenanceManager->getState();

        return $state['level'] ?? 'default';
    }

    public function renderBanner(): string
    {
        if (!$this->isMaintenanceActive()) {
            return '';
        }

        $state = $this->maintenanceManager->getState();
        $level = $state['level'] ?? 'default';

        $config = $this->params->get('maintenance.config');
        $levelConfig = $config['levels'][$level] ?? [];

        $template = $levelConfig['banner_template'] ?? null;
        if (!$template) {
            return '';
        }

        $request = $this->requestStack->getMainRequest();
        $isExcluded = $request && $this->accessChecker->isExcludedRoute($request, $levelConfig);

        // an excluded page is not in maintenance: only people who can bypass the level need the warning
        if ($isExcluded
            && !$this->accessChecker->hasValidToken($request, $level)
            && null === $this->accessChecker->getAccessLifetime($request, $levelConfig, [])) {
            return '';
        }

        try {
            return $this->twig->render($template, ['status' => ['page' => $this->describePageAccess($isExcluded, $levelConfig)]]);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Says who can reach the current page. IP values stay out of it, as they may be private.
     */
    private function describePageAccess(bool $isExcluded, array $levelConfig): TranslatableMessage
    {
        $roles = $levelConfig['allowed_roles'] ?? [];
        $hasIps = !empty($levelConfig['allowed_ip']);

        $case = match (true) {
            $isExcluded => 'everyone',
            $roles && $hasIps => 'roles_and_ips',
            (bool) $roles => 'roles',
            $hasIps => 'ips',
            default => 'nobody',
        };

        return new TranslatableMessage('maintenance.status.page.'.$case, ['%roles%' => implode(', ', $roles)], 'maintenance');
    }
}
