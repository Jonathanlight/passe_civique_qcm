<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

use App\Entity\User;

/**
 * Interface CustomerCreatorInterface.
 *
 * @author Jonathan Kablan
 */
interface CustomerCreatorInterface
{
    public function createCustomer(User $user): string;

    /**
     * Met à jour la méthode de paiement par défaut d'un customer.
     */
    public function updateDefaultPaymentMethod(string $customerId, string $paymentMethodId): void;
}
