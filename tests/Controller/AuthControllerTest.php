<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AuthToken;
use App\Entity\User;
use App\Repository\AuthTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
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

    public function testLoginSuccessReturnsToken(): void
    {
        $client = static::createClient();
        $this->createUser('valid@example.com', 'correct-password');

        $client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'valid@example.com', 'password' => 'correct-password'], \JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('expires_at', $data);
        $this->assertSame(64, strlen($data['token']));
    }

    public function testLoginWrongPasswordReturns401(): void
    {
        $client = static::createClient();
        $this->createUser('valid2@example.com', 'correct-password');

        $client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'valid2@example.com', 'password' => 'wrong-password'], \JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Invalid credentials', $data['error']);
    }

    public function testLoginUnknownEmailReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nobody@example.com', 'password' => 'some-password'], \JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Invalid credentials', $data['error']);
    }

    public function testLoginMissingFieldsReturns400(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'user@example.com'], \JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('email and password are required', $data['error']);
    }

    public function testLoginMissingEmailReturns400(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['password' => 'some-password'], \JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('email and password are required', $data['error']);
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

    public function testLogoutWithValidTokenReturns204AndDeletesToken(): void
    {
        $client = static::createClient();
        $this->createUser('logout_valid@example.com', 'secret');
        $token = $this->loginAndGetToken('logout_valid@example.com', 'secret');

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
        $this->assertNull($repo->findOneByToken($token));
    }

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

    public function testLogoutWithNoAuthorizationHeaderReturns400(): void
    {
        $client = static::createClient();

        $client->request('POST', '/auth/logout');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLogoutTwiceWithSameTokenReturns401OnSecondCall(): void
    {
        $client = static::createClient();
        $this->createUser('logout_reuse@example.com', 'secret');
        $token = $this->loginAndGetToken('logout_reuse@example.com', 'secret');

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

}
