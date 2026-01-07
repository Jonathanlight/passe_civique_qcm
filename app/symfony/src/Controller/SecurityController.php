<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use App\Service\Mail\SubscriptionMailerInterface;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserService $userService,
        SubscriptionMailerInterface $mailer,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($userService->emailExists($data['email'])) {
                $this->addFlash('error', 'Un compte existe deja avec cet email.');

                return $this->render('security/register.html.twig', [
                    'form' => $form,
                ]);
            }

            $user = $userService->register(
                $data['email'],
                $data['password'],
                $data['firstName'],
                $data['lastName'],
                $request->getClientIp()
            );

            // Generate verification token and send email
            $user->generateEmailVerificationToken();
            $userService->save($user);

            try {
                $mailer->sendEmailVerification($user);
                $this->addFlash('success', 'Votre compte a ete cree ! Un email de verification vous a ete envoye. Vous pouvez deja profiter de 2 quiz gratuits.');
            } catch (\Throwable $e) {
                $this->addFlash('success', 'Votre compte a ete cree ! Vous pouvez profiter de 2 quiz gratuits.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/verification-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $userRepository->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Lien de verification invalide ou expire.');
            return $this->redirectToRoute('app_login');
        }

        if (!$user->isEmailVerificationTokenValid()) {
            $this->addFlash('error', 'Ce lien de verification a expire. Veuillez demander un nouveau lien.');
            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);
        $em->flush();

        $this->addFlash('success', 'Votre email a ete verifie avec succes ! Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/renvoyer-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        SubscriptionMailerInterface $mailer
    ): Response {
        $email = $request->query->get('email');

        if (!$email) {
            $this->addFlash('error', 'Adresse email manquante.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('success', 'Si cette adresse existe, un email de verification a ete envoye.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre compte est deja verifie.');
            return $this->redirectToRoute('app_login');
        }

        $user->generateEmailVerificationToken();
        $em->flush();

        try {
            $mailer->sendEmailVerification($user);
        } catch (\Throwable $e) {
            // Silent fail
        }

        $this->addFlash('success', 'Un nouvel email de verification a ete envoye.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): Response
    {
        return new Response();
    }

    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
