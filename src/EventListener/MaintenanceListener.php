<?php

namespace MathisVincent\MaintenanceBundle\EventListener;

use MathisVincent\MaintenanceBundle\Service\MaintenanceAccessChecker;
use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use MathisVincent\MaintenanceBundle\Service\MaintenanceTokenSigner;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
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
    /**
     * Initializes the maintenance request listener.
     */
    public function __construct(
        private MaintenanceManager $maintenanceManager,
        private MaintenanceTokenSigner $tokenSigner,
        private MaintenanceAccessChecker $accessChecker,
        private ParameterBagInterface $params,
        private Environment $twig
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

        if ($this->accessChecker->isExcludedRoute($request, $levelConfig) || $this->accessChecker->hasValidToken($request, $level)) {
            return;
        }

        $userLifetime = $this->accessChecker->getAccessLifetime($request, $levelConfig, $config['role_lifetimes'] ?? []);

        if (null !== $userLifetime) {
            $cookie = Cookie::create(
                MaintenanceAccessChecker::COOKIE_NAME,
                $this->tokenSigner->generateToken($userLifetime, $level),
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
