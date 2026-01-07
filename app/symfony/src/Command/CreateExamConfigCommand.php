<?php

namespace App\Command;

use App\Entity\QuizConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-exam-config',
    description: 'Create a quiz configuration in exam mode',
)]
class CreateExamConfigCommand extends Command
{
    private const EXAM_PRESETS = [
        'officiel' => [
            'name' => 'Examen Officiel',
            'description' => 'Configuration identique a l\'examen officiel de citoyennete francaise',
            'questionsCount' => 40,
            'timeLimitMinutes' => 45,
            'minimumScorePercent' => '75.00',
        ],
        'entrainement' => [
            'name' => 'Entrainement Standard',
            'description' => 'Session d\'entrainement avec 20 questions',
            'questionsCount' => 20,
            'timeLimitMinutes' => 25,
            'minimumScorePercent' => '60.00',
        ],
        'rapide' => [
            'name' => 'Quiz Rapide',
            'description' => 'Quiz rapide de 10 questions pour tester vos connaissances',
            'questionsCount' => 10,
            'timeLimitMinutes' => 10,
            'minimumScorePercent' => '50.00',
        ],
        'marathon' => [
            'name' => 'Marathon',
            'description' => 'Session intensive avec 60 questions pour une preparation complete',
            'questionsCount' => 60,
            'timeLimitMinutes' => 75,
            'minimumScorePercent' => '70.00',
        ],
        'difficile' => [
            'name' => 'Examen Difficile',
            'description' => 'Version difficile avec seuil de reussite eleve',
            'questionsCount' => 40,
            'timeLimitMinutes' => 30,
            'minimumScorePercent' => '85.00',
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('preset', InputArgument::OPTIONAL, 'Preset name: officiel, entrainement, rapide, marathon, difficile')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Custom configuration name')
            ->addOption('questions', null, InputOption::VALUE_REQUIRED, 'Number of questions', 40)
            ->addOption('time', 't', InputOption::VALUE_REQUIRED, 'Time limit in minutes', 45)
            ->addOption('score', 's', InputOption::VALUE_REQUIRED, 'Minimum score percent to pass', '75.00')
            ->addOption('no-timer', null, InputOption::VALUE_NONE, 'No time limit')
            ->addOption('show-explanation', null, InputOption::VALUE_NONE, 'Show explanation after each answer')
            ->addOption('default', null, InputOption::VALUE_NONE, 'Set as default configuration')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List available presets')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('list')) {
            $this->listPresets($io);
            return Command::SUCCESS;
        }

        $preset = $input->getArgument('preset');

        if ($preset && !isset(self::EXAM_PRESETS[$preset])) {
            $io->error(sprintf('Preset "%s" inconnu.', $preset));
            $this->listPresets($io);
            return Command::FAILURE;
        }

        $config = $this->createConfiguration($input, $preset);

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $io->success(sprintf('Configuration "%s" creee avec succes! (ID: %d)', $config->getName(), $config->getId()));

        $io->table(
            ['Parametre', 'Valeur'],
            [
                ['Nom', $config->getName()],
                ['Description', $config->getDescription()],
                ['Questions', $config->getQuestionsCount()],
                ['Temps limite', $config->getTimeLimitMinutes() ? $config->getTimeLimitMinutes() . ' min' : 'Illimite'],
                ['Score minimum', $config->getMinimumScorePercent() . '%'],
                ['Reponses min.', $config->getMinimumCorrectAnswers()],
                ['Explication apres reponse', $config->isShowExplanationAfterAnswer() ? 'Oui' : 'Non'],
                ['Par defaut', $config->isDefault() ? 'Oui' : 'Non'],
            ]
        );

        return Command::SUCCESS;
    }

    private function listPresets(SymfonyStyle $io): void
    {
        $io->title('Presets disponibles');

        $rows = [];
        foreach (self::EXAM_PRESETS as $key => $preset) {
            $rows[] = [
                $key,
                $preset['name'],
                $preset['questionsCount'],
                $preset['timeLimitMinutes'] . ' min',
                $preset['minimumScorePercent'] . '%',
            ];
        }

        $io->table(
            ['Preset', 'Nom', 'Questions', 'Temps', 'Score min.'],
            $rows
        );

        $io->info('Utilisez: php bin/console app:create-exam-config <preset>');
    }

    private function createConfiguration(InputInterface $input, ?string $preset): QuizConfiguration
    {
        $config = new QuizConfiguration();

        if ($preset) {
            $presetData = self::EXAM_PRESETS[$preset];
            $config->setName($input->getOption('name') ?? $presetData['name']);
            $config->setDescription($presetData['description']);
            $config->setQuestionsCount($presetData['questionsCount']);
            $config->setTimeLimitMinutes($presetData['timeLimitMinutes']);
            $config->setMinimumScorePercent($presetData['minimumScorePercent']);
        } else {
            $name = $input->getOption('name') ?? 'Configuration personnalisee';
            $questions = (int) $input->getOption('questions');
            $time = $input->getOption('no-timer') ? null : (int) $input->getOption('time');
            $score = $input->getOption('score');

            $config->setName($name);
            $config->setDescription(sprintf('Configuration avec %d questions', $questions));
            $config->setQuestionsCount($questions);
            $config->setTimeLimitMinutes($time);
            $config->setMinimumScorePercent($score);
        }

        $minimumCorrect = (int) ceil($config->getQuestionsCount() * ((float) $config->getMinimumScorePercent() / 100));
        $config->setMinimumCorrectAnswers($minimumCorrect);

        $config->setShuffleQuestions(true);
        $config->setShuffleAnswers(true);
        $config->setShowExplanationAfterAnswer($input->getOption('show-explanation'));
        $config->setShowResultsAtEnd(true);
        $config->setAllowRetake(true);
        $config->setIsDefault($input->getOption('default'));
        $config->setIsActive(true);

        return $config;
    }
}
