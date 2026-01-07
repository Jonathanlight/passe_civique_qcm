<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

use App\Entity\Subscription;

interface SubscriptionSyncInterface
{
    /**
     * Synchronise tous les abonnements actifs avec Stripe.
     *
     * @return array{synced: int, errors: int, updated: array<string>, failed: array<string>}
     */
    public function syncAllActiveSubscriptions(): array;

    /**
     * Synchronise un abonnement spécifique.
     *
     * @return bool true si l'abonnement a été modifié
     */
    public function syncSubscription(Subscription $subscription): bool;

    /**
     * Vérifie les renouvellements à venir.
     *
     * @return array{total: int, renewals: list<array{id: int|null, stripe_id: string|null, user: string|null, organization: string|null, packaging: string|null, price: float|null, price_currency: string|null, end_date: string|null, days_remaining: int|false}>}
     */
    public function checkUpcomingRenewals(int $days = 7): array;
}
