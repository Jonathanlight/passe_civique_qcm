<?php

declare(strict_types=1);

namespace App\Service\Stripe\Interface;

interface WebhookHandlerInterface
{
    /**
     * Traite un événement webhook de Stripe.
     */
    public function handleWebhook(string $payload, string $signature): void;
}
