<?php

namespace App\Repository;

use App\Entity\Quiz;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.user = :user')
            ->setParameter('user', $user)
            ->orderBy('q.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedByUser(User $user): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.user = :user')
            ->andWhere('q.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Quiz::STATUS_COMPLETED)
            ->orderBy('q.finishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findInProgressByUser(User $user): ?Quiz
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.user = :user')
            ->andWhere('q.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Quiz::STATUS_IN_PROGRESS)
            ->orderBy('q.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countPassedByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.user = :user')
            ->andWhere('q.isPassed = :passed')
            ->setParameter('user', $user)
            ->setParameter('passed', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageScoreByUser(User $user): float
    {
        $result = $this->createQueryBuilder('q')
            ->select('AVG(q.scorePercent) as avgScore')
            ->andWhere('q.user = :user')
            ->andWhere('q.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Quiz::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? round((float) $result, 2) : 0.0;
    }

    public function getScoreHistoryByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('q')
            ->select('q.finishedAt', 'q.scorePercent', 'q.isPassed')
            ->andWhere('q.user = :user')
            ->andWhere('q.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Quiz::STATUS_COMPLETED)
            ->orderBy('q.finishedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getStatsByUser(User $user): array
    {
        $stats = $this->createQueryBuilder('q')
            ->select(
                'COUNT(q.id) as totalQuizzes',
                'SUM(CASE WHEN q.isPassed = true THEN 1 ELSE 0 END) as passedQuizzes',
                'AVG(q.scorePercent) as averageScore',
                'MAX(q.scorePercent) as bestScore',
                'MIN(q.scorePercent) as worstScore'
            )
            ->andWhere('q.user = :user')
            ->andWhere('q.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Quiz::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleResult();

        return [
            'totalQuizzes' => (int) ($stats['totalQuizzes'] ?? 0),
            'passedQuizzes' => (int) ($stats['passedQuizzes'] ?? 0),
            'averageScore' => round((float) ($stats['averageScore'] ?? 0), 2),
            'bestScore' => round((float) ($stats['bestScore'] ?? 0), 2),
            'worstScore' => round((float) ($stats['worstScore'] ?? 0), 2),
        ];
    }

    public function countTotalQuizzes(): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.status = :status')
            ->setParameter('status', Quiz::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getGlobalStats(): array
    {
        $stats = $this->createQueryBuilder('q')
            ->select(
                'COUNT(q.id) as totalQuizzes',
                'SUM(CASE WHEN q.isPassed = true THEN 1 ELSE 0 END) as passedQuizzes',
                'AVG(q.scorePercent) as averageScore'
            )
            ->andWhere('q.status = :status')
            ->setParameter('status', Quiz::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleResult();

        $totalQuizzes = (int) ($stats['totalQuizzes'] ?? 0);
        $passedQuizzes = (int) ($stats['passedQuizzes'] ?? 0);

        return [
            'totalQuizzes' => $totalQuizzes,
            'passedQuizzes' => $passedQuizzes,
            'failedQuizzes' => $totalQuizzes - $passedQuizzes,
            'averageScore' => round((float) ($stats['averageScore'] ?? 0), 2),
            'successRate' => $totalQuizzes > 0 ? round(($passedQuizzes / $totalQuizzes) * 100, 2) : 0,
        ];
    }
}