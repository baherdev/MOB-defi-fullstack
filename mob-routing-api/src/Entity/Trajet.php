<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

// ============================================
// Trajet Entity
// ============================================
#[ORM\Entity]
#[ORM\Table(name: 'trajets')]
#[ORM\Index(columns: ['created_at'], name: 'idx_trajet_created_at')]
#[ORM\Index(columns: ['code_analytics_id'], name: 'idx_trajet_code_analytics')]
class Trajet
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: Types::INTEGER)]
private ?int $id = null;

#[ORM\ManyToOne(targetEntity: Train::class)]
#[ORM\JoinColumn(nullable: false)]
private Train $train;

#[ORM\ManyToOne(targetEntity: Station::class)]
#[ORM\JoinColumn(nullable: false)]
private Station $stationDep;

#[ORM\ManyToOne(targetEntity: Station::class)]
#[ORM\JoinColumn(nullable: false)]
private Station $stationArriv;

#[ORM\ManyToOne(targetEntity: CodeAnalytics::class)]
#[ORM\JoinColumn(nullable: false)]
private CodeAnalytics $codeAnalytics;

#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
private string $distanceKmTotal;

#[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
private \DateTimeImmutable $createdAt;

#[ORM\OneToMany(targetEntity: TrajetSegment::class, mappedBy: 'trajet', cascade: ['persist'])]
#[ORM\OrderBy(['sequenceOrder' => 'ASC'])]
private Collection $trajetSegments;

public function __construct(
Train $train,
Station $stationDep,
Station $stationArriv,
CodeAnalytics $codeAnalytics
) {
$this->train = $train;
$this->stationDep = $stationDep;
$this->stationArriv = $stationArriv;
$this->codeAnalytics = $codeAnalytics;
$this->distanceKmTotal = '0.00';
$this->createdAt = new \DateTimeImmutable();
$this->trajetSegments = new ArrayCollection();
}

public function getId(): ?int
{
return $this->id;
}

public function getTrain(): Train
{
return $this->train;
}

public function getStationDep(): Station
{
return $this->stationDep;
}

public function getStationArriv(): Station
{
return $this->stationArriv;
}

public function getCodeAnalytics(): CodeAnalytics
{
return $this->codeAnalytics;
}

public function getDistanceKmTotal(): float
{
return (float) $this->distanceKmTotal;
}

public function setDistanceKmTotal(float $distance): self
{
$this->distanceKmTotal = (string) $distance;
return $this;
}

public function getCreatedAt(): \DateTimeImmutable
{
return $this->createdAt;
}

public function getTrajetSegments(): Collection
{
return $this->trajetSegments;
}

public function addTrajetSegment(TrajetSegment $segment): self
{
if (!$this->trajetSegments->contains($segment)) {
$this->trajetSegments->add($segment);
}
return $this;
}
}
