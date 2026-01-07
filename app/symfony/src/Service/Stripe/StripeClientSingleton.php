<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use Stripe\StripeClient;

class StripeClientSingleton
{
    private static ?StripeClient $client = null;

    public static function getInstance(string $secretKey): StripeClient
    {
        // Check if the client is already instantiated
        if (null === self::$client) {
            self::$client = new StripeClient($secretKey);
        }

        return self::$client;
    }
}
