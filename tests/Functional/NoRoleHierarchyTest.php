<?php

namespace MathisVincent\MaintenanceBundle\Tests\Functional;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

class NoRoleHierarchyTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return NoRoleHierarchyKernel::class;
    }

    public function testAllowedRoleGetsThroughWithoutRoleHierarchy(): void
    {
        $client = static::createClient();
        $manager = static::getContainer()->get(MaintenanceManager::class);
        $manager->activate('admin');

        try {
            $client->request('GET', '/');
            self::assertResponseStatusCodeSame(503);

            $client->loginUser(new InMemoryUser('admin', 'admin', ['ROLE_ADMIN']));
            $client->request('GET', '/');
            self::assertResponseIsSuccessful();
        } finally {
            $manager->deactivate();
        }
    }
}
