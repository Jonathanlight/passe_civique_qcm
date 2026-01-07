<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use Stripe\StripeClient;

class PaymentMethodUpdater
{
    public function __construct(
        private readonly StripeClient $stripeClient,
    ) {
    }

    /**
     * Attache une méthode de paiement à un customer.
     */
    public function attachPaymentMethodToCustomer(
        string $customerId,
        string $paymentMethodId,
    ): void {
        $this->stripeClient->paymentMethods->attach(
            $paymentMethodId,
            ['customer' => $customerId]
        );
    }

    /**
     * Met à jour la méthode de paiement par défaut d'un customer.
     */
    public function updateDefaultPaymentMethod(
        string $customerId,
        string $paymentMethodId,
    ): void {
        $this->stripeClient->customers->update($customerId, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);
    }

    /**
     * Met à jour la méthode de paiement d'un abonnement.
     */
    public function updateSubscriptionPaymentMethod(
        string $subscriptionId,
        string $paymentMethodId,
    ): void {
        $this->stripeClient->subscriptions->update($subscriptionId, [
            'default_payment_method' => $paymentMethodId,
        ]);
    }
}
