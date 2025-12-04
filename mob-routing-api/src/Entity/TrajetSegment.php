<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

// ============================================
// TrajetSegment Entity
// ============================================
#[ORM\Entity]
#[ORM\Table(name: 'trajet_segments')]
#[ORM\Index(columns: ['trajet_id', 'sequence_order'], name: 'idx_trajet_sequence')]
class TrajetSegment
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: Types::INTEGER)]
private ?int $id = null;

#[ORM\ManyToOne(targetEntity: Trajet::class, inversedBy: 'trajetSegments')]
#[ORM\JoinColumn(nullable: false)]
private Trajet $trajet;

#[ORM\ManyToOne(targetEntity: NetworkSegment::class)]
#[ORM\JoinColumn(nullable: false)]
private NetworkSegment $networkSegment;

#[ORM\Column(type: Types::INTEGER)]
private int $sequenceOrder;

public function __construct(
Trajet $trajet,
NetworkSegment $networkSegment,
int $sequenceOrder
) {
$this->trajet = $trajet;
$this->networkSegment = $networkSegment;
$this->sequenceOrder = $sequenceOrder;
}

public function getId(): ?int
{
return $this->id;
}

public function getTrajet(): Trajet
{
return $this->trajet;
}

public function getNetworkSegment(): NetworkSegment
{
return $this->networkSegment;
}

public function getSequenceOrder(): int
{
return $this->sequenceOrder;
}
}
