<?php

declare(strict_types=1);
// ============================================
// TrajetRepository
// ============================================

namespace App\Repository;

use App\Entity\Trajet;
use App\Entity\CodeAnalytics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
* @extends ServiceEntityRepository<Trajet>
  */
  class TrajetRepository extends ServiceEntityRepository
  {
  public function __construct(ManagerRegistry $registry)
  {
  parent::__construct($registry, Trajet::class);
  }

  /**
  * Sauvegarder un trajet
  */
  public function save(Trajet $trajet, bool $flush = true): void
  {
  $this->getEntityManager()->persist($trajet);

  if ($flush) {
  $this->getEntityManager()->flush();
  }
  }

  /**
  * Récupérer les statistiques par code analytique
  *
  * @return array{analyticCode: string, totalDistanceKm: float, nbTrajets: int}[]
  */
  public function getStatsByAnalyticCode(
  ?\DateTimeInterface $from = null,
  ?\DateTimeInterface $to = null,
  string $groupBy = 'none'
  ): array {
  $qb = $this->createQueryBuilder('t')
  ->select(
  'ca.label as analyticCode',
  'SUM(t.distanceKmTotal) as totalDistanceKm',
  'COUNT(t.id) as nbTrajets'
  )
  ->join('t.codeAnalytics', 'ca');

  // Filtrer par période
  if ($from !== null) {
  $qb->andWhere('t.createdAt >= :from')
  ->setParameter('from', $from);
  }

  if ($to !== null) {
  $qb->andWhere('t.createdAt <= :to')
  ->setParameter('to', $to);
  }

  // Groupement
  switch ($groupBy) {
  case 'day':
  $qb->addSelect("DATE_FORMAT(t.createdAt, '%Y-%m-%d') as groupKey")
  ->addGroupBy('groupKey');
  break;
  case 'month':
  $qb->addSelect("DATE_FORMAT(t.createdAt, '%Y-%m') as groupKey")
  ->addGroupBy('groupKey');
  break;
  case 'year':
  $qb->addSelect("DATE_FORMAT(t.createdAt, '%Y') as groupKey")
  ->addGroupBy('groupKey');
  break;
  default:
  // Pas de groupement temporel
  break;
  }

  $qb->addGroupBy('ca.id')
  ->orderBy('totalDistanceKm', 'DESC');

  return $qb->getQuery()->getResult();
  }

  /**
  * Récupérer les trajets récents
  *
  * @return Trajet[]
  */
  public function findRecent(int $limit = 10): array
  {
  return $this->createQueryBuilder('t')
  ->orderBy('t.createdAt', 'DESC')
  ->setMaxResults($limit)
  ->getQuery()
  ->getResult();
  }

  /**
  * Récupérer les trajets par code analytique
  *
  * @return Trajet[]
  */
  public function findByAnalyticCode(CodeAnalytics $codeAnalytics): array
  {
  return $this->createQueryBuilder('t')
  ->where('t.codeAnalytics = :code')
  ->setParameter('code', $codeAnalytics)
  ->orderBy('t.createdAt', 'DESC')
  ->getQuery()
  ->getResult();
  }

  /**
  * Calculer la distance totale parcourue
  */
  public function getTotalDistance(
  ?\DateTimeInterface $from = null,
  ?\DateTimeInterface $to = null
  ): float {
  $qb = $this->createQueryBuilder('t')
  ->select('SUM(t.distanceKmTotal)');

  if ($from !== null) {
  $qb->andWhere('t.createdAt >= :from')
  ->setParameter('from', $from);
  }

  if ($to !== null) {
  $qb->andWhere('t.createdAt <= :to')
  ->setParameter('to', $to);
  }

  return (float) $qb->getQuery()->getSingleScalarResult();
  }
  }
