<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

// ============================================
// Train Entity
// ============================================
#[ORM\Entity]
#[ORM\Table(name: 'trains')]
class Train
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: Types::INTEGER)]
private ?int $id = null;

#[ORM\Column(type: Types::STRING, length: 50)]
private string $trainLabel;

public function __construct(string $trainLabel)
{
$this->trainLabel = $trainLabel;
}

public function getId(): ?int
{
return $this->id;
}

public function getTrainLabel(): string
{
return $this->trainLabel;
}
}
