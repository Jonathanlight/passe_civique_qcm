<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

use App\Entity\Packaging;
use App\Entity\User;
use Stripe\Subscription;

/**
 * Interface SubscriptionCreatorInterface.
 *
 * @author Jonathan Kablan
 */
interface SubscriptionCreatorInterface
{
    /**
     * @return array{subscriptionId: string, invoiceUrl: string}
     *
     * @throws \Stripe\Exception\ApiErrorException
     *
     * Creates a subscription for the given user and packaging
     */
    public function createSubscription(User $user, Packaging $packaging, ?string $paymentMethodId, ?string $paymentMethodManuel, bool $isTrialPeriodDays): array;

    /**
     * @throws \Stripe\Exception\ApiErrorException
     *
     * Gets the invoice URL for the given subscription
     */
    public function getInvoiceUrl(Subscription $subscription): string;

    /**
     * Met à jour la méthode de paiement d'un abonnement.
     */
    public function updateSubscriptionPaymentMethod(string $subscriptionId, string $paymentMethodId): void;
}
