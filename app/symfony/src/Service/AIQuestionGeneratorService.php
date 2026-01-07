<?php

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\QuizConfiguration;
use App\Entity\Theme;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIQuestionGeneratorService
{
    private const THEME_TOPICS = [
        'Histoire de France' => [
            'Revolution francaise', 'Guerres mondiales', 'Ve Republique',
            'Rois de France', 'Napoleon', 'Colonisation et decolonisation'
        ],
        'Geographie' => [
            'Regions', 'Fleuves', 'Montagnes', 'DOM-TOM',
            'Pays frontaliers', 'Grandes villes'
        ],
        'Institutions' => [
            'President', 'Gouvernement', 'Parlement',
            'Conseil constitutionnel', 'Collectivites territoriales', 'Justice'
        ],
        'Valeurs de la Republique' => [
            'Liberte', 'Egalite', 'Fraternite', 'Laicite',
            'Droits de l\'Homme', 'Devise et symboles'
        ],
        'Symboles nationaux' => [
            'Drapeau', 'Hymne national', 'Marianne',
            'Coq gaulois', 'Fete nationale', 'Devise'
        ],
        'Culture et patrimoine' => [
            'Litterature', 'Arts', 'Monuments',
            'Gastronomie', 'Langue francaise', 'UNESCO'
        ],
        'Droits et devoirs' => [
            'Droit de vote', 'Service civique', 'Impots',
            'Respect des lois', 'Egalite homme-femme', 'Protection sociale'
        ],
        'Europe et international' => [
            'Union europeenne', 'ONU', 'OTAN',
            'Francophonie', 'Zone euro', 'Schengen'
        ],
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly ThemeRepository $themeRepository,
        private readonly string $anthropicApiKey,
    ) {
    }

    public function generateQuestionsForTheme(
        Theme $theme,
        int $count = 10,
        string $difficulty = Question::DIFFICULTY_MEDIUM
    ): array {
        $topics = self::THEME_TOPICS[$theme->getName()] ?? [];

        $difficultyInstructions = [
            'easy' => 'Questions simples avec des reponses evidentes pour les personnes ayant une connaissance de base.',
            'medium' => 'Questions de difficulte moyenne necessitant une bonne connaissance generale.',
            'hard' => 'Questions difficiles necessitant une connaissance approfondie du sujet.',
        ];

        $prompt = $this->buildPrompt($theme, $topics, $count, $difficulty, $difficultyInstructions[$difficulty]);

        $response = $this->callAnthropicApi($prompt);
        $questionsData = $this->parseResponse($response);

        return $this->createQuestionEntities($questionsData, $theme, $difficulty);
    }

    public function generateQuestionsForAllThemes(
        int $questionsPerTheme = 5,
        string $difficulty = Question::DIFFICULTY_MEDIUM
    ): array {
        $themes = $this->themeRepository->findBy(['isActive' => true]);
        $allQuestions = [];

        foreach ($themes as $theme) {
            $questions = $this->generateQuestionsForTheme($theme, $questionsPerTheme, $difficulty);
            $allQuestions = array_merge($allQuestions, $questions);
        }

        return $allQuestions;
    }

    public function createExamConfiguration(
        string $name,
        int $questionsCount = 40,
        int $timeLimitMinutes = 45
    ): QuizConfiguration {
        $config = new QuizConfiguration();
        $config->setName($name);
        $config->setDescription("Configuration d'examen officiel avec {$questionsCount} questions en {$timeLimitMinutes} minutes");
        $config->setQuestionsCount($questionsCount);
        $config->setMinimumCorrectAnswers((int) ceil($questionsCount * 0.75));
        $config->setMinimumScorePercent('75.00');
        $config->setTimeLimitMinutes($timeLimitMinutes);
        $config->setShuffleQuestions(true);
        $config->setShuffleAnswers(true);
        $config->setShowExplanationAfterAnswer(false);
        $config->setShowResultsAtEnd(true);
        $config->setAllowRetake(true);
        $config->setIsDefault(false);
        $config->setIsActive(true);

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $config;
    }

    public function saveQuestions(array $questions): int
    {
        $count = 0;
        foreach ($questions as $question) {
            $this->entityManager->persist($question);
            $count++;
        }
        $this->entityManager->flush();

        return $count;
    }

    private function buildPrompt(
        Theme $theme,
        array $topics,
        int $count,
        string $difficulty,
        string $difficultyInstruction
    ): string {
        $topicsStr = implode(', ', $topics);

        return <<<PROMPT
Tu es un expert en citoyennete francaise. Genere exactement {$count} questions QCM pour le theme "{$theme->getName()}".

Theme: {$theme->getName()}
Description: {$theme->getDescription()}
Sujets a couvrir: {$topicsStr}
Difficulte: {$difficulty} - {$difficultyInstruction}

IMPORTANT: Retourne UNIQUEMENT un JSON valide, sans texte avant ou apres.

Format JSON requis:
{
  "questions": [
    {
      "content": "La question complete",
      "explanation": "Explication pedagogique de la bonne reponse",
      "isMultipleChoice": false,
      "answers": [
        {"content": "Reponse A", "isCorrect": true},
        {"content": "Reponse B", "isCorrect": false},
        {"content": "Reponse C", "isCorrect": false},
        {"content": "Reponse D", "isCorrect": false}
      ]
    }
  ]
}

Regles:
- Chaque question doit avoir exactement 4 reponses
- Une seule reponse correcte par question (sauf si isMultipleChoice est true)
- Les questions doivent etre variees et couvrir differents sujets du theme
- Les reponses incorrectes doivent etre plausibles
- L'explication doit etre educative et aider a memoriser
- Evite les questions pieges ou ambigues
- Utilise un francais correct
PROMPT;
    }

    private function callAnthropicApi(string $prompt): string
    {
        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->anthropicApiKey,
                'anthropic-version' => '2023-06-01',
            ],
            'json' => [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 4096,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ],
        ]);

        $data = $response->toArray();

        return $data['content'][0]['text'] ?? '';
    }

    private function parseResponse(string $response): array
    {
        // Extract JSON from response
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $jsonStr = $matches[0];
        } else {
            $jsonStr = $response;
        }

        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse AI response: ' . json_last_error_msg());
        }

        return $data['questions'] ?? [];
    }

    private function createQuestionEntities(array $questionsData, Theme $theme, string $difficulty): array
    {
        $questions = [];

        foreach ($questionsData as $qData) {
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
