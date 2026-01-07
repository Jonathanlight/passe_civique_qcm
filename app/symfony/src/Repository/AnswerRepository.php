<?php

namespace App\Repository;

use App\Entity\Answer;
use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Answer>
 */
class AnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Answer::class);
    }

    public function findByQuestion(Question $question): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.question = :question')
            ->setParameter('question', $question)
            ->orderBy('a.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCorrectAnswers(Question $question): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.question = :question')
            ->andWhere('a.isCorrect = :correct')
            ->setParameter('question', $question)
            ->setParameter('correct', true)
            ->getQuery()
            ->getResult();
    }
}