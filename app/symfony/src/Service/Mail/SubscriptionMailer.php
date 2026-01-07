<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\Subscription;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SubscriptionMailer implements SubscriptionMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        private readonly string $adminEmail,
        private readonly string $serviceEmail,
        private readonly string $fromName = 'Passe Civique',
    ) {
    }

    private function getFromAddress(): Address
    {
        $from = $this->serviceEmail ?: 'noreply@passecivique.fr';
        return new Address($from, $this->fromName);
    }

    public function sendSubscriptionConfirmation(Subscription $subscription): void
    {
        $user = $subscription->getUser();
        if ($user === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from($this->getFromAddress())
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Confirmation de votre abonnement Passe Civique')
            ->htmlTemplate('emails/subscription_confirmation.html.twig')
            ->context([
                'subscription' => $subscription,
                'user' => $user,
                'package' => $subscription->getPackage(),
                'profileUrl' => $this->urlGenerator->generate('app_profile', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        $this->mailer->send($email);
    }

    public function sendEmailVerification(User $user): void
    {
        $token = $user->getEmailVerificationToken();
        if ($token === null) {
            return;
        }

        $verificationUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from($this->getFromAddress())
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Confirmez votre adresse email - Passe Civique')
            ->htmlTemplate('emails/email_verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $user->getEmailVerificationTokenExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    public function sendSubscriptionExpiringSoon(Subscription $subscription, int $daysRemaining): void
    {
        $user = $subscription->getUser();
        if ($user === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from($this->getFromAddress())
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Votre abonnement Passe Civique expire bientot')
            ->htmlTemplate('emails/subscription_expiring.html.twig')
            ->context([
                'subscription' => $subscription,
                'user' => $user,
                'daysRemaining' => $daysRemaining,
                'renewUrl' => $this->urlGenerator->generate('app_pricing', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        $this->mailer->send($email);
    }

    public function sendPaymentFailed(Subscription $subscription): void
    {
        $user = $subscription->getUser();
        if ($user === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from($this->getFromAddress())
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Echec du paiement - Passe Civique')
            ->htmlTemplate('emails/payment_failed.html.twig')
            ->context([
                'subscription' => $subscription,
                'user' => $user,
                'updatePaymentUrl' => $this->urlGenerator->generate('app_pricing', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        $this->mailer->send($email);
    }

    public function sendWelcomeEmail(User $user): void
    {
        // Email to user
        $userEmail = (new TemplatedEmail())
            ->from($this->getFromAddress())
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Bienvenue sur Passe Civique !')
            ->htmlTemplate('emails/welcome_user.html.twig')
            ->context([
                'user' => $user,
                'quizUrl' => $this->urlGenerator->generate('app_quiz_start', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'profileUrl' => $this->urlGenerator->generate('app_profile', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        try {
            $this->mailer->send($userEmail);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send welcome email to user', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
        }

        // Email to admin
        if ($this->adminEmail) {
            $adminEmail = (new TemplatedEmail())
                ->from($this->getFromAddress())
                ->to(new Address($this->adminEmail, 'Admin Passe Civique'))
                ->subject('Nouvelle inscription - ' . $user->getFullName())
                ->htmlTemplate('emails/welcome_admin.html.twig')
                ->context([
                    'user' => $user,
                    'registeredAt' => $user->getCreatedAt(),
                ]);

            try {
                $this->mailer->send($adminEmail);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send welcome notification to admin', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function sendNewSubscriptionNotification(Subscription $subscription): void
    {
        $user = $subscription->getUser();
        $package = $subscription->getPackage();

        if ($user === null || $package === null) {
            return;
        }

        // Email to user (already handled by sendSubscriptionConfirmation, but we ensure admin is notified)

        // Email to admin
        if ($this->adminEmail) {
            $adminEmail = (new TemplatedEmail())
                ->from($this->getFromAddress())
                ->to(new Address($this->adminEmail, 'Admin Passe Civique'))
                ->subject('Nouvel abonnement - ' . $user->getFullName())
                ->htmlTemplate('emails/subscription_admin.html.twig')
                ->context([
                    'user' => $user,
                    'subscription' => $subscription,
                    'package' => $package,
                ]);

            try {
                $this->mailer->send($adminEmail);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send subscription notification to admin', [
                    'subscription_id' => $subscription->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function sendLoginNotification(User $user, array $loginInfo): void
    {
        // Email to user about login
        $userEmail = (new TemplatedEmail())
            ->from($this->getFromAddress())
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Nouvelle connexion a votre compte Passe Civique')
            ->htmlTemplate('emails/login_notification.html.twig')
            ->context([
                'user' => $user,
                'loginInfo' => $loginInfo,
                'profileUrl' => $this->urlGenerator->generate('app_profile', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        try {
            $this->mailer->send($userEmail);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send login notification to user', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
        }

        // Email to admin about login
        if ($this->adminEmail) {
            $adminEmail = (new TemplatedEmail())
                ->from($this->getFromAddress())
                ->to(new Address($this->adminEmail, 'Admin Passe Civique'))
                ->subject('Connexion utilisateur - ' . $user->getFullName())
                ->htmlTemplate('emails/login_admin.html.twig')
                ->context([
                    'user' => $user,
                    'loginInfo' => $loginInfo,
                ]);

            try {
                $this->mailer->send($adminEmail);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send login notification to admin', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
