<?php

namespace App\Repository;

use App\Entity\QuizConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizConfiguration>
 */
class QuizConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizConfiguration::class);
    }

    public function findDefaultConfiguration(): ?QuizConfiguration
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isDefault = :default')
            ->andWhere('c.isActive = :active')
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveConfigurations(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}