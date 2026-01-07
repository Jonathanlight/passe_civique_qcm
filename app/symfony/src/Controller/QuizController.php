<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\QuizConfiguration;
use App\Entity\User;
use App\Repository\QuizConfigurationRepository;
use App\Service\QuizService;
use App\Service\QuestionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/quiz')]
#[IsGranted('ROLE_USER')]
class QuizController extends AbstractController
{
    #[Route('/demarrer', name: 'app_quiz_start')]
    public function start(QuizConfigurationRepository $configRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $configs = $configRepository->findBy(['isActive' => true], ['isFreeAccess' => 'DESC', 'name' => 'ASC']);

        return $this->render('quiz/start.html.twig', [
            'configurations' => $configs,
            'remainingFreeQuizzes' => $user->getRemainingFreeQuizzes(),
            'hasSubscription' => $user->hasActiveSubscription(),
        ]);
    }

    #[Route('/creer/{id}', name: 'app_quiz_create')]
    public function create(
        QuizConfiguration $config,
        QuizService $quizService,
        QuestionService $questionService,
        SessionInterface $session
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Check if user has access to this quiz type
        if (!$user->hasActiveSubscription()) {
            if (!$config->isFreeAccess()) {
                $this->addFlash('warning', 'Ce mode de quiz necessite un abonnement.');
                return $this->redirectToRoute('app_pricing');
            }

            if (!$user->canTakeFreeQuiz()) {
                $this->addFlash('warning', 'Vous avez utilise vos 2 quiz gratuits. Abonnez-vous pour continuer.');
                return $this->redirectToRoute('app_pricing');
            }
        }

        $quiz = $quizService->createQuiz($user, $config);

        $questions = $quizService->getQuestionsForQuiz($quiz);
        $questionIds = array_map(fn ($q) => $q->getId(), $questions);
        $session->set('quiz_' . $quiz->getId() . '_questions', $questionIds);

        return $this->redirectToRoute('app_quiz_question', ['id' => $quiz->getId()]);
    }

    #[Route('/{id}/question', name: 'app_quiz_question')]
    public function question(
        Quiz $quiz,
        QuestionService $questionService,
        SessionInterface $session
    ): Response {
        $this->denyAccessUnlessGranted('view', $quiz);

        if ($quiz->isCompleted()) {
            return $this->redirectToRoute('app_quiz_results', ['id' => $quiz->getId()]);
        }

        $questionIds = $session->get('quiz_' . $quiz->getId() . '_questions', []);

        if (empty($questionIds)) {
            $questions = $questionService->getRandomQuestions($quiz->getConfiguration()->getQuestionsCount());
            $questionIds = array_map(fn ($q) => $q->getId(), $questions);
            $session->set('quiz_' . $quiz->getId() . '_questions', $questionIds);
        }

        $currentIndex = $quiz->getCurrentQuestionIndex();
        $totalQuestions = count($questionIds);

        if ($currentIndex >= $totalQuestions) {
            return $this->redirectToRoute('app_quiz_finish', ['id' => $quiz->getId()]);
        }

        $question = $questionService->getQuestionById($questionIds[$currentIndex]);

        $config = $quiz->getConfiguration();
        $answers = $question->getAnswers()->toArray();
        if ($config->isShuffleAnswers()) {
            shuffle($answers);
        }

        return $this->render('quiz/question.html.twig', [
            'quiz' => $quiz,
            'question' => $question,
            'answers' => $answers,
            'currentIndex' => $currentIndex + 1,
            'totalQuestions' => $totalQuestions,
            'progress' => ($currentIndex / $totalQuestions) * 100,
            'config' => $config,
        ]);
    }

    #[Route('/{id}/repondre', name: 'app_quiz_answer', methods: ['POST'])]
    public function answer(
        Quiz $quiz,
        Request $request,
        QuizService $quizService,
        QuestionService $questionService,
        SessionInterface $session
    ): Response {
        $this->denyAccessUnlessGranted('view', $quiz);

        $questionId = $request->request->get('question_id');
        $selectedAnswers = $request->request->all('answers');

        $question = $questionService->getQuestionById((int) $questionId);

        if ($question) {
            $quizService->submitAnswer($quiz, $question, $selectedAnswers);
        }

        $questionIds = $session->get('quiz_' . $quiz->getId() . '_questions', []);
        if ($quiz->getCurrentQuestionIndex() >= count($questionIds)) {
            return $this->redirectToRoute('app_quiz_finish', ['id' => $quiz->getId()]);
        }

        return $this->redirectToRoute('app_quiz_question', ['id' => $quiz->getId()]);
    }

    #[Route('/{id}/terminer', name: 'app_quiz_finish')]
    public function finish(Quiz $quiz, QuizService $quizService, SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('view', $quiz);

        if (!$quiz->isCompleted()) {
            $quizService->completeQuiz($quiz);
        }

        $session->remove('quiz_' . $quiz->getId() . '_questions');

        return $this->redirectToRoute('app_quiz_results', ['id' => $quiz->getId()]);
    }

    #[Route('/{id}/resultats', name: 'app_quiz_results')]
    public function results(Quiz $quiz): Response
    {
        $this->denyAccessUnlessGranted('view', $quiz);

        return $this->render('quiz/results.html.twig', [
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}/abandonner', name: 'app_quiz_abandon')]
    public function abandon(Quiz $quiz, QuizService $quizService, SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('view', $quiz);

        $quizService->abandonQuiz($quiz);
        $session->remove('quiz_' . $quiz->getId() . '_questions');

        $this->addFlash('info', 'Quiz abandonne.');

        return $this->redirectToRoute('app_profile');
    }
}
