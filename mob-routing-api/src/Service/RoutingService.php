<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Station;
use App\Entity\NetworkSegment;
use App\Repository\StationRepository;
use App\Repository\NetworkSegmentRepository;

/**
 * Service de calcul de trajets avec algorithme de Dijkstra
 */
class RoutingService
{
  public function __construct(
    private readonly StationRepository $stationRepository,
    private readonly NetworkSegmentRepository $networkSegmentRepository,
  ) {
  }

  /**
   * Calculer le plus court chemin entre deux stations
   *
   * @return array{path: Station[], distance: float, segments: NetworkSegment[]}
   * @throws RoutingException
   */
  public function findShortestPath(string $fromStationCode, string $toStationCode): array
  {
    // 1. Récupérer les stations
    $fromStation = $this->stationRepository->findOneBy(['shortName' => $fromStationCode]);
    $toStation = $this->stationRepository->findOneBy(['shortName' => $toStationCode]);

    if ($fromStation === null) {
      throw new RoutingException("Station not found: $fromStationCode");
    }

    if ($toStation === null) {
      throw new RoutingException("Station not found: $toStationCode");
    }

    if ($fromStation->getId() === $toStation->getId()) {
      throw new RoutingException("Start and end stations must be different");
    }

    // 2. Charger tous les segments du réseau
    $allSegments = $this->networkSegmentRepository->findAll();

    if (empty($allSegments)) {
      throw new RoutingException("No network segments available");
    }

    // 3. Construire le graphe
    $graph = $this->buildGraph($allSegments);

    // 4. Exécuter Dijkstra
    $result = $this->dijkstra($graph, $fromStation->getId(), $toStation->getId());

    if ($result === null) {
      throw new RoutingException(
        "No path found between {$fromStationCode} and {$toStationCode}"
      );
    }

    // 5. Reconstruire le chemin avec les stations et segments
    return $this->reconstructPath($result['path'], $result['distance'], $allSegments);
  }

  /**
   * Construire le graphe depuis les segments
   *
   * @param NetworkSegment[] $segments
   * @return array<int, array<int, float>> Format: [stationId => [neighborId => distance]]
   */
  private function buildGraph(array $segments): array
  {
    $graph = [];

    foreach ($segments as $segment) {
      $parentId = $segment->getParentStation()->getId();
      $childId = $segment->getChildStation()->getId();
      $distance = $segment->getDistanceKm();

      // Ajouter l'arête dans le graphe
      if (!isset($graph[$parentId])) {
        $graph[$parentId] = [];
      }
      $graph[$parentId][$childId] = $distance;
    }

    return $graph;
  }

  /**
   * Algorithme de Dijkstra pour trouver le plus court chemin
   *
   * @param array<int, array<int, float>> $graph
   * @return array{path: int[], distance: float}|null
   */
  private function dijkstra(array $graph, int $startId, int $endId): ?array
  {
    // Initialisation
    $distances = [];      // Distance minimale depuis le départ
    $previous = [];       // Station précédente dans le chemin optimal
    $unvisited = [];      // File de priorité des stations non visitées

    // Initialiser toutes les distances à l'infini
    foreach (array_keys($graph) as $stationId) {
      $distances[$stationId] = PHP_FLOAT_MAX;
      $previous[$stationId] = null;
      $unvisited[$stationId] = true;
    }

    // Ajouter aussi les voisins comme nœuds possibles
    foreach ($graph as $neighbors) {
      foreach (array_keys($neighbors) as $neighborId) {
        if (!isset($distances[$neighborId])) {
          $distances[$neighborId] = PHP_FLOAT_MAX;
          $previous[$neighborId] = null;
          $unvisited[$neighborId] = true;
        }
      }
    }

    // Distance de départ = 0
    $distances[$startId] = 0.0;

    // Tant qu'il reste des stations non visitées
    while (!empty($unvisited)) {
      // Trouver la station non visitée avec la plus petite distance
      $currentId = $this->getMinDistanceNode($distances, $unvisited);

      if ($currentId === null || $distances[$currentId] === PHP_FLOAT_MAX) {
        // Aucun chemin trouvé
        return null;
      }

      // Si on a atteint la destination
      if ($currentId === $endId) {
        break;
      }

      // Marquer comme visité
      unset($unvisited[$currentId]);

      // Explorer les voisins
      if (!isset($graph[$currentId])) {
        continue;
      }

      foreach ($graph[$currentId] as $neighborId => $edgeDistance) {
        if (!isset($unvisited[$neighborId])) {
          continue; // Déjà visité
        }

        // Calculer la nouvelle distance
        $newDistance = $distances[$currentId] + $edgeDistance;

        // Si on a trouvé un chemin plus court
        if ($newDistance < $distances[$neighborId]) {
          $distances[$neighborId] = $newDistance;
          $previous[$neighborId] = $currentId;
        }
      }
    }

    // Si la destination n'est pas atteignable
    if ($distances[$endId] === PHP_FLOAT_MAX) {
      return null;
    }

    // Reconstruire le chemin
    $path = [];
    $currentId = $endId;

    while ($currentId !== null) {
      array_unshift($path, $currentId);
      $currentId = $previous[$currentId];
    }

    return [
      'path' => $path,
      'distance' => $distances[$endId],
    ];
  }

  /**
   * Trouver le nœud non visité avec la distance minimale
   *
   * @param array<int, float> $distances
   * @param array<int, bool> $unvisited
   */
  private function getMinDistanceNode(array $distances, array $unvisited): ?int
  {
    $minDistance = PHP_FLOAT_MAX;
    $minNode = null;

    foreach (array_keys($unvisited) as $nodeId) {
      if ($distances[$nodeId] < $minDistance) {
        $minDistance = $distances[$nodeId];
        $minNode = $nodeId;
      }
    }

    return $minNode;
  }

  /**
   * Reconstruire le chemin complet avec stations et segments
   *
   * @param int[] $pathIds
   * @param NetworkSegment[] $allSegments
   * @return array{path: Station[], distance: float, segments: NetworkSegment[]}
   */
  private function reconstructPath(array $pathIds, float $totalDistance, array $allSegments): array
  {
    // Récupérer les stations dans l'ordre
    $stations = [];
    foreach ($pathIds as $stationId) {
      $station = $this->stationRepository->find($stationId);
      if ($station !== null) {
        $stations[] = $station;
      }
    }

    // Récupérer les segments utilisés
    $usedSegments = [];
    for ($i = 0; $i < count($pathIds) - 1; $i++) {
      $fromId = $pathIds[$i];
      $toId = $pathIds[$i + 1];

      // Trouver le segment correspondant
      foreach ($allSegments as $segment) {
        if ($segment->getParentStation()->getId() === $fromId
          && $segment->getChildStation()->getId() === $toId) {
          $usedSegments[] = $segment;
          break;
        }
      }
    }

    return [
      'path' => $stations,
      'distance' => $totalDistance,
      'segments' => $usedSegments,
    ];
  }

  /**
   * Obtenir le chemin sous forme de codes de stations
   *
   * @param Station[] $stations
   * @return string[]
   */
  public function getPathCodes(array $stations): array
  {
    return array_map(
      fn(Station $station) => $station->getShortName(),
      $stations
    );
  }
}

// ============================================
// Exception personnalisée
// ============================================

class RoutingException extends \Exception
{
}
