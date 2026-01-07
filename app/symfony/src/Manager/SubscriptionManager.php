<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Package;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Service\Mail\SubscriptionMailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SubscriptionManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly SubscriptionMailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createSubscription(User $user, Package $package): Subscription
    {
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPackage($package);
        $subscription->setAmountPaid($package->getPrice());
        $subscription->setStartDate(new \DateTime());
        $subscription->setEndDate($subscription->calculateEndDate());
        $subscription->setStatus(Subscription::STATUS_PENDING);

        $this->em->persist($subscription);
        $this->em->flush();

        $this->logger->info('Subscription created', [
            'subscription_id' => $subscription->getId(),
            'user_id' => $user->getId(),
            'package_id' => $package->getId(),
        ]);

        return $subscription;
    }

    public function activateSubscription(Subscription $subscription): void
    {
        $subscription->setStatus(Subscription::STATUS_ACTIVE);
        $subscription->setStartDate(new \DateTime());
        $subscription->setEndDate($subscription->calculateEndDate());

        $this->em->flush();

        $this->logger->info('Subscription activated', [
            'subscription_id' => $subscription->getId(),
        ]);

        // Send confirmation email to user
        try {
            $this->mailer->sendSubscriptionConfirmation($subscription);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send subscription confirmation email', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage(),
            ]);
        }

        // Send notification email to admin
        try {
            $this->mailer->sendNewSubscriptionNotification($subscription);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send subscription admin notification', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function cancelSubscription(Subscription $subscription): void
    {
        $subscription->setStatus(Subscription::STATUS_CANCELLED);
        $subscription->setCancelledAt(new \DateTime());

        $this->em->flush();

        $this->logger->info('Subscription cancelled', [
            'subscription_id' => $subscription->getId(),
        ]);
    }

    public function expireSubscription(Subscription $subscription): void
    {
        $subscription->setStatus(Subscription::STATUS_EXPIRED);
        $this->em->flush();

        $this->logger->info('Subscription expired', [
            'subscription_id' => $subscription->getId(),
        ]);
    }

    public function updateStripeInfo(
        Subscription $subscription,
        ?string $subscriptionId = null,
        ?string $paymentIntentId = null,
        ?string $invoiceId = null,
        ?string $invoiceUrl = null,
        ?string $invoicePdf = null,
    ): void {
        if ($subscriptionId !== null) {
            $subscription->setStripeSubscriptionId($subscriptionId);
        }
        if ($paymentIntentId !== null) {
            $subscription->setStripePaymentIntentId($paymentIntentId);
        }
        if ($invoiceId !== null) {
            $subscription->setStripeInvoiceId($invoiceId);
        }
        if ($invoiceUrl !== null) {
            $subscription->setStripeInvoiceUrl($invoiceUrl);
        }
        if ($invoicePdf !== null) {
            $subscription->setStripeInvoicePdf($invoicePdf);
        }

        $this->em->flush();
    }

    public function isUserSubscriptionValid(User $user): bool
    {
        $subscription = $this->subscriptionRepository->findActiveSubscriptionForUser($user);

        if ($subscription === null) {
            return $user->canTakeFreeQuiz();
        }

        return $subscription->isActive();
    }

    public function getActiveSubscription(User $user): ?Subscription
    {
        return $this->subscriptionRepository->findActiveSubscriptionForUser($user);
    }

    public function findByStripePaymentIntentId(string $paymentIntentId): ?Subscription
    {
        return $this->subscriptionRepository->findByStripePaymentIntentId($paymentIntentId);
    }

    public function findByStripeSubscriptionId(string $subscriptionId): ?Subscription
    {
        return $this->subscriptionRepository->findByStripeSubscriptionId($subscriptionId);
    }

    public function processExpiredSubscriptions(): int
    {
        $expiredCount = 0;
        $subscriptions = $this->subscriptionRepository->findBy([
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        foreach ($subscriptions as $subscription) {
            if ($subscription->isExpired()) {
                $this->expireSubscription($subscription);
                $expiredCount++;
            }
        }

        return $expiredCount;
    }
}
