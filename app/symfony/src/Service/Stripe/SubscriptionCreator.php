<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use App\Entity\Packaging;
use App\Entity\User;
use App\Enum\ManagerEnum;
use App\Manager\ManagerManager;
use App\Service\Stripe\Interface\SubscriptionCreatorInterface;
use Stripe\Subscription;

class SubscriptionCreator implements SubscriptionCreatorInterface
{
    public function __construct(
        private readonly string $secretKey,
        private readonly ManagerManager $managerManager,
    ) {
    }

    /**
     * @return array{subscriptionId: string, invoiceUrl: string}
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function createSubscription(User $user, Packaging $packaging, ?string $paymentMethodId, ?string $paymentMethodManuel, bool $isTrialPeriodDays = true): array
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);
        $trialPeriodDays = $this->managerManager->getActiveTrialPeriod(ManagerEnum::MANAGER->value);

        // Vérifier que nous avons bien un Payment Method ID
        if (!$paymentMethodId) {
            throw new \InvalidArgumentException('Payment Method ID is required to create a subscription.');
        }

        // Assure-toi que l'utilisateur a un customer Stripe ID
        $customer = $user->getStripeCustomer();

        // 1. Créer un customer Stripe si l'utilisateur n'en a pas
        if (!$customer) {
            $email = $user->getEmail();
            $name = $user->getUserIdentifier();

            if (!$email || !$name) {
                throw new \InvalidArgumentException('User must have a valid email and name to create a Stripe customer.');
            }

            $customerObj = $stripe->customers->create([
                'email' => $email,
                'name' => $name,
                'address' => [
                    'country' => 'FR',
                ],
            ]);
            $customer = $customerObj->id;
            $user->setStripeCustomer($customer);
        }

        // 2. Attacher le payment method au customer
        $stripe->paymentMethods->attach(
            $paymentMethodId,
            ['customer' => $customer]
        );

        // 3. Mettre à jour le customer avec le payment method par défaut
        $stripe->customers->update($customer, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);

        $priceId = $packaging->getStripePrice();
        if (!$priceId) {
            throw new \InvalidArgumentException('Stripe price ID must not be null.');
        }        // Vérifier si le prix existe, sinon le créer
        try {
            $stripe->prices->retrieve($priceId);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Le prix n'existe pas, créons-le
            $isAnnual = false !== stripos($packaging->getName(), 'annuel');
            $interval = $isAnnual ? 'year' : 'month';

            $priceObj = $stripe->prices->create([
                'currency' => 'eur',
                'unit_amount' => (int) ($packaging->getPrice() * 100), // Convertir en centimes
                'recurring' => [
                    'interval' => $interval,
                ],
                'product_data' => [
                    'name' => $packaging->getName(),
                ],
            ]);
            $priceId = $priceObj->id;

            if (!$priceId) {
                throw new \InvalidArgumentException('Stripe price ID must not be null.');
            }
        }

        // 4. Créer la subscription
        $subscriptionData = [
            'customer' => $customer,
            'items' => [[
                'price' => $priceId,
            ]],
            'default_payment_method' => $paymentMethodId,
        ];

        if ($isTrialPeriodDays && null !== $trialPeriodDays) {
            $subscriptionData['trial_period_days'] = $trialPeriodDays;
        }

        $subscription = $stripe->subscriptions->create($subscriptionData);

        if (!$subscription->latest_invoice) {
            throw new \RuntimeException('Stripe subscription did not return a latest invoice.');
        }

        return [
            'subscriptionId' => $subscription->id,
            'invoiceUrl' => $this->getInvoiceUrl($subscription),
        ];
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function getInvoiceUrl(Subscription $subscription): string
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        $invoiceId = $subscription->latest_invoice;

        if (!is_string($invoiceId)) {
            throw new \RuntimeException('Expected invoice ID as string.');
        }

        $invoice = $stripe->invoices->retrieve($invoiceId);

        return $invoice->hosted_invoice_url ?? '';
    }

    /**
     * Met à jour la méthode de paiement d'un abonnement.
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[\Override]
    public function updateSubscriptionPaymentMethod(string $subscriptionId, string $paymentMethodId): void
    {
        $stripe = StripeClientSingleton::getInstance($this->secretKey);

        $stripe->subscriptions->update($subscriptionId, [
            'default_payment_method' => $paymentMethodId,
        ]);
    }
}
