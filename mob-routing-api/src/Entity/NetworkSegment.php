<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


// ============================================
// NetworkSegment Entity
// ============================================
#[ORM\Entity]
#[ORM\Table(name: 'network_segments')]
#[ORM\Index(columns: ['parent_station_id', 'child_station_id'], name: 'idx_segment_stations')]
class NetworkSegment
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: Types::INTEGER)]
private ?int $id = null;

#[ORM\ManyToOne(targetEntity: Station::class)]
#[ORM\JoinColumn(nullable: false)]
private Station $parentStation;

#[ORM\ManyToOne(targetEntity: Station::class)]
#[ORM\JoinColumn(nullable: false)]
private Station $childStation;

#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
private string $distanceKm;

#[ORM\ManyToOne(targetEntity: Network::class, inversedBy: 'segments')]
#[ORM\JoinColumn(nullable: false)]
private Network $network;

public function __construct(
Station $parentStation,
Station $childStation,
float $distanceKm,
Network $network
) {
$this->parentStation = $parentStation;
$this->childStation = $childStation;
$this->distanceKm = (string) $distanceKm;
$this->network = $network;
}

public function getId(): ?int
{
return $this->id;
}

public function getParentStation(): Station
{
return $this->parentStation;
}

public function getChildStation(): Station
{
return $this->childStation;
}

public function getDistanceKm(): float
{
return (float) $this->distanceKm;
}

public function getNetwork(): Network
{
return $this->network;
}
}
