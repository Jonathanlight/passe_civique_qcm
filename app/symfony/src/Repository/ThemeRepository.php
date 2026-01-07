<?php

namespace App\Repository;

use App\Entity\Theme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Theme>
 */
class ThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class);
    }

    public function findActiveThemes(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findThemesWithQuestionCount(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t', 'COUNT(q.id) as questionCount')
            ->leftJoin('t.questions', 'q', 'WITH', 'q.isActive = true')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('t.id')
            ->orderBy('t.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}