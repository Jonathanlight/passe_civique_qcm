<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

use App\Entity\Packaging;

/**
 * Interface ProductCreatorInterface.
 *
 * @author Jonathan Kablan
 */
interface ProductCreatorInterface
{
    public function createProduct(Packaging $packaging): string;
}
