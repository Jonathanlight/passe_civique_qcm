<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use App\Repository\ThemeRepository;
use App\Repository\UserRepository;
use App\Repository\QuizRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin')]
    public function index(
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository,
        UserRepository $userRepository
    ): Response {
        return $this->render('admin/index.html.twig', [
            'globalStats' => $quizRepository->getGlobalStats(),
            'questionCount' => $questionRepository->countActiveQuestions(),
            'userCount' => $userRepository->count([]),
            'questionsByTheme' => $questionRepository->countByTheme(),
        ]);
    }

    #[Route('/questions', name: 'app_admin_questions')]
    public function questions(QuestionRepository $questionRepository): Response
    {
        return $this->render('admin/questions.html.twig', [
            'questions' => $questionRepository->findAll(),
        ]);
    }

    #[Route('/themes', name: 'app_admin_themes')]
    public function themes(ThemeRepository $themeRepository): Response
    {
        return $this->render('admin/themes.html.twig', [
            'themes' => $themeRepository->findBy([], ['displayOrder' => 'ASC']),
        ]);
    }

    #[Route('/utilisateurs', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }
}
