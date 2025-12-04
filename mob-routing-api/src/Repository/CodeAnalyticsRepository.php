<?php

declare(strict_types=1);
// ============================================
// CodeAnalyticsRepository
// ============================================

namespace App\Repository;

use App\Entity\CodeAnalytics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
* @extends ServiceEntityRepository<CodeAnalytics>
  */
  class CodeAnalyticsRepository extends ServiceEntityRepository
  {
  public function __construct(ManagerRegistry $registry)
  {
  parent::__construct($registry, CodeAnalytics::class);
  }

  /**
  * Trouver par label
  */
  public function findByLabel(string $label): ?CodeAnalytics
  {
  return $this->findOneBy(['label' => $label]);
  }

  /**
  * Vérifier l'existence d'un code
  */
  public function existsByLabel(string $label): bool
  {
  return $this->count(['label' => $label]) > 0;
  }
  }
