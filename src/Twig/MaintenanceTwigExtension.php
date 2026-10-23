<?php

namespace MathisVincent\MaintenanceBundle\Twig;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MaintenanceTwigExtension extends AbstractExtension
{
    public function __construct(
        private MaintenanceManager $maintenanceManager,
        private ParameterBagInterface $params,
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

        try {
            return $this->twig->render($template);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
