<?php

namespace App\Repository;

use App\Entity\GamificationLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GamificationLevel>
 */
class GamificationLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GamificationLevel::class);
    }

    public function findActiveLevels(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('g.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLevelForUser(int $quizzesPassed, float $averageScore): ?GamificationLevel
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.isActive = :active')
            ->andWhere('g.minQuizzesPassed <= :quizzes')
            ->andWhere('g.minAverageScore <= :score')
            ->setParameter('active', true)
            ->setParameter('quizzes', $quizzesPassed)
            ->setParameter('score', $averageScore)
            ->orderBy('g.displayOrder', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLowestLevel(): ?GamificationLevel
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('g.displayOrder', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNextLevel(GamificationLevel $currentLevel): ?GamificationLevel
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.isActive = :active')
            ->andWhere('g.displayOrder > :currentOrder')
            ->setParameter('active', true)
            ->setParameter('currentOrder', $currentLevel->getDisplayOrder())
            ->orderBy('g.displayOrder', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}