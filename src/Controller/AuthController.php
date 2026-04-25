<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AuthToken;
use App\Entity\User;
use App\Repository\AuthTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    private const LOCKOUT_MINUTES = 15;
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthTokenRepository $authTokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $email = trim((string) ($data['email'] ?? ''));
            $password = (string) ($data['password'] ?? '');

            if ($email === '' || $password === '') {
                return $this->json(
                    ['error' => 'email and password are required'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            /** @var User|null $user */
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user === null) {
                return $this->json(
                    ['error' => 'Invalid credentials'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            if ($user->isLocked()) {
                $retryAfter = $user->getLockedUntil()->getTimestamp() - time();

                return $this->json(
                    ['error' => 'Account locked. Try again after ' . $user->getLockedUntil()->format(\DateTimeInterface::ATOM)],
                    Response::HTTP_LOCKED,
                    ['Retry-After' => max(0, $retryAfter)]
                );
            }

            if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                $attempts = $user->getFailedAttempts() + 1;
                if ($attempts >= 5) {
                    $user->setLockedUntil(new \DateTimeImmutable('+' . self::LOCKOUT_MINUTES . ' minutes'));
                    $user->setFailedAttempts(0);
                } else {
                    $user->setFailedAttempts($attempts);
                }
                $this->entityManager->flush();

                return $this->json(
                    ['error' => 'Invalid credentials'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $user->setFailedAttempts(0)->setLockedUntil(null);

            $tokenValue = bin2hex(random_bytes(32));

            $authToken = new AuthToken();
            $authToken->setToken($tokenValue);
            $authToken->setUser($user);

            $this->entityManager->persist($authToken);
            $this->entityManager->flush();

            return $this->json([
                'token' => $tokenValue,
                'expires_at' => $authToken->getExpiresAt()->format(\DateTimeInterface::ATOM),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error during login', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json(
                ['error' => 'Internal server error'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/auth/logout', name: 'auth_logout', methods: ['POST'])]
    public function logout(Request $request): Response
    {
        $authorizationHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return $this->json(
                ['error' => 'Authorization header missing or malformed'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $token = substr($authorizationHeader, strlen('Bearer '));

        if ($token === '') {
            return $this->json(
                ['error' => 'Authorization header missing or malformed'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $authToken = $this->authTokenRepository->findOneByToken($token);

        if ($authToken === null) {
            return $this->json(
                ['error' => 'Invalid or expired token'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $this->authTokenRepository->invalidate($authToken);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
