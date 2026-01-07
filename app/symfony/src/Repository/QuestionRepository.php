<?php

namespace App\Repository;

use App\Entity\Question;
use App\Entity\Theme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function findActiveQuestions(): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function findByTheme(Theme $theme): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.theme = :theme')
            ->andWhere('q.isActive = :active')
            ->setParameter('theme', $theme)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function findRandomQuestions(int $count, ?array $themeIds = null): array
    {
        $qb = $this->createQueryBuilder('q')
            ->andWhere('q.isActive = :active')
            ->setParameter('active', true);

        if ($themeIds !== null && count($themeIds) > 0) {
            $qb->andWhere('q.theme IN (:themes)')
               ->setParameter('themes', $themeIds);
        }

        $questions = $qb->getQuery()->getResult();

        shuffle($questions);

        return array_slice($questions, 0, $count);
    }

    public function findByDifficulty(string $difficulty): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.difficulty = :difficulty')
            ->andWhere('q.isActive = :active')
            ->setParameter('difficulty', $difficulty)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function countActiveQuestions(): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByTheme(): array
    {
        return $this->createQueryBuilder('q')
            ->select('t.name as themeName', 'COUNT(q.id) as questionCount')
            ->join('q.theme', 't')
            ->andWhere('q.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('t.id')
            ->getQuery()
            ->getResult();
    }
}