<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Package;
use App\Entity\Subscription;
use App\Entity\User;
use App\Manager\SubscriptionManager;
use App\Repository\PackageRepository;
use App\Service\Stripe\CustomerCreator;
use App\Service\Stripe\StripeClientSingleton;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly CustomerCreator $customerCreator,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly string $stripeSecretKey,
        private readonly string $stripePublicKey,
        private readonly string $stripeWebhookSecret,
    ) {
    }

    #[Route('/tarifs', name: 'app_pricing')]
    public function pricing(): Response
    {
        $packages = $this->packageRepository->findActivePackages();

        return $this->render('payment/pricing.html.twig', [
            'packages' => $packages,
            'stripePublicKey' => $this->stripePublicKey,
        ]);
    }

    #[Route('/paiement/{id}', name: 'app_checkout', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function checkout(Package $package): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->hasActiveSubscription()) {
            $this->addFlash('warning', 'Vous avez deja un abonnement actif.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('payment/checkout.html.twig', [
            'package' => $package,
            'stripePublicKey' => $this->stripePublicKey,
        ]);
    }

    #[Route('/paiement/{id}/create-session', name: 'app_create_checkout_session', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createCheckoutSession(Package $package, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $stripe = StripeClientSingleton::getInstance($this->stripeSecretKey);

            // Get or create Stripe customer
            $customerId = $user->getStripeCustomerId();
            if ($customerId) {
                // Verify customer exists in Stripe, recreate if not
                try {
                    $stripe->customers->retrieve($customerId);
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    // Customer doesn't exist, create a new one
                    $this->logger->warning('Stripe customer not found, creating new one', [
                        'old_customer_id' => $customerId,
                        'user_id' => $user->getId(),
                    ]);
                    $customerId = null;
                }
            }

            if (!$customerId) {
                $customerId = $this->customerCreator->createCustomer($user);
                $user->setStripeCustomerId($customerId);
                $this->em->flush();
            }

            $subscription = $this->subscriptionManager->createSubscription($user, $package);

            $successUrl = $this->generateUrl('app_payment_success', [
                'subscription' => $subscription->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}';

            $cancelUrl = $this->generateUrl('app_payment_cancel', [
                'subscription' => $subscription->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $sessionParams = [
                'customer' => $user->getStripeCustomerId(),
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $package->getName(),
                            'description' => sprintf(
                                'Abonnement %d mois - Passe Civique',
                                $package->getDurationInMonths()
                            ),
                        ],
                        'unit_amount' => $package->getPriceInCents(),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'subscription_id' => $subscription->getId(),
                    'user_id' => $user->getId(),
                    'package_id' => $package->getId(),
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'subscription_id' => $subscription->getId(),
                    ],
                ],
                'locale' => 'fr',
                'billing_address_collection' => 'required',
                'customer_update' => [
                    'address' => 'auto',
                    'name' => 'auto',
                ],
            ];

            $checkoutSession = $stripe->checkout->sessions->create($sessionParams);

            $this->subscriptionManager->updateStripeInfo(
                $subscription,
                paymentIntentId: $checkoutSession->payment_intent
            );

            return new JsonResponse([
                'sessionId' => $checkoutSession->id,
                'url' => $checkoutSession->url,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Stripe checkout error', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
            ]);

            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la creation de la session de paiement.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/paiement/succes/{subscription}', name: 'app_payment_success')]
    #[IsGranted('ROLE_USER')]
    public function success(Subscription $subscription, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($subscription->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $sessionId = $request->query->get('session_id');

        if ($sessionId && $subscription->getStatus() === Subscription::STATUS_PENDING) {
            try {
                $stripe = StripeClientSingleton::getInstance($this->stripeSecretKey);
                $session = $stripe->checkout->sessions->retrieve($sessionId, [
                    'expand' => ['payment_intent', 'payment_intent.latest_charge'],
                ]);

                if ($session->payment_status === 'paid') {
                    $this->subscriptionManager->activateSubscription($subscription);

                    if ($session->payment_intent) {
                        $paymentIntent = $session->payment_intent;
                        $invoiceUrl = null;
                        $invoicePdf = null;

                        if ($paymentIntent->latest_charge && $paymentIntent->latest_charge->receipt_url) {
                            $invoiceUrl = $paymentIntent->latest_charge->receipt_url;
                        }

                        $this->subscriptionManager->updateStripeInfo(
                            $subscription,
                            paymentIntentId: $paymentIntent->id,
                            invoiceUrl: $invoiceUrl,
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Error processing payment success', [
                    'error' => $e->getMessage(),
                    'session_id' => $sessionId,
                ]);
            }
        }

        return $this->render('payment/success.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    #[Route('/paiement/annule/{subscription}', name: 'app_payment_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(Subscription $subscription): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($subscription->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($subscription->getStatus() === Subscription::STATUS_PENDING) {
            $this->subscriptionManager->cancelSubscription($subscription);
        }

        return $this->render('payment/cancel.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    #[Route('/webhook/stripe', name: 'app_stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->stripeWebhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Invalid webhook payload', ['error' => $e->getMessage()]);
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->error('Invalid webhook signature', ['error' => $e->getMessage()]);
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        $this->handleStripeEvent($event);

        return new Response('OK', Response::HTTP_OK);
    }

    private function handleStripeEvent(Event $event): void
    {
        $this->logger->info('Stripe webhook received', ['type' => $event->type]);

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;

            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            case 'charge.succeeded':
                $this->handleChargeSucceeded($event->data->object);
                break;

            default:
                $this->logger->debug('Unhandled webhook event', ['type' => $event->type]);
        }
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $subscriptionId = $session->metadata->subscription_id ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = $this->em->getRepository(Subscription::class)->find($subscriptionId);

        if (!$subscription) {
            $this->logger->warning('Subscription not found for checkout', [
                'subscription_id' => $subscriptionId,
            ]);
            return;
        }

        if ($session->payment_status === 'paid') {
            $this->subscriptionManager->activateSubscription($subscription);
        }
    }

    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        $subscriptionId = $paymentIntent->metadata->subscription_id ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = $this->em->getRepository(Subscription::class)->find($subscriptionId);

        if (!$subscription) {
            return;
        }

        $this->subscriptionManager->updateStripeInfo(
            $subscription,
            paymentIntentId: $paymentIntent->id,
        );

        if ($subscription->getStatus() !== Subscription::STATUS_ACTIVE) {
            $this->subscriptionManager->activateSubscription($subscription);
        }
    }

    private function handlePaymentFailed(object $paymentIntent): void
    {
        $subscriptionId = $paymentIntent->metadata->subscription_id ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = $this->em->getRepository(Subscription::class)->find($subscriptionId);

        if ($subscription) {
            $subscription->setStatus(Subscription::STATUS_PAST_DUE);
            $this->em->flush();

            $this->logger->warning('Payment failed', [
                'subscription_id' => $subscription->getId(),
                'payment_intent_id' => $paymentIntent->id,
            ]);
        }
    }

    private function handleChargeSucceeded(object $charge): void
    {
        if (!isset($charge->payment_intent)) {
            return;
        }

        $subscription = $this->subscriptionManager->findByStripePaymentIntentId($charge->payment_intent);

        if ($subscription && $charge->receipt_url) {
            $this->subscriptionManager->updateStripeInfo(
                $subscription,
                invoiceUrl: $charge->receipt_url,
            );
        }
    }
}
