<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Entity\QuizAnswer;
use App\Entity\User;
use App\Entity\Question;
use App\Entity\QuizConfiguration;
use App\Enum\QuizStatus;
use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use App\Repository\QuizConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuizService
{
    public function __construct(
        private EntityManagerInterface $em,
        private QuizRepository $quizRepository,
        private QuestionRepository $questionRepository,
        private QuizConfigurationRepository $configRepository,
        private GamificationService $gamificationService,
    ) {
    }

    public function createQuiz(User $user, ?QuizConfiguration $config = null): Quiz
    {
        $existingQuiz = $this->quizRepository->findInProgressByUser($user);
        if ($existingQuiz) {
            return $existingQuiz;
        }

        $config ??= $this->configRepository->findOneBy(['isDefault' => true, 'isActive' => true]);

        $quiz = new Quiz();
        $quiz->setUser($user);
        $quiz->setConfiguration($config);
        $quiz->setTotalQuestions($config->getQuestionsCount());

        $this->em->persist($quiz);
        $this->em->flush();

        return $quiz;
    }

    public function getQuestionsForQuiz(Quiz $quiz): array
    {
        $config = $quiz->getConfiguration();

        return $this->questionRepository->findRandomQuestions(
            $config->getQuestionsCount()
        );
    }

    public function submitAnswer(Quiz $quiz, Question $question, array $selectedAnswerIds): QuizAnswer
    {
        $quizAnswer = new QuizAnswer();
        $quizAnswer->setQuiz($quiz);
        $quizAnswer->setQuestion($question);
        $quizAnswer->setSelectedAnswerIds(array_map('intval', $selectedAnswerIds));
        $quizAnswer->checkCorrectness();

        $quiz->addQuizAnswer($quizAnswer);
        $quiz->setCurrentQuestionIndex($quiz->getCurrentQuestionIndex() + 1);

        $this->em->persist($quizAnswer);
        $this->em->flush();

        return $quizAnswer;
    }

    public function completeQuiz(Quiz $quiz): void
    {
        $quiz->complete();

        $user = $quiz->getUser();
        if ($quiz->isPassed()) {
            $user->incrementQuizzesPassed();
        }

        $avgScore = $this->quizRepository->getAverageScoreByUser($user);
        $user->setAverageScore((string) $avgScore);

        $this->gamificationService->updateUserLevel($user);

        $this->em->flush();
    }

    public function abandonQuiz(Quiz $quiz): void
    {
        $quiz->setStatus(QuizStatus::ABANDONED->value);
        $quiz->setFinishedAt(new \DateTime());
        $this->em->flush();
    }

    public function getQuizProgress(Quiz $quiz): array
    {
        $totalQuestions = $quiz->getConfiguration()->getQuestionsCount();
        $currentIndex = $quiz->getCurrentQuestionIndex();

        return [
            'currentIndex' => $currentIndex,
            'totalQuestions' => $totalQuestions,
            'progress' => $totalQuestions > 0 ? ($currentIndex / $totalQuestions) * 100 : 0,
            'isComplete' => $currentIndex >= $totalQuestions,
        ];
    }
}
