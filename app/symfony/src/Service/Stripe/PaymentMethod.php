<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use App\Entity\User;
use App\Service\Stripe\Interface\PaymentMethodInterface;

class PaymentMethod implements PaymentMethodInterface
{
    public function __construct(private readonly string $secretKey)
    {
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function createPaymentMethod(User $user): string
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        $paymentMethod = $stripe->paymentMethods->create([
            'type' => 'sepa_debit',
            'sepa_debit' => [
                'iban' => 'DE89370400440532013000',
            ],
            'billing_details' => [
                'name' => $user->getUserIdentifier(),
                'email' => $user->getEmail(),
            ],
        ]);

        if (!$paymentMethod->id) {
            throw new \RuntimeException('Failed to create payment method on Stripe.');
        }

        return $paymentMethod->id;
    }

    /**
     * Attache une méthode de paiement à un customer.
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function attachPaymentMethodToCustomer(string $customerId, string $paymentMethodId): void
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        $stripe->paymentMethods->attach(
            $paymentMethodId,
            ['customer' => $customerId]
        );
    }
}
