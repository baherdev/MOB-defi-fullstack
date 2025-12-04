<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

// ============================================
// RouteRequest DTO
// ============================================
class RouteRequest
{
  #[Assert\NotBlank(message: 'fromStationId is required')]
  #[Assert\Type('string')]
  public string $fromStationId;

  #[Assert\NotBlank(message: 'toStationId is required')]
  #[Assert\Type('string')]
  public string $toStationId;

  #[Assert\NotBlank(message: 'analyticCode is required')]
  #[Assert\Type('string')]
  public string $analyticCode;

  public function __construct(array $data)
  {
    $this->fromStationId = $data['fromStationId'] ?? '';
    $this->toStationId = $data['toStationId'] ?? '';
    $this->analyticCode = $data['analyticCode'] ?? '';
  }
}






