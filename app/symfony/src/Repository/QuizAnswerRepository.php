<?php

namespace App\Repository;

use App\Entity\Quiz;
use App\Entity\QuizAnswer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizAnswer>
 */
class QuizAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizAnswer::class);
    }

    public function findByQuiz(Quiz $quiz): array
    {
        return $this->createQueryBuilder('qa')
            ->andWhere('qa.quiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->orderBy('qa.answeredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFailedByQuiz(Quiz $quiz): array
    {
        return $this->createQueryBuilder('qa')
            ->andWhere('qa.quiz = :quiz')
            ->andWhere('qa.isCorrect = :correct')
            ->setParameter('quiz', $quiz)
            ->setParameter('correct', false)
            ->getQuery()
            ->getResult();
    }

    public function findMostFailedQuestionsByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('qa')
            ->select('q.id', 'q.content', 'COUNT(qa.id) as failCount')
            ->join('qa.question', 'q')
            ->join('qa.quiz', 'qz')
            ->andWhere('qz.user = :user')
            ->andWhere('qa.isCorrect = :correct')
            ->setParameter('user', $user)
            ->setParameter('correct', false)
            ->groupBy('q.id')
            ->orderBy('failCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getStatsByThemeForUser(User $user): array
    {
        return $this->createQueryBuilder('qa')
            ->select(
                't.name as themeName',
                't.color as themeColor',
                'COUNT(qa.id) as totalAnswers',
                'SUM(CASE WHEN qa.isCorrect = true THEN 1 ELSE 0 END) as correctAnswers'
            )
            ->join('qa.question', 'q')
            ->join('q.theme', 't')
            ->join('qa.quiz', 'qz')
            ->andWhere('qz.user = :user')
            ->andWhere('qz.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Quiz::STATUS_COMPLETED)
            ->groupBy('t.id')
            ->getQuery()
            ->getResult();
    }
}