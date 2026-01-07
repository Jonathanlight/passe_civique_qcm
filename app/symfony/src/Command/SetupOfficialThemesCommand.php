<?php

namespace App\Command;

use App\Entity\Theme;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:setup-official-themes',
    description: 'Setup official French citizenship exam themes from Ministry of Interior',
)]
class SetupOfficialThemesCommand extends Command
{
    private const OFFICIAL_THEMES = [
        // Principes et valeurs de la République
        [
            'name' => 'Devise et symboles de la République',
            'description' => 'La devise Liberté, Égalité, Fraternité et les symboles nationaux: drapeau tricolore, Marianne, La Marseillaise, le coq gaulois, le 14 juillet',
            'icon' => 'fa-flag',
            'color' => '#0055A4',
            'displayOrder' => 1,
        ],
        [
            'name' => 'Laïcité',
            'description' => 'Le principe de laïcité, la séparation des Églises et de l\'État, la neutralité religieuse, la liberté de conscience',
            'icon' => 'fa-scale-balanced',
            'color' => '#9B59B6',
            'displayOrder' => 2,
        ],
        // Système institutionnel et politique
        [
            'name' => 'Démocratie et droit de vote',
            'description' => 'Le système démocratique français, le suffrage universel, les élections, la participation citoyenne',
            'icon' => 'fa-check-to-slot',
            'color' => '#E74C3C',
            'displayOrder' => 3,
        ],
        [
            'name' => 'Organisation de la République',
            'description' => 'Le Président, le Gouvernement, le Parlement, les collectivités territoriales, la justice',
            'icon' => 'fa-building-columns',
            'color' => '#3498DB',
            'displayOrder' => 4,
        ],
        // Droits et devoirs
        [
            'name' => 'Droits fondamentaux',
            'description' => 'Les droits de l\'Homme, les libertés fondamentales, l\'égalité homme-femme, les droits des enfants',
            'icon' => 'fa-hand-fist',
            'color' => '#2ECC71',
            'displayOrder' => 5,
        ],
        [
            'name' => 'Obligations et devoirs',
            'description' => 'Le respect des lois, le paiement des impôts, le devoir de défense, la scolarisation obligatoire',
            'icon' => 'fa-gavel',
            'color' => '#34495E',
            'displayOrder' => 6,
        ],
        // Histoire, géographie et culture
        [
            'name' => 'Histoire de France',
            'description' => 'Les grandes dates de l\'histoire française: Révolution, République, guerres mondiales, construction européenne',
            'icon' => 'fa-landmark',
            'color' => '#8E44AD',
            'displayOrder' => 7,
        ],
        [
            'name' => 'Géographie de la France',
            'description' => 'Le territoire français, les régions, les DOM-TOM, les frontières, les grandes villes, la population',
            'icon' => 'fa-map',
            'color' => '#1ABC9C',
            'displayOrder' => 8,
        ],
        [
            'name' => 'Culture française',
            'description' => 'La littérature, les arts, le patrimoine, la gastronomie, la langue française, les personnalités célèbres',
            'icon' => 'fa-palette',
            'color' => '#F39C12',
            'displayOrder' => 9,
        ],
        // Vivre dans la société française
        [
            'name' => 'Démarches administratives',
            'description' => 'Les documents d\'identité, l\'état civil, les services publics, les impôts, la sécurité sociale',
            'icon' => 'fa-file-lines',
            'color' => '#16A085',
            'displayOrder' => 10,
        ],
        [
            'name' => 'Santé',
            'description' => 'Le système de santé français, la sécurité sociale, l\'assurance maladie, les droits des patients',
            'icon' => 'fa-heart-pulse',
            'color' => '#E91E63',
            'displayOrder' => 11,
        ],
        [
            'name' => 'Emploi',
            'description' => 'Le droit du travail, le contrat de travail, Pôle emploi, la formation professionnelle, les syndicats',
            'icon' => 'fa-briefcase',
            'color' => '#FF9800',
            'displayOrder' => 12,
        ],
        [
            'name' => 'Parentalité et éducation',
            'description' => 'L\'école obligatoire, l\'autorité parentale, les droits des enfants, les allocations familiales, la CAF',
            'icon' => 'fa-children',
            'color' => '#00BCD4',
            'displayOrder' => 13,
        ],
        // Europe
        [
            'name' => 'Union Européenne',
            'description' => 'La construction européenne, les institutions de l\'UE, l\'espace Schengen, la zone euro, la citoyenneté européenne',
            'icon' => 'fa-globe-europe',
            'color' => '#003399',
            'displayOrder' => 14,
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ThemeRepository $themeRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force recreation of themes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Configuration des themes officiels du parcours d\'integration republicaine');

        $force = $input->getOption('force');
        $created = 0;
        $skipped = 0;

        foreach (self::OFFICIAL_THEMES as $themeData) {
            $existing = $this->themeRepository->findOneBy(['name' => $themeData['name']]);

            if ($existing && !$force) {
                $io->text(sprintf('  [SKIP] %s (existe deja)', $themeData['name']));
                $skipped++;
                continue;
            }

            if ($existing && $force) {
                $this->entityManager->remove($existing);
                $this->entityManager->flush();
            }

            $theme = new Theme();
            $theme->setName($themeData['name']);
            $theme->setDescription($themeData['description']);
            $theme->setIcon($themeData['icon']);
            $theme->setColor($themeData['color']);
            $theme->setDisplayOrder($themeData['displayOrder']);
            $theme->setIsActive(true);

            $this->entityManager->persist($theme);
            $io->text(sprintf('  [OK] %s', $themeData['name']));
            $created++;
        }

        $this->entityManager->flush();

        $io->newLine();
        $io->success(sprintf('%d themes crees, %d ignores', $created, $skipped));

        return Command::SUCCESS;
    }
}
