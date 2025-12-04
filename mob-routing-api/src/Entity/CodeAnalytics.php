<?php


namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// ============================================
// CodeAnalytics Entity
// ============================================
#[ORM\Entity]
#[ORM\Table(name: 'code_analytics')]
class CodeAnalytics
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: Types::INTEGER)]
private ?int $id = null;

#[ORM\Column(type: Types::STRING, length: 50, unique: true)]
private string $label;

public function __construct(string $label)
{
$this->label = $label;
}

public function getId(): ?int
{
return $this->id;
}

public function getLabel(): string
{
return $this->label;
}
}
