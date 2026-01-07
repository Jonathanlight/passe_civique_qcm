<?php

namespace App\Command;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Theme;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:generate-questions',
    description: 'Generate quiz questions using Claude AI',
)]
class GenerateQuestionsCommand extends Command
{
    private const THEME_TOPICS = [
        // Themes officiels du parcours d'integration republicaine
        'Devise et symboles de la République' => [
            'Devise Liberte Egalite Fraternite', 'Drapeau tricolore bleu blanc rouge',
            'La Marseillaise hymne national', 'Marianne symbole', 'Coq gaulois',
            '14 juillet fete nationale', 'Bonnet phrygien', 'Grand sceau de France'
        ],
        'Laïcité' => [
            'Separation des Eglises et de l\'Etat 1905', 'Neutralite religieuse',
            'Liberte de conscience', 'Liberte de culte', 'Ecole laique',
            'Signes religieux dans l\'espace public', 'Charte de la laicite'
        ],
        'Démocratie et droit de vote' => [
            'Suffrage universel', 'Elections presidentielles', 'Elections legislatives',
            'Elections municipales', 'Referendum', 'Droit de vote des femmes 1944',
            'Age du droit de vote', 'Inscription sur les listes electorales'
        ],
        'Organisation de la République' => [
            'President de la Republique', 'Premier ministre', 'Gouvernement',
            'Assemblee nationale', 'Senat', 'Conseil constitutionnel',
            'Conseil d\'Etat', 'Regions et departements', 'Communes et maires'
        ],
        'Droits fondamentaux' => [
            'Declaration des droits de l\'Homme 1789', 'Liberte d\'expression',
            'Liberte de la presse', 'Droit a la vie privee', 'Egalite homme-femme',
            'Droits des enfants', 'Droit au respect', 'Non-discrimination'
        ],
        'Obligations et devoirs' => [
            'Respect des lois', 'Paiement des impots', 'Defense nationale',
            'Scolarisation obligatoire', 'Respect d\'autrui', 'Devoir de vote',
            'Protection de l\'environnement', 'Solidarite nationale'
        ],
        'Histoire de France' => [
            'Revolution francaise 1789', 'Napoleon Bonaparte', 'Guerres mondiales',
            'Resistance et Liberation', 'Ve Republique 1958', 'Mai 68',
            'Construction europeenne', 'Abolition de l\'esclavage'
        ],
        'Géographie de la France' => [
            'Regions de France', 'DOM-TOM outre-mer', 'Fleuves Seine Loire Rhone',
            'Montagnes Alpes Pyrenees', 'Frontieres et pays voisins',
            'Grandes villes Paris Lyon Marseille', 'Population francaise', 'Superficie'
        ],
        'Culture française' => [
            'Ecrivains Victor Hugo Moliere', 'Peintres Monet Renoir',
            'Monuments Tour Eiffel Louvre', 'Gastronomie francaise',
            'Cinema francais', 'Musique francaise', 'Langue francaise Academie',
            'Fetes traditionnelles', 'Sites UNESCO'
        ],
        'Démarches administratives' => [
            'Carte d\'identite', 'Passeport', 'Permis de conduire',
            'Etat civil mairie', 'Declaration d\'impots', 'Carte Vitale',
            'Service-public.fr', 'Prefecture', 'Acte de naissance'
        ],
        'Santé' => [
            'Securite sociale', 'Carte Vitale', 'Medecin traitant',
            'Hopitaux publics', 'Urgences SAMU 15', 'Mutuelle complementaire',
            'Vaccination', 'CMU couverture maladie universelle'
        ],
        'Emploi' => [
            'Contrat de travail CDI CDD', 'SMIC salaire minimum',
            'Pole emploi France Travail', 'Conges payes', 'Code du travail',
            'Syndicats', 'Formation professionnelle', 'Retraite', 'Chomage'
        ],
        'Parentalité et éducation' => [
            'Ecole obligatoire 3-16 ans', 'Ecole maternelle primaire',
            'College lycee', 'Autorite parentale', 'Allocations familiales CAF',
            'Conge maternite paternite', 'Protection de l\'enfance', 'Droits des parents'
        ],
        'Union Européenne' => [
            'Traite de Rome 1957', 'Institutions europeennes', 'Parlement europeen',
            'Commission europeenne', 'Zone euro monnaie', 'Espace Schengen',
            'Citoyennete europeenne', 'Drapeau europeen', 'Hymne a la joie'
        ],
        // Legacy themes (for compatibility)
        'Valeurs de la République' => [
            'Liberte', 'Egalite', 'Fraternite', 'Laicite',
            'Droits de l\'Homme', 'Devise et symboles', 'Democratie', 'Citoyennete'
        ],
        'Institutions françaises' => [
            'President', 'Gouvernement', 'Parlement', 'Senat', 'Assemblee nationale',
            'Conseil constitutionnel', 'Collectivites territoriales', 'Justice', 'Elections'
        ],
        'Symboles de la République' => [
            'Drapeau tricolore', 'Hymne national La Marseillaise', 'Marianne',
            'Coq gaulois', '14 juillet', 'Devise republicaine', 'Pantheon'
        ],
        'Vie quotidienne en France' => [
            'Demarches administratives', 'Sante', 'Emploi', 'Education',
            'Transport', 'Logement', 'Services publics', 'Vie associative'
        ],
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly ThemeRepository $themeRepository,
        private readonly ?string $anthropicApiKey = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('theme', InputArgument::OPTIONAL, 'Theme name or ID (leave empty for all themes)')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of questions per theme', 10)
            ->addOption('difficulty', 'd', InputOption::VALUE_REQUIRED, 'Difficulty level (easy, medium, hard)', 'medium')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview without saving to database')
            ->addOption('api-key', 'k', InputOption::VALUE_REQUIRED, 'Anthropic API key (or set ANTHROPIC_API_KEY env var)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $apiKey = $input->getOption('api-key') ?? $this->anthropicApiKey ?? $_ENV['ANTHROPIC_API_KEY'] ?? null;

        if (!$apiKey) {
            $io->error('API key is required. Set ANTHROPIC_API_KEY env var or use --api-key option.');
            return Command::FAILURE;
        }

        $count = (int) $input->getOption('count');
        $difficulty = $input->getOption('difficulty');
        $dryRun = $input->getOption('dry-run');
        $themeArg = $input->getArgument('theme');

        if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
            $io->error('Invalid difficulty. Use: easy, medium, or hard');
            return Command::FAILURE;
        }

        // Get themes to process
        $themes = $this->getThemesToProcess($themeArg, $io);
        if (empty($themes)) {
            return Command::FAILURE;
        }

        $io->title('Generation de questions pour le quiz citoyennete');
        $io->info(sprintf('Themes: %d | Questions par theme: %d | Difficulte: %s', count($themes), $count, $difficulty));

        $totalQuestions = 0;

        foreach ($themes as $theme) {
            $io->section(sprintf('Theme: %s', $theme->getName()));

            try {
                $questions = $this->generateQuestionsForTheme($theme, $count, $difficulty, $apiKey, $io);

                if (!$dryRun) {
                    foreach ($questions as $question) {
                        $this->entityManager->persist($question);
                    }
                    $this->entityManager->flush();
                    $io->success(sprintf('%d questions creees pour "%s"', count($questions), $theme->getName()));
                } else {
                    $io->info(sprintf('[DRY-RUN] %d questions generees pour "%s"', count($questions), $theme->getName()));
                    foreach ($questions as $q) {
                        $io->text('  - ' . mb_substr($q->getContent(), 0, 80) . '...');
                    }
                }

                $totalQuestions += count($questions);

            } catch (\Exception $e) {
                $io->error(sprintf('Erreur pour le theme "%s": %s', $theme->getName(), $e->getMessage()));
            }
        }

        $io->newLine();
        if ($dryRun) {
            $io->success(sprintf('[DRY-RUN] Total: %d questions generees (non sauvegardees)', $totalQuestions));
        } else {
            $io->success(sprintf('Total: %d questions creees avec succes!', $totalQuestions));
        }

        return Command::SUCCESS;
    }

    private function getThemesToProcess(?string $themeArg, SymfonyStyle $io): array
    {
        if ($themeArg === null) {
            $themes = $this->themeRepository->findBy(['isActive' => true]);
            if (empty($themes)) {
                $io->error('Aucun theme actif trouve. Creez d\'abord des themes.');
                return [];
            }
            return $themes;
        }

        // Try to find by ID
        if (is_numeric($themeArg)) {
            $theme = $this->themeRepository->find((int) $themeArg);
        } else {
            $theme = $this->themeRepository->findOneBy(['name' => $themeArg]);
        }

        if (!$theme) {
            $io->error(sprintf('Theme "%s" non trouve.', $themeArg));
            $availableThemes = $this->themeRepository->findBy(['isActive' => true]);
            $io->info('Themes disponibles:');
            foreach ($availableThemes as $t) {
                $io->text(sprintf('  [%d] %s', $t->getId(), $t->getName()));
            }
            return [];
        }

        return [$theme];
    }

    private function generateQuestionsForTheme(
        Theme $theme,
        int $count,
        string $difficulty,
        string $apiKey,
        SymfonyStyle $io
    ): array {
        $topics = self::THEME_TOPICS[$theme->getName()] ?? [];
        $topicsStr = implode(', ', $topics);

        $difficultyInstructions = [
            'easy' => 'Questions simples avec des reponses evidentes.',
            'medium' => 'Questions de difficulte moyenne necessitant une bonne connaissance generale.',
            'hard' => 'Questions difficiles necessitant une connaissance approfondie.',
        ];

        $prompt = <<<PROMPT
Tu es un expert en citoyennete francaise. Genere exactement {$count} questions QCM pour le theme "{$theme->getName()}".

Theme: {$theme->getName()}
Description: {$theme->getDescription()}
Sujets a couvrir: {$topicsStr}
Difficulte: {$difficulty} - {$difficultyInstructions[$difficulty]}

IMPORTANT: Retourne UNIQUEMENT un JSON valide, sans texte avant ou apres.

Format JSON requis:
{
  "questions": [
    {
      "content": "La question complete?",
      "explanation": "Explication pedagogique de la bonne reponse",
      "isMultipleChoice": false,
      "answers": [
        {"content": "Reponse correcte", "isCorrect": true},
        {"content": "Reponse incorrecte 1", "isCorrect": false},
        {"content": "Reponse incorrecte 2", "isCorrect": false},
        {"content": "Reponse incorrecte 3", "isCorrect": false}
      ]
    }
  ]
}

Regles importantes:
- Chaque question doit avoir exactement 4 reponses
- Une seule reponse correcte par question
- Questions variees couvrant differents sujets du theme
- Reponses incorrectes plausibles mais clairement fausses
- Explications educatives et memorables
- Francais correct, pas de questions pieges
- Questions pertinentes pour l'examen de citoyennete francaise
PROMPT;

        $io->text('Appel API Claude...');

        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ],
            'json' => [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 4096,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $data = $response->toArray();
        $responseText = $data['content'][0]['text'] ?? '';

        // Extract JSON
        if (preg_match('/\{[\s\S]*\}/', $responseText, $matches)) {
            $jsonStr = $matches[0];
        } else {
            $jsonStr = $responseText;
        }

        $questionsData = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Erreur parsing JSON: ' . json_last_error_msg());
        }

        $questions = [];
        foreach ($questionsData['questions'] ?? [] as $qData) {
            $question = new Question();
            $question->setContent($qData['content']);
            $question->setTheme($theme);
            $question->setDifficulty($difficulty);
            $question->setExplanation($qData['explanation'] ?? null);
            $question->setIsMultipleChoice($qData['isMultipleChoice'] ?? false);
            $question->setIsActive(true);

            $displayOrder = 0;
            foreach ($qData['answers'] ?? [] as $aData) {
                $answer = new Answer();
                $answer->setContent($aData['content']);
                $answer->setIsCorrect($aData['isCorrect'] ?? false);
                $answer->setDisplayOrder($displayOrder++);
                $question->addAnswer($answer);
            }

            $questions[] = $question;
        }

        return $questions;
    }
}
