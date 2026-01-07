<?php

namespace App\DataFixtures;

use App\Entity\QuizConfiguration;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class QuizConfigurationFixtures extends Fixture
{
    public const CONFIG_DEFAULT = 'config-default';
    public const CONFIG_ENTRAINEMENT = 'config-entrainement';
    public const CONFIG_EXAMEN = 'config-examen';

    public function load(ObjectManager $manager): void
    {
        $configs = [
            [
                'name' => 'Test Standard',
                'description' => 'Le test officiel avec 40 questions et un minimum de 30 bonnes réponses requises.',
                'questionsCount' => 40,
                'minimumCorrectAnswers' => 30,
                'minimumScorePercent' => '75.00',
                'timeLimitMinutes' => null,
                'shuffleQuestions' => true,
                'shuffleAnswers' => true,
                'showExplanationAfterAnswer' => true,
                'showResultsAtEnd' => true,
                'allowRetake' => true,
                'isDefault' => true,
                'reference' => self::CONFIG_DEFAULT,
            ],
            [
                'name' => 'Mode Entraînement',
                'description' => 'Un test court de 10 questions pour s\'entraîner rapidement.',
                'questionsCount' => 10,
                'minimumCorrectAnswers' => 7,
                'minimumScorePercent' => '70.00',
                'timeLimitMinutes' => null,
                'shuffleQuestions' => true,
                'shuffleAnswers' => true,
                'showExplanationAfterAnswer' => true,
                'showResultsAtEnd' => true,
                'allowRetake' => true,
                'isDefault' => false,
                'reference' => self::CONFIG_ENTRAINEMENT,
            ],
            [
                'name' => 'Mode Examen',
                'description' => 'Conditions réelles d\'examen avec limite de temps.',
                'questionsCount' => 40,
                'minimumCorrectAnswers' => 30,
                'minimumScorePercent' => '75.00',
                'timeLimitMinutes' => 45,
                'shuffleQuestions' => true,
                'shuffleAnswers' => true,
                'showExplanationAfterAnswer' => false,
                'showResultsAtEnd' => true,
                'allowRetake' => true,
                'isDefault' => false,
                'reference' => self::CONFIG_EXAMEN,
            ],
        ];

        foreach ($configs as $configData) {
            $config = new QuizConfiguration();
            $config->setName($configData['name']);
            $config->setDescription($configData['description']);
            $config->setQuestionsCount($configData['questionsCount']);
            $config->setMinimumCorrectAnswers($configData['minimumCorrectAnswers']);
            $config->setMinimumScorePercent($configData['minimumScorePercent']);
            $config->setTimeLimitMinutes($configData['timeLimitMinutes']);
            $config->setShuffleQuestions($configData['shuffleQuestions']);
            $config->setShuffleAnswers($configData['shuffleAnswers']);
            $config->setShowExplanationAfterAnswer($configData['showExplanationAfterAnswer']);
            $config->setShowResultsAtEnd($configData['showResultsAtEnd']);
            $config->setAllowRetake($configData['allowRetake']);
            $config->setIsDefault($configData['isDefault']);
            $config->setIsActive(true);

            $manager->persist($config);
            $this->addReference($configData['reference'], $config);
        }

        $manager->flush();
    }
}