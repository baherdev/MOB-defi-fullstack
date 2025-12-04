<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

// ============================================
// AnalyticDistanceList DTO
// ============================================
class AnalyticDistanceList
{
/**
* @param AnalyticDistance[] $items
*/
public function __construct(
public readonly array $items,
public readonly ?string $from = null,
public readonly ?string $to = null,
public readonly string $groupBy = 'none',
) {
}

public function toArray(): array
{
return [
'from' => $this->from,
'to' => $this->to,
'groupBy' => $this->groupBy,
'items' => array_map(fn($item) => $item->toArray(), $this->items),
];
}
}
