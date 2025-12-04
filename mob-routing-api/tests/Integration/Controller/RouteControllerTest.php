<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RouteControllerTest extends WebTestCase
{
  public function testCalculateRouteSuccess(): void
  {
    // Arrange
    $client = static::createClient();

    $requestData = [
      'fromStationId' => 'MX',
      'toStationId' => 'GST',
      'analyticCode' => 'PASSAGER',
    ];

    // Act
    $client->request(
      'POST',
      '/api/v1/routes',
      [],
      [],
      ['CONTENT_TYPE' => 'application/json'],
      json_encode($requestData)
    );

    // Assert
    $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    $this->assertResponseHeaderSame('Content-Type', 'application/json');

    $responseData = json_decode($client->getResponse()->getContent(), true);

    $this->assertArrayHasKey('id', $responseData);
    $this->assertArrayHasKey('fromStationId', $responseData);
    $this->assertArrayHasKey('toStationId', $responseData);
    $this->assertArrayHasKey('analyticCode', $responseData);
    $this->assertArrayHasKey('distanceKm', $responseData);
    $this->assertArrayHasKey('path', $responseData);
    $this->assertArrayHasKey('createdAt', $responseData);

    $this->assertEquals('MX', $responseData['fromStationId']);
    $this->assertEquals('GST', $responseData['toStationId']);
    $this->assertEquals('PASSAGER', $responseData['analyticCode']);
    $this->assertIsFloat($responseData['distanceKm']);
    $this->assertIsArray($responseData['path']);
    $this->assertGreaterThan(0, count($responseData['path']));
  }

  public function testCalculateRouteMissingFields(): void
  {
    // Arrange
    $client = static::createClient();

    $requestData = [
      'fromStationId' => 'MX',
      // toStationId manquant
      // analyticCode manquant
    ];

    // Act
    $client->request(
      'POST',
      '/api/v1/routes',
      [],
      [],
      ['CONTENT_TYPE' => 'application/json'],
      json_encode($requestData)
    );

    // Assert
    $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode($client->getResponse()->getContent(), true);

    $this->assertArrayHasKey('message', $responseData);
    $this->assertArrayHasKey('details', $responseData);
    $this->assertIsArray($responseData['details']);
    $this->assertNotEmpty($responseData['details']);
  }

  public function testCalculateRouteSameStations(): void
  {
    // Arrange
    $client = static::createClient();

    $requestData = [
      'fromStationId' => 'MX',
      'toStationId' => 'MX',
      'analyticCode' => 'PASSAGER',
    ];

    // Act
    $client->request(
      'POST',
      '/api/v1/routes',
      [],
      [],
      ['CONTENT_TYPE' => 'application/json'],
      json_encode($requestData)
    );

    // Assert
    $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode($client->getResponse()->getContent(), true);
    $this->assertArrayHasKey('details', $responseData);
    $this->assertNotEmpty($responseData['details']);
  }

  public function testCalculateRouteInvalidStation(): void
  {
    // Arrange
    $client = static::createClient();

    $requestData = [
      'fromStationId' => 'INVALID_STATION',
      'toStationId' => 'GST',
      'analyticCode' => 'PASSAGER',
    ];

    // Act
    $client->request(
      'POST',
      '/api/v1/routes',
      [],
      [],
      ['CONTENT_TYPE' => 'application/json'],
      json_encode($requestData)
    );

    // Assert
    $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

    $responseData = json_decode($client->getResponse()->getContent(), true);
    $this->assertStringContainsString('not found', strtolower($responseData['message']));
  }

  public function testCalculateRouteInvalidAnalyticCode(): void
  {
    // Arrange
    $client = static::createClient();

    $requestData = [
      'fromStationId' => 'MX',
      'toStationId' => 'GST',
      'analyticCode' => 'INVALID_CODE',
    ];

    // Act
    $client->request(
      'POST',
      '/api/v1/routes',
      [],
      [],
      ['CONTENT_TYPE' => 'application/json'],
      json_encode($requestData)
    );

    // Assert
    $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

    $responseData = json_decode($client->getResponse()->getContent(), true);
    $this->assertStringContainsString('not found', strtolower($responseData['message']));
  }

  public function testCalculateRouteInvalidJson(): void
  {
    // Arrange
    $client = static::createClient();

    // Act
    $client->request(
      'POST',
      '/api/v1/routes',
      [],
      [],
      ['CONTENT_TYPE' => 'application/json'],
      'invalid json{{'
    );

    // Assert
    $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
  }
}
