<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Station;
use App\Entity\Network;
use App\Entity\NetworkSegment;
use App\Repository\StationRepository;
use App\Repository\NetworkSegmentRepository;
use App\Service\RoutingService;
use App\Service\RoutingException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class RoutingServiceTest extends TestCase
{
  private RoutingService $routingService;
  private StationRepository|MockObject $stationRepository;
  private NetworkSegmentRepository|MockObject $segmentRepository;

  protected function setUp(): void
  {
    $this->stationRepository = $this->createMock(StationRepository::class);
    $this->segmentRepository = $this->createMock(NetworkSegmentRepository::class);

    $this->routingService = new RoutingService(
      $this->stationRepository,
      $this->segmentRepository
    );
  }

  public function testFindShortestPathSuccess(): void
  {
    // Arrange - Créer des stations de test
    $stationA = $this->createStation(1, 'A', 'Station A');
    $stationB = $this->createStation(2, 'B', 'Station B');
    $stationC = $this->createStation(3, 'C', 'Station C');

    // Créer des segments bidirectionnels (comme dans la vraie implémentation)
    $network = $this->createNetwork(1, 'TEST');
    $segments = [
      $this->createSegment(1, $stationA, $stationB, 10.0, $network),
      $this->createSegment(2, $stationB, $stationA, 10.0, $network), // Bidirectionnel
      $this->createSegment(3, $stationB, $stationC, 15.0, $network),
      $this->createSegment(4, $stationC, $stationB, 15.0, $network), // Bidirectionnel
      $this->createSegment(5, $stationA, $stationC, 30.0, $network),
      $this->createSegment(6, $stationC, $stationA, 30.0, $network), // Bidirectionnel
    ];

    // Mock findOneBy pour trouver les stations de départ et d'arrivée
    $this->stationRepository
      ->expects($this->exactly(2))
      ->method('findOneBy')
      ->willReturnCallback(function ($criteria) use ($stationA, $stationC) {
        if ($criteria['shortName'] === 'A') {
          return $stationA;
        }
        if ($criteria['shortName'] === 'C') {
          return $stationC;
        }
        return null;
      });

    // Mock find() pour reconstructPath (appelé pour chaque station du path)
    $this->stationRepository
      ->expects($this->exactly(3)) // 3 stations dans le path : A, B, C
      ->method('find')
      ->willReturnCallback(function ($id) use ($stationA, $stationB, $stationC) {
        if ($id === 1) return $stationA;
        if ($id === 2) return $stationB;
        if ($id === 3) return $stationC;
        return null;
      });

    $this->segmentRepository
      ->expects($this->once())
      ->method('findAll')
      ->willReturn($segments);

    // Act
    $result = $this->routingService->findShortestPath('A', 'C');

    // Assert
    $this->assertIsArray($result);
    $this->assertArrayHasKey('path', $result);
    $this->assertArrayHasKey('distance', $result);
    $this->assertArrayHasKey('segments', $result);

    // Le chemin optimal devrait être A → B → C (25km) au lieu de A → C (30km)
    $this->assertCount(3, $result['path']); // A, B, C
    $this->assertEquals(25.0, $result['distance']);
    $this->assertCount(2, $result['segments']); // 2 segments
  }

  public function testFindShortestPathStationNotFound(): void
  {
    // Arrange - Le service appelle findOneBy DEUX FOIS (ligne 32 et 33) avant de vérifier
    // Le premier retourne null (INVALID), le deuxième retourne une station valide (B)
    $stationB = $this->createStation(2, 'B', 'Station B');

    $this->stationRepository
      ->expects($this->exactly(2))
      ->method('findOneBy')
      ->willReturnCallback(function ($criteria) use ($stationB) {
        if ($criteria['shortName'] === 'INVALID') {
          return null;
        }
        if ($criteria['shortName'] === 'B') {
          return $stationB;
        }
        return null;
      });

    // Assert
    $this->expectException(RoutingException::class);
    $this->expectExceptionMessage('Station not found: INVALID');

    // Act
    $this->routingService->findShortestPath('INVALID', 'B');
  }

  public function testFindShortestPathSameStation(): void
  {
    // Arrange
    $station = $this->createStation(1, 'A', 'Station A');

    $this->stationRepository
      ->expects($this->exactly(2))
      ->method('findOneBy')
      ->willReturn($station);

    // Assert
    $this->expectException(RoutingException::class);
    $this->expectExceptionMessage('Start and end stations must be different');

    // Act
    $this->routingService->findShortestPath('A', 'A');
  }

  public function testFindShortestPathNoRoute(): void
  {
    // Arrange - Stations isolées (pas de segments)
    $stationA = $this->createStation(1, 'A', 'Station A');
    $stationB = $this->createStation(2, 'B', 'Station B');

    $this->stationRepository
      ->expects($this->exactly(2))
      ->method('findOneBy')
      ->willReturnCallback(function ($criteria) use ($stationA, $stationB) {
        if ($criteria['shortName'] === 'A') {
          return $stationA;
        }
        if ($criteria['shortName'] === 'B') {
          return $stationB;
        }
        return null;
      });

    $this->segmentRepository
      ->expects($this->once())
      ->method('findAll')
      ->willReturn([]); // Aucun segment

    // Assert
    $this->expectException(RoutingException::class);
    // Le message réel est "No network segments available" quand il n'y a pas de segments
    $this->expectExceptionMessage('No network segments available');

    // Act
    $this->routingService->findShortestPath('A', 'B');
  }

  public function testGetPathCodes(): void
  {
    // Arrange
    $stations = [
      $this->createStation(1, 'MX', 'Montreux'),
      $this->createStation(2, 'GST', 'Gstaad'),
      $this->createStation(3, 'ZW', 'Zweisimmen'),
    ];

    // Act
    $codes = $this->routingService->getPathCodes($stations);

    // Assert
    $this->assertEquals(['MX', 'GST', 'ZW'], $codes);
  }

  // Helpers pour créer des objets de test
  private function createStation(int $id, string $shortName, string $longName): Station
  {
    $station = new Station($shortName, $longName);

    // Utiliser la réflexion pour définir l'ID (car c'est auto-généré normalement)
    $reflection = new \ReflectionClass($station);
    $property = $reflection->getProperty('id');
    $property->setAccessible(true);
    $property->setValue($station, $id);

    return $station;
  }

  private function createNetwork(int $id, string $name): Network
  {
    $network = new Network($name);

    $reflection = new \ReflectionClass($network);
    $property = $reflection->getProperty('id');
    $property->setAccessible(true);
    $property->setValue($network, $id);

    return $network;
  }

  private function createSegment(
    int $id,
    Station $parent,
    Station $child,
    float $distance,
    Network $network
  ): NetworkSegment {
    $segment = new NetworkSegment($parent, $child, $distance, $network);

    $reflection = new \ReflectionClass($segment);
    $property = $reflection->getProperty('id');
    $property->setAccessible(true);
    $property->setValue($segment, $id);

    return $segment;
  }
}
