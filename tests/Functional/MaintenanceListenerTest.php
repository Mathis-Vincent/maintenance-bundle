<?php

namespace MathisVincent\MaintenanceBundle\Tests\Functional;

use MathisVincent\MaintenanceBundle\EventListener\MaintenanceListener;
use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use MathisVincent\MaintenanceBundle\Service\MaintenanceTokenSigner;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MaintenanceListenerTest extends WebTestCase
{
    private const COOKIE = 'maintenance_auto_bypass';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        $this->manager()->deactivate();
        parent::tearDown();
    }

    public function testRequestPassesWhenMaintenanceIsOff(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertResponseNotHasCookie(self::COOKIE);
    }

    public function testAnonymousVisitorGetsMaintenancePage(): void
    {
        $this->manager()->activate('default');

        $this->client->request('GET', '/');

        self::assertResponseStatusCodeSame(503);
        self::assertStringContainsString('Maintenance in progress', (string) $this->client->getResponse()->getContent());
    }

    public function testUnknownLevelLetsRequestThrough(): void
    {
        $this->manager()->activate('ghost');

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testMissingTemplateFallsBackToBuiltInPage(): void
    {
        $this->manager()->activate('broken');

        $this->client->request('GET', '/');

        self::assertResponseStatusCodeSame(503);
        self::assertStringContainsString('Site in maintenance', (string) $this->client->getResponse()->getContent());
    }

    public function testAllowedIpGetsThroughWithDefaultLifetimeCookie(): void
    {
        $this->manager()->activate('admin');

        $this->client->request('GET', '/', server: ['REMOTE_ADDR' => '10.0.0.42']);

        self::assertResponseIsSuccessful();
        $this->assertBypassCookieLifetime(2592000);
    }

    public function testOtherIpIsBlocked(): void
    {
        $this->manager()->activate('admin');

        $this->client->request('GET', '/', server: ['REMOTE_ADDR' => '192.168.1.1']);

        self::assertResponseStatusCodeSame(503);
    }

    public function testAllowedRoleGetsThroughWithItsConfiguredLifetime(): void
    {
        $this->manager()->activate('admin');
        $this->client->loginUser($this->user('admin'));

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $this->assertBypassCookieLifetime(3600);
    }

    public function testAllowedRoleWithoutConfiguredLifetimeUsesDefault(): void
    {
        $this->manager()->activate('support');
        $this->client->loginUser($this->user('support'));

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $this->assertBypassCookieLifetime(2592000);
    }

    public function testLoggedInUserWithoutAllowedRoleIsBlocked(): void
    {
        $this->manager()->activate('admin');
        $this->client->loginUser($this->user('user'));

        $this->client->request('GET', '/');

        self::assertResponseStatusCodeSame(503);
    }

    public function testValidBypassCookieGetsThroughAnyLevel(): void
    {
        $this->manager()->activate('default');
        $token = static::getContainer()->get(MaintenanceTokenSigner::class)->generateToken(60);
        $this->client->getCookieJar()->set(new Cookie(self::COOKIE, $token));

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testAuthorizedUserKeepsItsValidToken(): void
    {
        $this->manager()->activate('admin');
        $token = static::getContainer()->get(MaintenanceTokenSigner::class)->generateToken(60);
        $this->client->getCookieJar()->set(new Cookie(self::COOKIE, $token));

        $this->client->request('GET', '/', server: ['REMOTE_ADDR' => '10.0.0.42']);

        self::assertResponseIsSuccessful();
        // a valid cookie returns early: no new cookie is issued
        self::assertResponseNotHasCookie(self::COOKIE);
    }

    public function testForgedBypassCookieIsRejected(): void
    {
        $this->manager()->activate('default');
        $this->client->getCookieJar()->set(new Cookie(self::COOKIE, 'forged.token'));

        $this->client->request('GET', '/');

        self::assertResponseStatusCodeSame(503);
    }

    public function testSubRequestsAreIgnored(): void
    {
        $this->manager()->activate('default');
        $listener = static::getContainer()->get(MaintenanceListener::class);
        $kernel = static::$kernel;
        $request = Request::create('/');

        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);
        $listener($requestEvent);
        self::assertFalse($requestEvent->hasResponse());

        $request->attributes->set('_maintenance_cookie_to_set', \Symfony\Component\HttpFoundation\Cookie::create(self::COOKIE, 'x'));
        $response = new Response();
        $listener->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response));
        self::assertSame([], $response->headers->getCookies());
    }

    private function assertBypassCookieLifetime(int $lifetime): void
    {
        self::assertResponseHasCookie(self::COOKIE);
        $cookie = $this->client->getResponse()->headers->getCookies()[0];
        self::assertEqualsWithDelta(time() + $lifetime, $cookie->getExpiresTime(), 5);
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue(static::getContainer()->get(MaintenanceTokenSigner::class)->validateToken((string) $cookie->getValue()));
    }

    private function user(string $username): \Symfony\Component\Security\Core\User\UserInterface
    {
        return static::getContainer()->get('security.user.provider.concrete.users')->loadUserByIdentifier($username);
    }

    private function manager(): MaintenanceManager
    {
        return static::getContainer()->get(MaintenanceManager::class);
    }
}
