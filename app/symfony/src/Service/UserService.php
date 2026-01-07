<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\GamificationLevelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private GamificationLevelRepository $levelRepository,
    ) {
    }

    public function register(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        ?string $ip = null
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRegistrationIp($ip ?? '');
        $user->setIsVerified(false); // Email verification required
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $initialLevel = $this->levelRepository->findLowestLevel();
        if ($initialLevel) {
            $user->setGamificationLevel($initialLevel);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function updateProfile(User $user, string $firstName, string $lastName, ?string $newPassword = null): User
    {
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        if ($newPassword !== null && $newPassword !== '') {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
        }

        $this->em->flush();

        return $user;
    }

    public function recordLogin(User $user, ?string $ip = null): void
    {
        $user->setLastLoginAt(new \DateTime());
        if ($ip !== null) {
            $user->setLastLoginIp($ip);
        }
        $this->em->flush();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }

    public function emailExists(string $email): bool
    {
        return $this->userRepository->findOneBy(['email' => $email]) !== null;
    }

    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }
}
