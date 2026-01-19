<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\Subscription;
use App\Entity\User;

interface SubscriptionMailerInterface
{
    public function sendSubscriptionConfirmation(Subscription $subscription): void;

    public function sendEmailVerification(User $user): void;

    public function sendSubscriptionExpiringSoon(Subscription $subscription, int $daysRemaining): void;

    public function sendPaymentFailed(Subscription $subscription): void;

    public function sendWelcomeEmail(User $user): void;

    public function sendNewSubscriptionNotification(Subscription $subscription): void;

    public function sendLoginNotification(User $user, array $loginInfo): void;

    public function sendPasswordReset(User $user): void;
}
