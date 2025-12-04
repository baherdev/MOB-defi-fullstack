<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

// ============================================
// AnalyticDistance DTO
// ============================================
class AnalyticDistance
{
public function __construct(
public readonly string $analyticCode,
public readonly float $totalDistanceKm,
public readonly ?string $periodStart = null,
public readonly ?string $periodEnd = null,
public readonly ?string $group = null,
) {
}

public function toArray(): array
{
$data = [
'analyticCode' => $this->analyticCode,
'totalDistanceKm' => $this->totalDistanceKm,
];

if ($this->periodStart !== null) {
$data['periodStart'] = $this->periodStart;
}

if ($this->periodEnd !== null) {
$data['periodEnd'] = $this->periodEnd;
}

if ($this->group !== null) {
$data['group'] = $this->group;
}

return $data;
}
}
