<?php

namespace App\Command;

use App\Entity\Theme;
use App\Repository\ThemeRepository;
use App\Repository\QuizConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:setup-exam',
    description: 'Setup complete exam database with themes, questions, and configurations',
)]
class SetupExamDatabaseCommand extends Command
{
    private const DEFAULT_THEMES = [
        [
            'name' => 'Histoire de France',
            'description' => 'Les grandes dates et evenements de l\'histoire francaise',
            'icon' => 'fa-landmark',
            'color' => '#0055A4',
            'displayOrder' => 1,
        ],
        [
            'name' => 'Geographie',
            'description' => 'Le territoire francais et ses caracteristiques',
            'icon' => 'fa-map',
            'color' => '#2ECC71',
            'displayOrder' => 2,
        ],
        [
            'name' => 'Institutions',
            'description' => 'Le fonctionnement de l\'Etat et des institutions',
            'icon' => 'fa-building-columns',
            'color' => '#9B59B6',
            'displayOrder' => 3,
        ],
        [
            'name' => 'Valeurs de la Republique',
            'description' => 'Les principes fondamentaux de la Republique francaise',
            'icon' => 'fa-scale-balanced',
            'color' => '#E74C3C',
            'displayOrder' => 4,
        ],
        [
            'name' => 'Symboles nationaux',
            'description' => 'Les symboles et emblemes de la France',
            'icon' => 'fa-flag',
            'color' => '#F39C12',
            'displayOrder' => 5,
        ],
        [
            'name' => 'Culture et patrimoine',
            'description' => 'La culture, les arts et le patrimoine francais',
            'icon' => 'fa-palette',
            'color' => '#1ABC9C',
            'displayOrder' => 6,
        ],
        [
            'name' => 'Droits et devoirs',
            'description' => 'Les droits et obligations des citoyens',
            'icon' => 'fa-gavel',
            'color' => '#34495E',
            'displayOrder' => 7,
        ],
        [
            'name' => 'Europe et international',
            'description' => 'La France dans l\'Europe et le monde',
            'icon' => 'fa-globe',
            'color' => '#3498DB',
            'displayOrder' => 8,
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ThemeRepository $themeRepository,
        private readonly QuizConfigurationRepository $configRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('questions-per-theme', null, InputOption::VALUE_REQUIRED, 'Questions to generate per theme', 10)
            ->addOption('skip-themes', null, InputOption::VALUE_NONE, 'Skip theme creation')
            ->addOption('skip-questions', null, InputOption::VALUE_NONE, 'Skip question generation')
            ->addOption('skip-configs', null, InputOption::VALUE_NONE, 'Skip configuration creation')
            ->addOption('api-key', 'k', InputOption::VALUE_REQUIRED, 'Anthropic API key')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force recreation of existing data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Configuration complete de la base de donnees examen');

        $force = $input->getOption('force');

        // Step 1: Create themes
        if (!$input->getOption('skip-themes')) {
            $this->createThemes($io, $force);
        }

        // Step 2: Create configurations
        if (!$input->getOption('skip-configs')) {
            $this->createConfigurations($io, $force);
        }

        // Step 3: Generate questions
        if (!$input->getOption('skip-questions')) {
            $apiKey = $input->getOption('api-key') ?? $_ENV['ANTHROPIC_API_KEY'] ?? null;

            if (!$apiKey) {
                $io->warning('API key non fournie. Utilisez --api-key ou definissez ANTHROPIC_API_KEY.');
                $io->info('Les questions n\'ont pas ete generees. Utilisez la commande app:generate-questions separement.');
            } else {
                $questionsPerTheme = (int) $input->getOption('questions-per-theme');
                $this->generateQuestions($input, $output, $questionsPerTheme, $apiKey);
            }
        }

        $io->success('Configuration terminee!');

        // Show summary
        $this->showSummary($io);

        return Command::SUCCESS;
    }

    private function createThemes(SymfonyStyle $io, bool $force): void
    {
        $io->section('Creation des themes');

        $existingThemes = $this->themeRepository->findAll();

        if (!empty($existingThemes) && !$force) {
            $io->info(sprintf('%d themes existent deja. Utilisez --force pour recreer.', count($existingThemes)));
            return;
        }

        if ($force && !empty($existingThemes)) {
            $io->warning('Suppression des themes existants...');
            foreach ($existingThemes as $theme) {
                $this->entityManager->remove($theme);
            }
            $this->entityManager->flush();
        }

        foreach (self::DEFAULT_THEMES as $themeData) {
            $theme = new Theme();
            $theme->setName($themeData['name']);
            $theme->setDescription($themeData['description']);
            $theme->setIcon($themeData['icon']);
            $theme->setColor($themeData['color']);
            $theme->setDisplayOrder($themeData['displayOrder']);
            $theme->setIsActive(true);

            $this->entityManager->persist($theme);
            $io->text(sprintf('  + %s', $themeData['name']));
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d themes crees', count(self::DEFAULT_THEMES)));
    }

    private function createConfigurations(SymfonyStyle $io, bool $force): void
    {
        $io->section('Creation des configurations d\'examen');

        $existingConfigs = $this->configRepository->findAll();

        if (!empty($existingConfigs) && !$force) {
            $io->info(sprintf('%d configurations existent deja. Utilisez --force pour recreer.', count($existingConfigs)));
            return;
        }

        $command = $this->getApplication()->find('app:create-exam-config');

        $presets = ['officiel', 'entrainement', 'rapide'];

        foreach ($presets as $index => $preset) {
            $arguments = [
                'preset' => $preset,
            ];

            if ($preset === 'officiel') {
                $arguments['--default'] = true;
            }

            $greetInput = new ArrayInput($arguments);
            $command->run($greetInput, $output = new \Symfony\Component\Console\Output\BufferedOutput());
        }

        $io->success('Configurations d\'examen creees');
    }

    private function generateQuestions(
        InputInterface $input,
        OutputInterface $output,
        int $questionsPerTheme,
        string $apiKey
    ): void {
        $io = new SymfonyStyle($input, $output);
        $io->section('Generation des questions avec Claude AI');

        $command = $this->getApplication()->find('app:generate-questions');

        $arguments = [
            '--count' => $questionsPerTheme,
            '--difficulty' => 'medium',
            '--api-key' => $apiKey,
        ];

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
    }

    private function showSummary(SymfonyStyle $io): void
    {
        $io->section('Resume');

        $themes = $this->themeRepository->findBy(['isActive' => true]);
        $configs = $this->configRepository->findBy(['isActive' => true]);

        $totalQuestions = 0;
        foreach ($themes as $theme) {
            $totalQuestions += $theme->getActiveQuestionsCount();
        }

        $io->table(
            ['Element', 'Nombre'],
            [
                ['Themes actifs', count($themes)],
                ['Questions totales', $totalQuestions],
                ['Configurations', count($configs)],
            ]
        );

        $io->info('Commandes utiles:');
        $io->listing([
            'php bin/console app:generate-questions --help  # Generer plus de questions',
            'php bin/console app:create-exam-config --list  # Voir les presets',
            'php bin/console app:setup-exam --force        # Reinitialiser tout',
        ]);
    }
}
