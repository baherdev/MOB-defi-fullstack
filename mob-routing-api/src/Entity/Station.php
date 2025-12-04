<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

// ============================================
// Station Entity
// ============================================
#[ORM\Entity]
#[ORM\Table(name: 'stations')]
#[ORM\Index(columns: ['short_name'], name: 'idx_station_short_name')]
class Station
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column(type: Types::INTEGER)]
  private ?int $id = null;

  #[ORM\Column(type: Types::STRING, length: 10, unique: true)]
  private string $shortName;

  #[ORM\Column(type: Types::STRING, length: 100)]
  private string $longName;

  public function __construct(string $shortName, string $longName)
  {
    $this->shortName = $shortName;
    $this->longName = $longName;
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getShortName(): string
  {
    return $this->shortName;
  }

  public function getLongName(): string
  {
    return $this->longName;
  }
}
