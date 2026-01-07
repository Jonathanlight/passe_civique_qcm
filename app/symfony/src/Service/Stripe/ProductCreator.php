<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use App\Entity\Packaging;
use App\Service\Stripe\Interface\ProductCreatorInterface;

readonly class ProductCreator implements ProductCreatorInterface
{
    public function __construct(private string $secretKey)
    {
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function createProduct(Packaging $packaging): string
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        $product = $stripe->products->create([
            'name' => $packaging->getName() ?? '',
            'description' => 'Abonnement mensuel à '.$packaging->getName(),
        ]);

        return $product->id;
    }
}
