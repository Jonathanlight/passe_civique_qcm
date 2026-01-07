<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use App\Entity\Packaging;
use App\Service\Stripe\Interface\PriceCreatorInterface;

class PriceCreator implements PriceCreatorInterface
{
    /**
     * Conversion factor from Euro to cents.
     * Stripe requires amounts in the smallest currency unit (cents for Euro).
     */
    private const int EURO_TO_CENTS = 100;

    public function __construct(private readonly string $secretKey)
    {
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function createPrice(Packaging $packaging, string $productId): string
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        $price = $stripe->prices->create([
            'unit_amount' => (int) round($packaging->getPrice() * self::EURO_TO_CENTS),
            'currency' => 'eur',
            'recurring' => ['interval' => 'month'],
            'product' => $productId,
        ]);

        return $price->id;
    }
}
