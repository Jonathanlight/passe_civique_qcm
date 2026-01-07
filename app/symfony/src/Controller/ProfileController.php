<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Repository\QuizRepository;
use App\Service\UserService;
use App\Service\GamificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(
        QuizRepository $quizRepository,
        GamificationService $gamificationService
    ): Response {
        $user = $this->getUser();
        $stats = $quizRepository->getStatsByUser($user);
        $recentQuizzes = $quizRepository->findCompletedByUser($user);
        $scoreHistory = $quizRepository->getScoreHistoryByUser($user);
        $levelProgress = $gamificationService->getProgressToNextLevel($user);
        $nextLevel = $gamificationService->getNextLevel($user);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'recentQuizzes' => array_slice($recentQuizzes, 0, 10),
            'scoreHistory' => $scoreHistory,
            'levelProgress' => $levelProgress,
            'nextLevel' => $nextLevel,
        ]);
    }

    #[Route('/modifier', name: 'app_profile_edit')]
    public function edit(Request $request, UserService $userService): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $userService->updateProfile(
                $user,
                $form->get('firstName')->getData(),
                $form->get('lastName')->getData(),
                $newPassword
            );

            $this->addFlash('success', 'Profil mis a jour avec succes !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/historique', name: 'app_profile_history')]
    public function history(QuizRepository $quizRepository): Response
    {
        $user = $this->getUser();
        $quizzes = $quizRepository->findByUser($user);

        return $this->render('profile/history.html.twig', [
            'quizzes' => $quizzes,
        ]);
    }
}
