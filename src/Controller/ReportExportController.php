<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dashboard\WeeklyKpiBuilder;
use App\Digest\DigestReportBuilder;
use App\Export\CsvReportExporter;
use App\Repository\AuthTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReportExportController extends AbstractController
{
    public function __construct(
        private readonly AuthTokenRepository $authTokenRepository,
    ) {
    }

    #[Route('/reports/export/csv', name: 'report_export_csv', methods: ['GET'])]
    public function exportCsv(
        Request $request,
        WeeklyKpiBuilder $weeklyKpiBuilder,
        DigestReportBuilder $digestReportBuilder,
        CsvReportExporter $exporter,
    ): Response {
        $authorizationHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($authorizationHeader, strlen('Bearer '));

        if ($token === '') {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $authToken = $this->authTokenRepository->findOneByToken($token);

        if ($authToken === null) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $type = $request->query->get('type', 'weekly');

        if ($type === 'weekly') {
            $data    = $weeklyKpiBuilder->build();
            $headers = ['week_start', 'week_end', 'new_users', 'total_users'];
            $rows    = [[$data['week_start'], $data['week_end'], $data['new_users'], $data['total_users']]];
        } elseif ($type === 'daily') {
            $data    = $digestReportBuilder->buildForDate(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
            $headers = ['report_date', 'new_users', 'total_users'];
            $rows    = [[$data['report_date'], $data['new_users'], $data['total_users']]];
        } else {
            return new JsonResponse(['error' => 'Invalid type. Use weekly or daily.'], Response::HTTP_BAD_REQUEST);
        }

        $csvContent = $exporter->export($headers, $rows);

        return new Response(
            $csvContent,
            200,
            [
                'Content-Type'           => 'text/csv; charset=UTF-8',
                'Content-Disposition'    => 'attachment; filename="report-' . date('Y-m-d') . '.csv"',
                'Cache-Control'          => 'no-store, no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
