<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

// ============================================
// Network Entity
// ============================================
#[ORM\Entity]
#[ORM\Table(name: 'networks')]
class Network
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: Types::INTEGER)]
private ?int $id = null;

#[ORM\Column(type: Types::STRING, length: 50, unique: true)]
private string $name;

#[ORM\OneToMany(targetEntity: NetworkSegment::class, mappedBy: 'network')]
private Collection $segments;

public function __construct(string $name)
{
$this->name = $name;
$this->segments = new ArrayCollection();
}

public function getId(): ?int
{
return $this->id;
}

public function getName(): string
{
return $this->name;
}

public function getSegments(): Collection
{
return $this->segments;
}
}
