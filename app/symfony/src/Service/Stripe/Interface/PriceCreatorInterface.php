<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

use App\Entity\Packaging;

/**
 * Interface PriceCreatorInterface.
 *
 * @author Jonathan Kablan
 */
interface PriceCreatorInterface
{
    public function createPrice(Packaging $packaging, string $productId): string;
}
