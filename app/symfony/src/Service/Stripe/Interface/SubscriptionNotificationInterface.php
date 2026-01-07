<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

use App\Entity\Subscription;

interface SubscriptionNotificationInterface
{
    /**
     * Envoie une notification de renouvellement imminent.
     */
    public function sendRenewalNotification(Subscription $subscription): void;

    /**
     * Envoie une notification d'annulation d'abonnement.
     */
    public function sendCancellationNotification(Subscription $subscription): void;

    /**
     * Envoie une notification d'échec de paiement.
     */
    public function sendPaymentFailedNotification(Subscription $subscription): void;
}
