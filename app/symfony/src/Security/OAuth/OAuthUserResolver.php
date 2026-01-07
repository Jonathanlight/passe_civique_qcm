<?php

declare(strict_types=1);

namespace App\Security\OAuth;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\GoogleUser;

class OAuthUserResolver
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function loadOrCreateUserFromGoogle(GoogleUser $googleUser): User
    {
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user !== null) {
            // Update OAuth info and verify user if they connect via Google
            if ($user->getOauthProvider() === null) {
                $user->setOauthProvider('google');
                $user->setOauthProviderId($googleId);
            }
            // Google verifies emails, so we can trust the user is verified
            if (!$user->isVerified()) {
                $user->setIsVerified(true);
            }
            $this->em->flush();
            return $user;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($googleUser->getFirstName() ?? 'Utilisateur');
        $user->setLastName($googleUser->getLastName() ?? 'Google');
        $user->setOauthProvider('google');
        $user->setOauthProviderId($googleId);
        $user->setIsVerified(true);
        $user->setIsActive(true);
        $user->setRoles([User::ROLE_USER]);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
