<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use App\Entity\User;
use App\Service\Stripe\Interface\CustomerCreatorInterface;

class CustomerCreator implements CustomerCreatorInterface
{
    public function __construct(private readonly string $secretKey)
    {
    }

    /**
     * @return string Stripe customer ID
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function createCustomer(User $user): string
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        $email = $user->getEmail();
        $name = $user->getUserIdentifier();

        if (!$email || !$name) {
            throw new \InvalidArgumentException('User must have a valid email and name to create a Stripe customer.');
        }

        $customer = $stripe->customers->create([
            'email' => $email,
            'name' => $name,
            'address' => [
                'country' => 'FR',
            ],
        ]);

        return $customer->id;
    }

    /**
     * Met à jour la méthode de paiement par défaut d'un customer.
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function updateDefaultPaymentMethod(string $customerId, string $paymentMethodId): void
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        $stripe->customers->update($customerId, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);
    }
}
