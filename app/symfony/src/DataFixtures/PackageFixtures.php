<?php

namespace App\DataFixtures;

use App\Entity\Package;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PackageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $packages = [
            [
                'name' => 'Essentiel',
                'description' => 'Abonnement 6 mois pour preparer sereinement votre citoyennete francaise',
                'price' => '12.00',
                'durationInMonths' => 6,
                'isPopular' => true,
                'features' => [
                    'Quiz illimites',
                    'Tous les themes officiels',
                    'Mode examen',
                    'Suivi de progression',
                    'Resultats detailles',
                    'Historique complet',
                ],
            ],
            [
                'name' => 'Intensif',
                'description' => 'Abonnement 3 mois pour une preparation acceleree',
                'price' => '9.00',
                'durationInMonths' => 3,
                'isPopular' => false,
                'features' => [
                    'Quiz illimites',
                    'Tous les themes officiels',
                    'Mode examen',
                    'Suivi de progression',
                    'Resultats detailles',
                ],
            ],
            [
                'name' => 'Annuel',
                'description' => 'Abonnement 12 mois - le meilleur rapport qualite/prix',
                'price' => '19.00',
                'durationInMonths' => 12,
                'isPopular' => false,
                'features' => [
                    'Quiz illimites',
                    'Tous les themes officiels',
                    'Mode examen',
                    'Suivi de progression',
                    'Resultats detailles',
                    'Historique complet',
                    'Support prioritaire',
                ],
            ],
        ];

        foreach ($packages as $packageData) {
            $package = new Package();
            $package->setName($packageData['name']);
            $package->setDescription($packageData['description']);
            $package->setPrice($packageData['price']);
            $package->setDurationInMonths($packageData['durationInMonths']);
            $package->setIsPopular($packageData['isPopular']);
            $package->setFeatures($packageData['features']);
            $package->setState(Package::STATE_ACTIVE);

            $manager->persist($package);
        }

        $manager->flush();
    }
}
