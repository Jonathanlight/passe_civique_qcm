<?php

namespace App\DataFixtures;

use App\Entity\GamificationLevel;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_TEST = 'user-test';
    public const USER_ADMIN = 'user-admin';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // User de test
        $userTest = new User();
        $userTest->setEmail('john@gmail.com');
        $userTest->setFirstName('Jean');
        $userTest->setLastName('Dupont');
        $userTest->setRoles(['ROLE_USER']);
        $userTest->setPassword($this->passwordHasher->hashPassword($userTest, 'password'));
        $userTest->setIsActive(true);
        $userTest->setRegistrationIp('127.0.0.1');
        $userTest->setTotalQuizzesPassed(0);
        $userTest->setAverageScore('0.00');
        $userTest->setGamificationLevel($this->getReference(GamificationLevelFixtures::LEVEL_DEBUTANT, GamificationLevel::class));

        $manager->persist($userTest);
        $this->addReference(self::USER_TEST, $userTest);

        // User admin
        $userAdmin = new User();
        $userAdmin->setEmail('admin@gmail.com');
        $userAdmin->setFirstName('Admin');
        $userAdmin->setLastName('Civique');
        $userAdmin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $userAdmin->setPassword($this->passwordHasher->hashPassword($userAdmin, 'password'));
        $userAdmin->setIsActive(true);
        $userAdmin->setRegistrationIp('127.0.0.1');
        $userAdmin->setTotalQuizzesPassed(5);
        $userAdmin->setAverageScore('85.00');
        $userAdmin->setGamificationLevel($this->getReference(GamificationLevelFixtures::LEVEL_CITOYEN, GamificationLevel::class));

        $manager->persist($userAdmin);
        $this->addReference(self::USER_ADMIN, $userAdmin);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            GamificationLevelFixtures::class,
        ];
    }
}
