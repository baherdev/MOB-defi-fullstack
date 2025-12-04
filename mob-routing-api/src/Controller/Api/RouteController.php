<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\RoutingService;
use App\Service\RoutingException;
use App\Repository\CodeAnalyticsRepository;
use App\Repository\TrainRepository;
use App\Repository\TrajetRepository;
use App\Entity\Trajet;
use App\Entity\TrajetSegment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1', name: 'api_v1_')]
class RouteController extends AbstractController
{
  public function __construct(
    private readonly ValidatorInterface $validator,
    private readonly RoutingService $routingService,
    private readonly CodeAnalyticsRepository $codeAnalyticsRepository,
    private readonly TrainRepository $trainRepository,
    private readonly TrajetRepository $trajetRepository,
  ) {
  }

  /**
   * Calculer un trajet A → B
   *
   * @Route("/routes", name="calculate_route", methods=["POST"])
   */
  #[Route('/routes', name: 'calculate_route', methods: ['POST'])]
  public function calculateRoute(Request $request): JsonResponse
  {
    try {
      // 1. Décoder la requête JSON
      $data = json_decode($request->getContent(), true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        return $this->jsonError('Invalid JSON format', Response::HTTP_BAD_REQUEST);
      }

      // 2. Valider les champs requis
      $validationErrors = $this->validateRouteRequest($data);
      if (!empty($validationErrors)) {
        return $this->jsonError('Validation failed', Response::HTTP_BAD_REQUEST, $validationErrors);
      }

      $fromStationId = $data['fromStationId'];
      $toStationId = $data['toStationId'];
      $analyticCode = $data['analyticCode'];

      // 3. Vérifier que le code analytique existe
      $codeAnalytics = $this->codeAnalyticsRepository->findByLabel($analyticCode);
      if ($codeAnalytics === null) {
        return $this->jsonError(
          "Analytic code not found: $analyticCode",
          Response::HTTP_UNPROCESSABLE_ENTITY,
          ["Available codes: PASSAGER, FRET, MAINTENANCE, TEST, TOURISME"]
        );
      }

      // 4. Calculer le trajet avec Dijkstra
      $result = $this->routingService->findShortestPath($fromStationId, $toStationId);

      // 5. Créer un train par défaut (ou récupérer depuis la requête)
      $train = $this->trainRepository->findOneBy([])
        ?? throw new \RuntimeException('No train available');

      // 6. Créer le trajet
      $trajet = new Trajet(
        $train,
        $result['path'][0],
        $result['path'][count($result['path']) - 1],
        $codeAnalytics
      );
      $trajet->setDistanceKmTotal($result['distance']);

      // 7. Ajouter les segments au trajet
      foreach ($result['segments'] as $index => $segment) {
        $trajetSegment = new TrajetSegment($trajet, $segment, $index + 1);
        $trajet->addTrajetSegment($trajetSegment);
      }

      // 8. Sauvegarder en base
      $this->trajetRepository->save($trajet);

      // 9. Construire la réponse
      $pathCodes = $this->routingService->getPathCodes($result['path']);

      $responseData = [
        'id' => (string) $trajet->getId(),
        'fromStationId' => $fromStationId,
        'toStationId' => $toStationId,
        'analyticCode' => $analyticCode,
        'distanceKm' => round($result['distance'], 2),
        'path' => $pathCodes,
        'createdAt' => $trajet->getCreatedAt()->format(\DateTimeInterface::RFC3339),
      ];

      return $this->json($responseData, Response::HTTP_CREATED);

    } catch (RoutingException $e) {
      return $this->jsonError(
        $e->getMessage(),
        Response::HTTP_UNPROCESSABLE_ENTITY
      );
    } catch (\Exception $e) {
      return $this->jsonError(
        'Internal server error: ' . $e->getMessage(),
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Valider les données de la requête
   */
  private function validateRouteRequest(array $data): array
  {
    $errors = [];

    if (empty($data['fromStationId'])) {
      $errors[] = 'Field "fromStationId" is required';
    }

    if (empty($data['toStationId'])) {
      $errors[] = 'Field "toStationId" is required';
    }

    if (empty($data['analyticCode'])) {
      $errors[] = 'Field "analyticCode" is required';
    }

    if (isset($data['fromStationId']) && isset($data['toStationId'])
      && $data['fromStationId'] === $data['toStationId']) {
      $errors[] = 'fromStationId and toStationId must be different';
    }

    return $errors;
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


