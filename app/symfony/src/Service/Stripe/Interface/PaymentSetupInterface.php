<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

/**
 * Interface PaymentSetupInterface.
 *
 * @author Jonathan Kablan
 */
interface PaymentSetupInterface
{
    public function createSetupIntent(?string $stripeCustomerId): string;
}
