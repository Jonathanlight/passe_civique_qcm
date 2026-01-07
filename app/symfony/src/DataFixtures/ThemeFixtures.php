<?php

namespace App\DataFixtures;

use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ThemeFixtures extends Fixture
{
    public const THEME_VALEURS = 'theme-valeurs';
    public const THEME_HISTOIRE = 'theme-histoire';
    public const THEME_INSTITUTIONS = 'theme-institutions';
    public const THEME_GEOGRAPHIE = 'theme-geographie';
    public const THEME_SYMBOLES = 'theme-symboles';
    public const THEME_DROITS = 'theme-droits';
    public const THEME_VIE_QUOTIDIENNE = 'theme-vie-quotidienne';

    public function load(ObjectManager $manager): void
    {
        $themes = [
            [
                'name' => 'Valeurs de la République',
                'description' => 'Liberté, Égalité, Fraternité et les principes fondamentaux de la République française.',
                'icon' => 'balance-scale',
                'color' => '#0055A4',
                'displayOrder' => 1,
                'reference' => self::THEME_VALEURS,
            ],
            [
                'name' => 'Histoire de France',
                'description' => 'Les grandes dates et événements de l\'histoire française.',
                'icon' => 'landmark',
                'color' => '#EF4135',
                'displayOrder' => 2,
                'reference' => self::THEME_HISTOIRE,
            ],
            [
                'name' => 'Institutions françaises',
                'description' => 'Le fonctionnement de l\'État, le gouvernement, l\'Assemblée nationale et le Sénat.',
                'icon' => 'building-columns',
                'color' => '#0055A4',
                'displayOrder' => 3,
                'reference' => self::THEME_INSTITUTIONS,
            ],
            [
                'name' => 'Géographie de la France',
                'description' => 'Les régions, les territoires d\'outre-mer et la place de la France dans le monde.',
                'icon' => 'map',
                'color' => '#008000',
                'displayOrder' => 4,
                'reference' => self::THEME_GEOGRAPHIE,
            ],
            [
                'name' => 'Symboles de la République',
                'description' => 'Le drapeau, l\'hymne national, la devise et Marianne.',
                'icon' => 'flag',
                'color' => '#EF4135',
                'displayOrder' => 5,
                'reference' => self::THEME_SYMBOLES,
            ],
            [
                'name' => 'Droits et devoirs',
                'description' => 'Les droits fondamentaux et les devoirs des citoyens français.',
                'icon' => 'gavel',
                'color' => '#0055A4',
                'displayOrder' => 6,
                'reference' => self::THEME_DROITS,
            ],
            [
                'name' => 'Vie quotidienne en France',
                'description' => 'La culture, les traditions et les aspects pratiques de la vie en France.',
                'icon' => 'home',
                'color' => '#FFFFFF',
                'displayOrder' => 7,
                'reference' => self::THEME_VIE_QUOTIDIENNE,
            ],
        ];

        foreach ($themes as $themeData) {
            $theme = new Theme();
            $theme->setName($themeData['name']);
            $theme->setDescription($themeData['description']);
            $theme->setIcon($themeData['icon']);
            $theme->setColor($themeData['color']);
            $theme->setDisplayOrder($themeData['displayOrder']);
            $theme->setIsActive(true);

            $manager->persist($theme);
            $this->addReference($themeData['reference'], $theme);
        }

        $manager->flush();
    }
}