<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\TrajetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// ============================================
// AnalyticsController - Statistiques
// ============================================

#[Route('/api/v1', name: 'api_v1_')]
class AnalyticsController extends AbstractController
{
  public function __construct(
    private readonly TrajetRepository $trajetRepository,
  ) {
  }

  /**
   * BONUS : Distances agrégées par code analytique
   *
   * GET /api/v1/stats/distances?from=2024-01-01&to=2024-12-31&groupBy=month
   */
  #[Route('/stats/distances', name: 'analytics_distances', methods: ['GET'])]
  public function getAnalyticDistances(Request $request): JsonResponse
  {
    try {
      // 1. Récupérer les paramètres de requête
      $from = $request->query->get('from');
      $to = $request->query->get('to');
      $groupBy = $request->query->get('groupBy', 'none');

      // 2. Valider les paramètres
      $validationErrors = $this->validateStatsRequest($from, $to, $groupBy);
      if (!empty($validationErrors)) {
        return $this->jsonError('Invalid parameters', Response::HTTP_BAD_REQUEST, $validationErrors);
      }

      // 3. Convertir les dates
      $fromDate = $from ? new \DateTimeImmutable($from) : null;
      $toDate = $to ? new \DateTimeImmutable($to . ' 23:59:59') : null;

      // 4. Récupérer les statistiques depuis le repository
      $stats = $this->trajetRepository->getStatsByAnalyticCode($fromDate, $toDate, $groupBy);

      // 5. Formater la réponse
      $items = array_map(function($stat) use ($from, $to) {
        $item = [
          'analyticCode' => $stat['analyticCode'],
          'totalDistanceKm' => round((float) $stat['totalDistanceKm'], 2),
        ];

        if ($from !== null) {
          $item['periodStart'] = $from;
        }

        if ($to !== null) {
          $item['periodEnd'] = $to;
        }

        if (isset($stat['groupKey'])) {
          $item['group'] = $stat['groupKey'];  // ← Changé de groupKey à group
        }

        return $item;
      }, $stats);

      $responseData = [
        'from' => $from,
        'to' => $to,
        'groupBy' => $groupBy,
        'items' => $items,
      ];

      return $this->json($responseData, Response::HTTP_OK);

    } catch (\Exception $e) {
      return $this->jsonError(
        'Internal server error: ' . $e->getMessage(),
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Valider les paramètres de la requête stats
   */
  private function validateStatsRequest(?string $from, ?string $to, string $groupBy): array
  {
    $errors = [];

    // Valider le format des dates
    if ($from !== null && !$this->isValidDate($from)) {
      $errors[] = 'Parameter "from" must be a valid date (YYYY-MM-DD)';
    }

    if ($to !== null && !$this->isValidDate($to)) {
      $errors[] = 'Parameter "to" must be a valid date (YYYY-MM-DD)';
    }

    // Vérifier que from <= to
    if ($from !== null && $to !== null) {
      $fromDate = new \DateTimeImmutable($from);
      $toDate = new \DateTimeImmutable($to);

      if ($fromDate > $toDate) {
        $errors[] = 'Parameter "from" must be before or equal to "to"';
      }
    }

    // Valider le groupBy
    $validGroupBy = ['day', 'month', 'year', 'none'];
    if (!in_array($groupBy, $validGroupBy, true)) {
      $errors[] = 'Parameter "groupBy" must be one of: ' . implode(', ', $validGroupBy);
    }

    return $errors;
  }

  /**
   * Vérifier si une chaîne est une date valide
   */
  private function isValidDate(string $date): bool
  {
    try {
      new \DateTimeImmutable($date);
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Créer une réponse d'erreur JSON
   */
  private function jsonError(string $message, int $statusCode, array $details = []): JsonResponse
  {
    $error = [
      'message' => $message,
    ];

    if (!empty($details)) {
      $error['details'] = $details;
    }

    return $this->json($error, $statusCode);
  }
}
