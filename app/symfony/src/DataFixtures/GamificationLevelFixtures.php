<?php

namespace App\DataFixtures;

use App\Entity\GamificationLevel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GamificationLevelFixtures extends Fixture
{
    public const LEVEL_DEBUTANT = 'level-debutant';
    public const LEVEL_APPRENTI = 'level-apprenti';
    public const LEVEL_CITOYEN = 'level-citoyen';
    public const LEVEL_PATRIOTE = 'level-patriote';
    public const LEVEL_EXPERT = 'level-expert';
    public const LEVEL_MARIANNE = 'level-marianne';

    public function load(ObjectManager $manager): void
    {
        $levels = [
            [
                'name' => 'Petit Croissant',
                'emoji' => '🥐',
                'description' => 'Tu viens d\'arriver ! Bienvenue dans l\'aventure citoyenne !',
                'minQuizzesPassed' => 0,
                'minAverageScore' => 0,
                'displayOrder' => 1,
                'badgeColor' => '#f5deb3',
                'reference' => self::LEVEL_DEBUTANT,
            ],
            [
                'name' => 'Baguette Curieuse',
                'emoji' => '🥖',
                'description' => 'Tu commences à explorer les valeurs de la République !',
                'minQuizzesPassed' => 1,
                'minAverageScore' => 50,
                'displayOrder' => 2,
                'badgeColor' => '#d4a574',
                'reference' => self::LEVEL_APPRENTI,
            ],
            [
                'name' => 'Coq Républicain',
                'emoji' => '🐓',
                'description' => 'Tu maîtrises les bases de la citoyenneté française !',
                'minQuizzesPassed' => 5,
                'minAverageScore' => 65,
                'displayOrder' => 3,
                'badgeColor' => '#cd5c5c',
                'reference' => self::LEVEL_CITOYEN,
            ],
            [
                'name' => 'Béret Tricolore',
                'emoji' => '🎨',
                'description' => 'Liberté, Égalité, Fraternité n\'ont plus de secrets pour toi !',
                'minQuizzesPassed' => 10,
                'minAverageScore' => 75,
                'displayOrder' => 4,
                'badgeColor' => '#4169e1',
                'reference' => self::LEVEL_PATRIOTE,
            ],
            [
                'name' => 'Tour Eiffel',
                'emoji' => '🗼',
                'description' => 'Tu domines la culture civique française de haut !',
                'minQuizzesPassed' => 20,
                'minAverageScore' => 85,
                'displayOrder' => 5,
                'badgeColor' => '#ffd700',
                'reference' => self::LEVEL_EXPERT,
            ],
            [
                'name' => 'Marianne Suprême',
                'emoji' => '👸',
                'description' => 'Tu es l\'incarnation de la République ! Bravo !',
                'minQuizzesPassed' => 50,
                'minAverageScore' => 90,
                'displayOrder' => 6,
                'badgeColor' => '#9400d3',
                'reference' => self::LEVEL_MARIANNE,
            ],
        ];

        foreach ($levels as $levelData) {
            $level = new GamificationLevel();
            $level->setName($levelData['name']);
            $level->setEmoji($levelData['emoji']);
            $level->setDescription($levelData['description']);
            $level->setMinQuizzesPassed($levelData['minQuizzesPassed']);
            $level->setMinAverageScore($levelData['minAverageScore']);
            $level->setDisplayOrder($levelData['displayOrder']);
            $level->setBadgeColor($levelData['badgeColor']);
            $level->setIsActive(true);

            $manager->persist($level);
            $this->addReference($levelData['reference'], $level);
        }

        $manager->flush();
    }
}