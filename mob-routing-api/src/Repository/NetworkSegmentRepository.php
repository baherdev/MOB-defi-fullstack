<?php

declare(strict_types=1);
// ============================================
// NetworkSegmentRepository
// ============================================

namespace App\Repository;

use App\Entity\NetworkSegment;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
* @extends ServiceEntityRepository<NetworkSegment>
  */
  class NetworkSegmentRepository extends ServiceEntityRepository
  {
  public function __construct(ManagerRegistry $registry)
  {
  parent::__construct($registry, NetworkSegment::class);
  }

  /**
  * Trouver tous les segments connectés à une station
  *
  * @return NetworkSegment[]
  */
  public function findByStation(Station $station): array
  {
  return $this->createQueryBuilder('ns')
  ->where('ns.parentStation = :station')
  ->orWhere('ns.childStation = :station')
  ->setParameter('station', $station)
  ->getQuery()
  ->getResult();
  }

  /**
  * Trouver un segment spécifique entre deux stations
  */
  public function findSegmentBetween(Station $from, Station $to): ?NetworkSegment
  {
  return $this->createQueryBuilder('ns')
  ->where('ns.parentStation = :from')
  ->andWhere('ns.childStation = :to')
  ->setParameter('from', $from)
  ->setParameter('to', $to)
  ->getQuery()
  ->getOneOrNullResult();
  }

  /**
  * Récupérer tous les segments avec leurs stations (optimisé)
  *
  * @return NetworkSegment[]
  */
  public function findAllWithStations(): array
  {
  return $this->createQueryBuilder('ns')
  ->select('ns', 'parent', 'child', 'network')
  ->join('ns.parentStation', 'parent')
  ->join('ns.childStation', 'child')
  ->join('ns.network', 'network')
  ->getQuery()
  ->getResult();
  }

  /**
  * Récupérer les segments par réseau
  *
  * @return NetworkSegment[]
  */
  public function findByNetworkName(string $networkName): array
  {
  return $this->createQueryBuilder('ns')
  ->join('ns.network', 'n')
  ->where('n.name = :name')
  ->setParameter('name', $networkName)
  ->getQuery()
  ->getResult();
  }
  }
