<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use App\Service\Stripe\Interface\SubscriptionCancelInterface;

readonly class SubscriptionCancel implements SubscriptionCancelInterface
{
    public function __construct(private string $secretKey)
    {
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function cancelSubscription(string $subscriptionId, bool $cancelAtPeriodEnd = true): bool
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        $subscription = $stripe->subscriptions->update($subscriptionId, [
            'cancel_at_period_end' => $cancelAtPeriodEnd,
        ]);

        return 'canceled' === $subscription->status || true === $subscription->cancel_at_period_end;
    }
}
