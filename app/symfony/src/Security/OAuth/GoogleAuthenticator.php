<?php

declare(strict_types=1);

namespace App\Security\OAuth;

use App\Entity\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class GoogleAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ClientRegistry $clientRegistry,
        private readonly OAuthUserResolver $resolver,
    ) {
    }

    #[\Override]
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $client->getAccessToken();

        if (!$accessToken instanceof AccessToken) {
            $accessToken = new AccessToken($accessToken->jsonSerialize());
        }

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);
        $email = $googleUser->getEmail();

        return new SelfValidatingPassport(
            new UserBadge($email, fn($userIdentifier) => $this->resolver->loadOrCreateUserFromGoogle($googleUser))
        );
    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return $this->redirectToLogin($request, 'Utilisateur introuvable.');
        }

        if (!$user->isActive()) {
            return $this->redirectToLogin($request, 'Votre compte est desactive.');
        }

        // Allow unverified users to access free quizzes
        // Email verification is encouraged but not required for basic access

        $targetPath = $request->getSession()->get('_security.main.target_path');
        if ($targetPath) {
            $request->getSession()->remove('_security.main.target_path');
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }

    #[\Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    private function redirectToLogin(Request $request, string $message): RedirectResponse
    {
        $request->getSession()->set(
            SecurityRequestAttributes::AUTHENTICATION_ERROR,
            new AuthenticationException($message)
        );
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
