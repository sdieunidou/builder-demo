<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AuthToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DashboardControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $connection = $em->getConnection();
        $connection->executeStatement('DELETE FROM auth_token');
        $connection->executeStatement('DELETE FROM "user"');
        static::ensureKernelShutdown();
    }

    private function createUser(string $email, string $plainPassword): User
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function loginAndGetToken(string $email, string $password): string
    {
        $client = static::getClient();
        $client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR)
        );
        $data = json_decode($client->getResponse()->getContent(), true);

        return $data['token'];
    }

    public function testGetWeeklyDashboardWithNoTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/dashboard/weekly');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetWeeklyDashboardWithValidTokenReturns200WithKpiValues(): void
    {
        $client = static::createClient();
        $this->createUser('dashboard_valid@example.com', 'secret');
        $token = $this->loginAndGetToken('dashboard_valid@example.com', 'secret');

        $client->request(
            'GET',
            '/dashboard/weekly',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('New users this week', $content === false ? '' : $content);
        $this->assertStringContainsString('Total users', $content === false ? '' : $content);
        $this->assertStringContainsString('Weekly KPIs:', $content === false ? '' : $content);
    }

    public function testGetWeeklyDashboardWithExpiredTokenReturns401(): void
    {
        $client = static::createClient();
        $user = $this->createUser('dashboard_expired@example.com', 'secret');

        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $authToken = new AuthToken();
        $authToken->setToken(bin2hex(random_bytes(32)));
        $authToken->setUser($user);
        $authToken->setExpiresAt(new \DateTimeImmutable('-1 hour'));
        $em->persist($authToken);
        $em->flush();

        $expiredToken = $authToken->getToken();

        $client->request(
            'GET',
            '/dashboard/weekly',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $expiredToken]
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetWeeklyDashboardWithInvalidatedTokenReturns401(): void
    {
        $client = static::createClient();
        $user = $this->createUser('dashboard_invalidated@example.com', 'secret');

        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $authToken = new AuthToken();
        $authToken->setToken(bin2hex(random_bytes(32)));
        $authToken->setUser($user);
        $authToken->setInvalidatedAt(new \DateTimeImmutable('-30 minutes'));
        $em->persist($authToken);
        $em->flush();

        $invalidatedToken = $authToken->getToken();

        $client->request(
            'GET',
            '/dashboard/weekly',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $invalidatedToken]
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
