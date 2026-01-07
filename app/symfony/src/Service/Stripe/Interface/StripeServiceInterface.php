<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

interface StripeServiceInterface
{
    /**
     * Met à jour la méthode de paiement par défaut d'un customer.
     * Cette méthode sera utilisée pour tous les futurs paiements et abonnements du customer.
     */
    public function updateCustomerPaymentMethod(
        string $customerId,
        string $paymentMethodId,
    ): void;
}
