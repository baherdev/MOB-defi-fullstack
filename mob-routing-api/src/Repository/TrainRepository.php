<?php

declare(strict_types=1);
// ============================================
// TrainRepository
// ============================================

namespace App\Repository;

use App\Entity\Train;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
* @extends ServiceEntityRepository<Train>
  */
  class TrainRepository extends ServiceEntityRepository
  {
  public function __construct(ManagerRegistry $registry)
  {
  parent::__construct($registry, Train::class);
  }

  /**
  * Trouver par label
  */
  public function findByLabel(string $label): ?Train
  {
  return $this->findOneBy(['trainLabel' => $label]);
  }
  }
