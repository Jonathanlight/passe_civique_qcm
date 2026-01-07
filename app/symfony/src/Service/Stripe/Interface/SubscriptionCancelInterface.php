<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

/**
 * Interface SubscriptionCancelInterface.
 *
 * @author Jonathan Kablan
 */
interface SubscriptionCancelInterface
{
    public function cancelSubscription(string $subscriptionId, bool $cancelAtPeriodEnd = true): bool;
}
