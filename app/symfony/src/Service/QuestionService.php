<?php

namespace App\Service;

use App\Entity\Question;
use App\Entity\Theme;
use App\Enum\Difficulty;
use App\Repository\QuestionRepository;
use App\Repository\ThemeRepository;

class QuestionService
{
    public function __construct(
        private QuestionRepository $questionRepository,
        private ThemeRepository $themeRepository,
    ) {
    }

    public function getRandomQuestions(
        int $count,
        ?array $themeIds = null,
        ?Difficulty $difficulty = null
    ): array {
        $questions = $this->questionRepository->findRandomQuestions($count, $themeIds);

        if ($difficulty !== null) {
            $questions = array_filter(
                $questions,
                fn (Question $q) => $q->getDifficulty() === $difficulty->value
            );
        }

        return array_slice($questions, 0, $count);
    }

    public function getQuestionsByTheme(Theme $theme): array
    {
        return $this->questionRepository->findByTheme($theme);
    }

    public function getQuestionStatistics(): array
    {
        return [
            'total' => $this->questionRepository->countActiveQuestions(),
            'byTheme' => $this->questionRepository->countByTheme(),
        ];
    }

    public function getAllThemes(): array
    {
        return $this->themeRepository->findBy(['isActive' => true], ['displayOrder' => 'ASC']);
    }

    public function getQuestionById(int $id): ?Question
    {
        return $this->questionRepository->find($id);
    }
}
