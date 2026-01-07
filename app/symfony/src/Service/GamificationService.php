<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\GamificationLevel;
use App\Repository\GamificationLevelRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;

class GamificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GamificationLevelRepository $levelRepository,
        private QuizRepository $quizRepository,
    ) {
    }

    public function updateUserLevel(User $user): ?GamificationLevel
    {
        $stats = $this->quizRepository->getStatsByUser($user);

        $newLevel = $this->levelRepository->findLevelForUser(
            $stats['passedQuizzes'],
            $stats['averageScore']
        );

        if ($newLevel && $newLevel !== $user->getGamificationLevel()) {
            $user->setGamificationLevel($newLevel);
            $this->em->flush();
        }

        return $newLevel;
    }

    public function getNextLevel(User $user): ?GamificationLevel
    {
        $currentLevel = $user->getGamificationLevel();
        if (!$currentLevel) {
            return $this->levelRepository->findLowestLevel();
        }

        return $this->levelRepository->findNextLevel($currentLevel);
    }

    public function getProgressToNextLevel(User $user): array
    {
        $currentLevel = $user->getGamificationLevel();
        $nextLevel = $this->getNextLevel($user);

        if (!$nextLevel) {
            return [
                'percentage' => 100,
                'quizzesNeeded' => 0,
                'scoreNeeded' => 0,
                'isMaxLevel' => true,
            ];
        }

        $stats = $this->quizRepository->getStatsByUser($user);

        $quizzesProgress = $nextLevel->getMinQuizzesPassed() > 0
            ? min(100, ($stats['passedQuizzes'] / $nextLevel->getMinQuizzesPassed()) * 100)
            : 100;

        $scoreProgress = $nextLevel->getMinAverageScore() > 0
            ? min(100, ($stats['averageScore'] / $nextLevel->getMinAverageScore()) * 100)
            : 100;

        return [
            'percentage' => ($quizzesProgress + $scoreProgress) / 2,
            'quizzesNeeded' => max(0, $nextLevel->getMinQuizzesPassed() - $stats['passedQuizzes']),
            'scoreNeeded' => max(0, $nextLevel->getMinAverageScore() - $stats['averageScore']),
            'isMaxLevel' => false,
        ];
    }

    public function getAllLevels(): array
    {
        return $this->levelRepository->findActiveLevels();
    }
}
