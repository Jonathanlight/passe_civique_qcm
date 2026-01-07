<?php

namespace App\Controller;

use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository,
        ThemeRepository $themeRepository
    ): Response {
        $stats = $quizRepository->getGlobalStats();
        $questionCount = $questionRepository->countActiveQuestions();
        $themes = $themeRepository->findBy(['isActive' => true], ['displayOrder' => 'ASC']);

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
            'questionCount' => $questionCount,
            'themes' => $themes,
        ]);
    }
}
