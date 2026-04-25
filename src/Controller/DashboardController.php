<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dashboard\WeeklyKpiBuilder;
use App\Repository\AuthTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AuthTokenRepository $authTokenRepository,
    ) {
    }

    #[Route('/dashboard/weekly', name: 'dashboard_weekly', methods: ['GET'])]
    public function weekly(Request $request, WeeklyKpiBuilder $kpi): Response
    {
        $authorizationHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return new JsonResponse('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($authorizationHeader, strlen('Bearer '));

        if ($token === '') {
            return new JsonResponse('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $authToken = $this->authTokenRepository->findOneBy(['token' => $token]);

        if ($authToken === null || !$authToken->isValid()) {
            return new JsonResponse('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $data = $kpi->build();

        return $this->render('dashboard/weekly.html.twig', $data);
    }
}
