<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AuthToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ReportExportControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em         = $container->get(EntityManagerInterface::class);
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

    public function testNoTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reports/export/csv');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testInvalidTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/reports/export/csv',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalidtoken12345']
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testValidTokenWithWeeklyTypeReturns200WithCsvContentType(): void
    {
        $client = static::createClient();
        $this->createUser('export_weekly@example.com', 'secret');
        $token = $this->loginAndGetToken('export_weekly@example.com', 'secret');

        $client->request(
            'GET',
            '/reports/export/csv?type=weekly',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('week_start', $content === false ? '' : $content);
    }

    public function testValidTokenWithDailyTypeReturns200WithReportDateHeader(): void
    {
        $client = static::createClient();
        $this->createUser('export_daily@example.com', 'secret');
        $token = $this->loginAndGetToken('export_daily@example.com', 'secret');

        $client->request(
            'GET',
            '/reports/export/csv?type=daily',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('report_date', $content === false ? '' : $content);
    }

    public function testValidTokenWithUnknownTypeReturns400WithJsonError(): void
    {
        $client = static::createClient();
        $this->createUser('export_badtype@example.com', 'secret');
        $token = $this->loginAndGetToken('export_badtype@example.com', 'secret');

        $client->request(
            'GET',
            '/reports/export/csv?type=monthly',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Invalid type. Use weekly or daily.', $data['error']);
    }

    public function testValidResponseHasContentDispositionHeaderWithReportFilename(): void
    {
        $client = static::createClient();
        $this->createUser('export_disposition@example.com', 'secret');
        $token = $this->loginAndGetToken('export_disposition@example.com', 'secret');

        $client->request(
            'GET',
            '/reports/export/csv',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);
        $disposition = $client->getResponse()->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringStartsWith('attachment; filename="report-', $disposition);
    }
}
