<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

// ============================================
// RouteResponse DTO
// ============================================
class RouteResponse
{
public function __construct(
public readonly string $id,
public readonly string $fromStationId,
public readonly string $toStationId,
public readonly string $analyticCode,
public readonly float $distanceKm,
public readonly array $path,
public readonly string $createdAt,
) {
}

public function toArray(): array
{
return [
'id' => $this->id,
'fromStationId' => $this->fromStationId,
'toStationId' => $this->toStationId,
'analyticCode' => $this->analyticCode,
'distanceKm' => $this->distanceKm,
'path' => $this->path,
'createdAt' => $this->createdAt,
];
}
}
