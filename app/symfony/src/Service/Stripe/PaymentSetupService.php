<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use App\Service\Stripe\Interface\PaymentSetupInterface;

class PaymentSetupService implements PaymentSetupInterface
{
    public function __construct(private readonly string $secretKey)
    {
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function createSetupIntent(?string $stripeCustomerId = null): string
    {
        $dataOptions = ['usage' => 'off_session'];
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        if ($stripeCustomerId) {
            $dataOptions = [
                'customer' => $stripeCustomerId,
            ];
        }

        $setupIntent = $stripe->setupIntents->create($dataOptions);

        if (!$setupIntent->client_secret) {
            throw new \RuntimeException('Stripe SetupIntent did not return a client_secret.');
        }

        return $setupIntent->client_secret;
    }
}
