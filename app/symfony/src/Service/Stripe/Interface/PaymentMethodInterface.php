<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

use App\Entity\User;

/**
 * Interface PaymentMethodInterface.
 *
 * @author Jonathan Kablan
 */
interface PaymentMethodInterface
{
    public function createPaymentMethod(User $user): string;

    /**
     * Attache une méthode de paiement à un customer.
     */
    public function attachPaymentMethodToCustomer(string $customerId, string $paymentMethodId): void;
}
