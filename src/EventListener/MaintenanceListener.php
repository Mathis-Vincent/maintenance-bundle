<?php

namespace MathisVincent\MaintenanceBundle\EventListener;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use MathisVincent\MaintenanceBundle\Service\MaintenanceTokenSigner;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Listens to kernel requests to handle maintenance mode restrictions and automatic token generation.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: -10)]
class MaintenanceListener
{
    private const COOKIE_NAME = 'maintenance_auto_bypass';

    /**
     * Initializes the maintenance request listener.
     */
    public function __construct(
        private MaintenanceManager $maintenanceManager,
        private MaintenanceTokenSigner $tokenSigner,
        private ParameterBagInterface $params,
        private Environment $twig,
        private ?Security $security = null
    ) {
    }

    /**
     * Handles the incoming request and evaluates maintenance access rules.
     */
    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $state = $this->maintenanceManager->getState();
        if (!$state || empty($state['active'])) {
            return;
        }

        $level = $state['level'] ?? 'default';
        $config = $this->params->get('maintenance.config');

        if (!isset($config['levels'][$level])) {
            return;
        }

        $levelConfig = $config['levels'][$level];
        $request = $event->getRequest();

        $clientToken = $request->cookies->get(self::COOKIE_NAME);
        if ($clientToken && $this->tokenSigner->validateToken($clientToken, $level)) {
            return;
        }

        $isAuthorized = false;
        $userLifetime = 2592000;
        $roleLifetimes = $config['role_lifetimes'] ?? [];

        $clientIp = $request->getClientIp();
        $allowedIps = $levelConfig['allowed_ip'] ?? [];
        if (!empty($allowedIps) && IpUtils::checkIp($clientIp, $allowedIps)) {
            $isAuthorized = true;
        }

        $allowedRoles = $levelConfig['allowed_roles'] ?? [];
        if (!$isAuthorized && !empty($allowedRoles) && $this->security && $this->security->getUser()) {
            foreach ($allowedRoles as $role) {
                if ($this->security->isGranted($role)) {
                    $isAuthorized = true;
                    if (isset($roleLifetimes[$role])) {
                        $userLifetime = $roleLifetimes[$role];
                    }
                    break;
                }
            }
        }

        if ($isAuthorized) {
            if (!$clientToken || !$this->tokenSigner->validateToken($clientToken, $level)) {
                $clientToken = $this->tokenSigner->generateToken($userLifetime, $level);
            }

            $cookie = Cookie::create(
                self::COOKIE_NAME,
                $clientToken,
                time() + $userLifetime,
                '/',
                null,
                true,
                true,
                false,
                'lax'
            );

            $event->getRequest()->attributes->set('_maintenance_cookie_to_set', $cookie);

            return;
        }

        $template = $levelConfig['template'];

        try {
            $content = $this->twig->render($template);
        } catch (\Throwable $e) {
            $content = '<h1>Site in maintenance</h1><p>Please try again later.</p>';
        }

        $response = new Response($content, Response::HTTP_SERVICE_UNAVAILABLE);
        $event->setResponse($response);
    }

    /**
     * Attaches the bypass cookie to the outgoing HTTP response if authorized.
     */
    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $cookie = $event->getRequest()->attributes->get('_maintenance_cookie_to_set');
        if ($cookie) {
            $event->getResponse()->headers->setCookie($cookie);
        }
    }
}
