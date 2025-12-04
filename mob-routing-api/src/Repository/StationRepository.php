<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Station>
 */
class StationRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Station::class);
  }

  /**
   * Trouver une station par son code court
   */
  public function findByShortName(string $shortName): ?Station
  {
    return $this->findOneBy(['shortName' => $shortName]);
  }

  /**
   * Récupérer toutes les stations triées par nom
   *
   * @return Station[]
   */
  public function findAllOrdered(): array
  {
    return $this->createQueryBuilder('s')
      ->orderBy('s.longName', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Vérifier si une station existe par son code
   */
  public function existsByShortName(string $shortName): bool
  {
    return $this->count(['shortName' => $shortName]) > 0;
  }
}






