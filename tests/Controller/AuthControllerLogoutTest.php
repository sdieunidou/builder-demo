<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AuthToken;
use App\Entity\User;
use App\Repository\AuthTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerLogoutTest extends WebTestCase
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

    /**
     * AC-1: Valid token → 204, token row has invalidatedAt set.
     */
    public function testLogoutWithValidTokenReturns204AndSoftDeletesToken(): void
    {
        $client = static::createClient();
        $this->createUser('sv12_valid@example.com', 'secret');
        $token = $this->loginAndGetToken('sv12_valid@example.com', 'secret');

        $client->request(
            'POST',
            '/auth/logout',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(204);

        $container = static::getContainer();
        /** @var AuthTokenRepository $repo */
        $repo = $container->get(AuthTokenRepository::class);

        // findOneByToken returns null for invalidated tokens (soft-deleted)
        $this->assertNull($repo->findOneByToken($token));

        // The row should still exist in DB with invalidatedAt set
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $em->clear();
        $authToken = $em->getRepository(AuthToken::class)->findOneBy(['token' => $token]);
        $this->assertNotNull($authToken);
        $this->assertNotNull($authToken->getInvalidatedAt());
    }

    /**
     * AC-2: Expired token → 401.
     */
    public function testLogoutWithExpiredTokenReturns401(): void
    {
        $client = static::createClient();
        $user = $this->createUser('sv12_expired@example.com', 'secret');

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
            'POST',
            '/auth/logout',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $expiredToken]
        );

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * AC-3: Already-invalidated token → 401.
     */
    public function testLogoutWithAlreadyInvalidatedTokenReturns401(): void
    {
        $client = static::createClient();
        $this->createUser('sv12_invalidated@example.com', 'secret');
        $token = $this->loginAndGetToken('sv12_invalidated@example.com', 'secret');

        // First logout — should be 204
        $client->request(
            'POST',
            '/auth/logout',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseStatusCodeSame(204);

        // Second logout with the same (now invalidated) token → 401
        $client->request(
            'POST',
            '/auth/logout',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * AC-4: Missing Authorization header → 400.
     */
    public function testLogoutWithNoAuthorizationHeaderReturns400(): void
    {
        $client = static::createClient();

        $client->request('POST', '/auth/logout');

        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * AC-5: Second request with same token after logout → 401.
     */
    public function testLogoutTwiceWithSameTokenReturns401OnSecondCall(): void
    {
        $client = static::createClient();
        $this->createUser('sv12_reuse@example.com', 'secret');
        $token = $this->loginAndGetToken('sv12_reuse@example.com', 'secret');

        $client->request(
            'POST',
            '/auth/logout',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseStatusCodeSame(204);

        $client->request(
            'POST',
            '/auth/logout',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * AC-6: Unknown token → 401.
     */
    public function testLogoutWithUnknownTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/auth/logout',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer unknowntoken123456789']
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
