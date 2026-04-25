<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AuthToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthToken>
 */
class AuthTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthToken::class);
    }

    public function findOneByToken(string $token): ?AuthToken
    {
        $authToken = $this->findOneBy(['token' => $token]);

        if ($authToken === null || !$authToken->isValid()) {
            return null;
        }

        return $authToken;
    }

    public function invalidate(AuthToken $token): void
    {
        $token->setInvalidatedAt(new \DateTimeImmutable());
    }
}
