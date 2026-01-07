<?php

namespace App\DataFixtures;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class QuestionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $questions = $this->getQuestionsData();

        foreach ($questions as $questionData) {
            /** @var Theme $theme */
            $theme = $this->getReference($questionData['theme'], Theme::class);

            $question = new Question();
            $question->setContent($questionData['content']);
            $question->setTheme($theme);
            $question->setIsMultipleChoice($questionData['isMultiple']);
            $question->setExplanation($questionData['explanation'] ?? null);
            $question->setDifficulty($questionData['difficulty'] ?? Question::DIFFICULTY_MEDIUM);
            $question->setIsActive(true);

            foreach ($questionData['answers'] as $index => $answerData) {
                $answer = new Answer();
                $answer->setContent($answerData['content']);
                $answer->setIsCorrect($answerData['isCorrect']);
                $answer->setDisplayOrder($index + 1);
                $question->addAnswer($answer);
            }

            $manager->persist($question);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ThemeFixtures::class,
        ];
    }

    private function getQuestionsData(): array
    {
        return [
            // VALEURS DE LA RÉPUBLIQUE
            [
                'theme' => ThemeFixtures::THEME_VALEURS,
                'content' => 'Quelle est la devise de la République française ?',
                'isMultiple' => false,
                'explanation' => 'La devise "Liberté, Égalité, Fraternité" est inscrite dans la Constitution de 1958.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Liberté, Égalité, Fraternité', 'isCorrect' => true],
                    ['content' => 'Liberté, Justice, Solidarité', 'isCorrect' => false],
                    ['content' => 'Unité, Égalité, Fraternité', 'isCorrect' => false],
                    ['content' => 'Liberté, Égalité, Solidarité', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_VALEURS,
                'content' => 'Quel principe signifie que l\'État ne favorise aucune religion ?',
                'isMultiple' => false,
                'explanation' => 'La laïcité garantit la liberté de conscience et la neutralité de l\'État face aux religions.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'La laïcité', 'isCorrect' => true],
                    ['content' => 'La neutralité', 'isCorrect' => false],
                    ['content' => 'L\'athéisme', 'isCorrect' => false],
                    ['content' => 'Le sécularisme', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_VALEURS,
                'content' => 'Quelles sont les valeurs fondamentales de la République française ?',
                'isMultiple' => true,
                'explanation' => 'La liberté, l\'égalité et la fraternité sont les trois valeurs inscrites dans la devise.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'Liberté', 'isCorrect' => true],
                    ['content' => 'Égalité', 'isCorrect' => true],
                    ['content' => 'Fraternité', 'isCorrect' => true],
                    ['content' => 'Monarchie', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_VALEURS,
                'content' => 'En quelle année la loi de séparation des Églises et de l\'État a-t-elle été adoptée ?',
                'isMultiple' => false,
                'explanation' => 'La loi de 1905 établit la laïcité en France en séparant les Églises et l\'État.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => '1905', 'isCorrect' => true],
                    ['content' => '1789', 'isCorrect' => false],
                    ['content' => '1848', 'isCorrect' => false],
                    ['content' => '1958', 'isCorrect' => false],
                ],
            ],
            // HISTOIRE DE FRANCE
            [
                'theme' => ThemeFixtures::THEME_HISTOIRE,
                'content' => 'En quelle année a eu lieu la Révolution française ?',
                'isMultiple' => false,
                'explanation' => 'La Révolution française a débuté en 1789 avec la prise de la Bastille le 14 juillet.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => '1789', 'isCorrect' => true],
                    ['content' => '1792', 'isCorrect' => false],
                    ['content' => '1799', 'isCorrect' => false],
                    ['content' => '1804', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_HISTOIRE,
                'content' => 'Quel événement est célébré le 14 juillet ?',
                'isMultiple' => false,
                'explanation' => 'Le 14 juillet 1789, la prise de la Bastille marque le début symbolique de la Révolution.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'La prise de la Bastille', 'isCorrect' => true],
                    ['content' => 'La fête de la Fédération', 'isCorrect' => false],
                    ['content' => 'La fin de la monarchie', 'isCorrect' => false],
                    ['content' => 'La victoire de 1918', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_HISTOIRE,
                'content' => 'Qui était le roi de France au moment de la Révolution ?',
                'isMultiple' => false,
                'explanation' => 'Louis XVI régnait depuis 1774 et fut guillotiné en 1793.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'Louis XVI', 'isCorrect' => true],
                    ['content' => 'Louis XIV', 'isCorrect' => false],
                    ['content' => 'Louis XV', 'isCorrect' => false],
                    ['content' => 'Napoléon Bonaparte', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_HISTOIRE,
                'content' => 'En quelle année la Ve République a-t-elle été fondée ?',
                'isMultiple' => false,
                'explanation' => 'La Constitution de la Ve République a été adoptée en 1958 sous Charles de Gaulle.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => '1958', 'isCorrect' => true],
                    ['content' => '1946', 'isCorrect' => false],
                    ['content' => '1962', 'isCorrect' => false],
                    ['content' => '1969', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_HISTOIRE,
                'content' => 'Quel général a fondé la Ve République ?',
                'isMultiple' => false,
                'explanation' => 'Charles de Gaulle a rédigé la Constitution de 1958 et fut le premier président de la Ve République.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Charles de Gaulle', 'isCorrect' => true],
                    ['content' => 'Philippe Pétain', 'isCorrect' => false],
                    ['content' => 'Napoléon Bonaparte', 'isCorrect' => false],
                    ['content' => 'Georges Pompidou', 'isCorrect' => false],
                ],
            ],
            // INSTITUTIONS
            [
                'theme' => ThemeFixtures::THEME_INSTITUTIONS,
                'content' => 'Combien de temps dure le mandat du Président de la République ?',
                'isMultiple' => false,
                'explanation' => 'Depuis 2002, le mandat présidentiel est de 5 ans (quinquennat).',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => '5 ans', 'isCorrect' => true],
                    ['content' => '7 ans', 'isCorrect' => false],
                    ['content' => '4 ans', 'isCorrect' => false],
                    ['content' => '6 ans', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_INSTITUTIONS,
                'content' => 'Qui nomme le Premier ministre ?',
                'isMultiple' => false,
                'explanation' => 'Le Président de la République nomme le Premier ministre.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Le Président de la République', 'isCorrect' => true],
                    ['content' => 'L\'Assemblée nationale', 'isCorrect' => false],
                    ['content' => 'Le Sénat', 'isCorrect' => false],
                    ['content' => 'Le peuple français', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_INSTITUTIONS,
                'content' => 'Où siège l\'Assemblée nationale ?',
                'isMultiple' => false,
                'explanation' => 'L\'Assemblée nationale siège au Palais Bourbon à Paris.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'Palais Bourbon', 'isCorrect' => true],
                    ['content' => 'Palais du Luxembourg', 'isCorrect' => false],
                    ['content' => 'Palais de l\'Élysée', 'isCorrect' => false],
                    ['content' => 'Hôtel Matignon', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_INSTITUTIONS,
                'content' => 'Combien de députés compte l\'Assemblée nationale ?',
                'isMultiple' => false,
                'explanation' => 'L\'Assemblée nationale compte 577 députés élus au suffrage universel direct.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => '577', 'isCorrect' => true],
                    ['content' => '348', 'isCorrect' => false],
                    ['content' => '500', 'isCorrect' => false],
                    ['content' => '650', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_INSTITUTIONS,
                'content' => 'Où siège le Sénat ?',
                'isMultiple' => false,
                'explanation' => 'Le Sénat siège au Palais du Luxembourg à Paris.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'Palais du Luxembourg', 'isCorrect' => true],
                    ['content' => 'Palais Bourbon', 'isCorrect' => false],
                    ['content' => 'Hôtel Matignon', 'isCorrect' => false],
                    ['content' => 'Palais de l\'Élysée', 'isCorrect' => false],
                ],
            ],
            // GÉOGRAPHIE
            [
                'theme' => ThemeFixtures::THEME_GEOGRAPHIE,
                'content' => 'Combien de régions métropolitaines compte la France ?',
                'isMultiple' => false,
                'explanation' => 'Depuis 2016, la France métropolitaine compte 13 régions.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => '13', 'isCorrect' => true],
                    ['content' => '18', 'isCorrect' => false],
                    ['content' => '22', 'isCorrect' => false],
                    ['content' => '15', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_GEOGRAPHIE,
                'content' => 'Quelle est la capitale de la France ?',
                'isMultiple' => false,
                'explanation' => 'Paris est la capitale de la France depuis des siècles.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Paris', 'isCorrect' => true],
                    ['content' => 'Lyon', 'isCorrect' => false],
                    ['content' => 'Marseille', 'isCorrect' => false],
                    ['content' => 'Bordeaux', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_GEOGRAPHIE,
                'content' => 'Quels sont les territoires français d\'outre-mer ?',
                'isMultiple' => true,
                'explanation' => 'La France possède plusieurs territoires d\'outre-mer comme la Guadeloupe, la Martinique et La Réunion.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'La Guadeloupe', 'isCorrect' => true],
                    ['content' => 'La Martinique', 'isCorrect' => true],
                    ['content' => 'La Réunion', 'isCorrect' => true],
                    ['content' => 'Le Portugal', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_GEOGRAPHIE,
                'content' => 'Quel fleuve traverse Paris ?',
                'isMultiple' => false,
                'explanation' => 'La Seine traverse Paris d\'est en ouest.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'La Seine', 'isCorrect' => true],
                    ['content' => 'La Loire', 'isCorrect' => false],
                    ['content' => 'Le Rhône', 'isCorrect' => false],
                    ['content' => 'La Garonne', 'isCorrect' => false],
                ],
            ],
            // SYMBOLES
            [
                'theme' => ThemeFixtures::THEME_SYMBOLES,
                'content' => 'Quelles sont les couleurs du drapeau français ?',
                'isMultiple' => true,
                'explanation' => 'Le drapeau tricolore bleu, blanc, rouge est le symbole de la France.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Bleu', 'isCorrect' => true],
                    ['content' => 'Blanc', 'isCorrect' => true],
                    ['content' => 'Rouge', 'isCorrect' => true],
                    ['content' => 'Vert', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_SYMBOLES,
                'content' => 'Comment s\'appelle l\'hymne national français ?',
                'isMultiple' => false,
                'explanation' => 'La Marseillaise a été composée en 1792 par Rouget de Lisle.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'La Marseillaise', 'isCorrect' => true],
                    ['content' => 'La Parisienne', 'isCorrect' => false],
                    ['content' => 'Le Chant du Départ', 'isCorrect' => false],
                    ['content' => 'La Carmagnole', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_SYMBOLES,
                'content' => 'Quel personnage symbolise la République française ?',
                'isMultiple' => false,
                'explanation' => 'Marianne est l\'incarnation de la République française.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Marianne', 'isCorrect' => true],
                    ['content' => 'Jeanne d\'Arc', 'isCorrect' => false],
                    ['content' => 'Marie-Antoinette', 'isCorrect' => false],
                    ['content' => 'La Dame de Fer', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_SYMBOLES,
                'content' => 'Quel animal est le symbole de la France ?',
                'isMultiple' => false,
                'explanation' => 'Le coq gaulois est un symbole national depuis l\'Antiquité.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Le coq', 'isCorrect' => true],
                    ['content' => 'L\'aigle', 'isCorrect' => false],
                    ['content' => 'Le lion', 'isCorrect' => false],
                    ['content' => 'L\'ours', 'isCorrect' => false],
                ],
            ],
            // DROITS ET DEVOIRS
            [
                'theme' => ThemeFixtures::THEME_DROITS,
                'content' => 'À partir de quel âge peut-on voter en France ?',
                'isMultiple' => false,
                'explanation' => 'Le droit de vote est accordé à tous les citoyens français de 18 ans et plus.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => '18 ans', 'isCorrect' => true],
                    ['content' => '16 ans', 'isCorrect' => false],
                    ['content' => '21 ans', 'isCorrect' => false],
                    ['content' => '25 ans', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_DROITS,
                'content' => 'L\'école est obligatoire jusqu\'à quel âge ?',
                'isMultiple' => false,
                'explanation' => 'L\'instruction est obligatoire de 3 à 16 ans.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => '16 ans', 'isCorrect' => true],
                    ['content' => '14 ans', 'isCorrect' => false],
                    ['content' => '18 ans', 'isCorrect' => false],
                    ['content' => '12 ans', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_DROITS,
                'content' => 'Quels sont les devoirs du citoyen français ?',
                'isMultiple' => true,
                'explanation' => 'Le citoyen doit respecter les lois, payer ses impôts et participer à la défense du pays si nécessaire.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'Respecter les lois', 'isCorrect' => true],
                    ['content' => 'Payer ses impôts', 'isCorrect' => true],
                    ['content' => 'Voter', 'isCorrect' => true],
                    ['content' => 'Parler uniquement français', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_DROITS,
                'content' => 'La Déclaration des droits de l\'homme et du citoyen date de quelle année ?',
                'isMultiple' => false,
                'explanation' => 'La DDHC a été adoptée le 26 août 1789.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => '1789', 'isCorrect' => true],
                    ['content' => '1791', 'isCorrect' => false],
                    ['content' => '1793', 'isCorrect' => false],
                    ['content' => '1848', 'isCorrect' => false],
                ],
            ],
            // VIE QUOTIDIENNE
            [
                'theme' => ThemeFixtures::THEME_VIE_QUOTIDIENNE,
                'content' => 'Quel numéro appeler en cas d\'urgence médicale ?',
                'isMultiple' => false,
                'explanation' => 'Le 15 est le numéro du SAMU pour les urgences médicales.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => '15', 'isCorrect' => true],
                    ['content' => '17', 'isCorrect' => false],
                    ['content' => '18', 'isCorrect' => false],
                    ['content' => '112', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_VIE_QUOTIDIENNE,
                'content' => 'Quel numéro appeler pour la police ?',
                'isMultiple' => false,
                'explanation' => 'Le 17 est le numéro de la police nationale.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => '17', 'isCorrect' => true],
                    ['content' => '15', 'isCorrect' => false],
                    ['content' => '18', 'isCorrect' => false],
                    ['content' => '19', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_VIE_QUOTIDIENNE,
                'content' => 'Quel est le numéro d\'urgence européen ?',
                'isMultiple' => false,
                'explanation' => 'Le 112 est le numéro d\'urgence valable dans toute l\'Union européenne.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => '112', 'isCorrect' => true],
                    ['content' => '911', 'isCorrect' => false],
                    ['content' => '999', 'isCorrect' => false],
                    ['content' => '119', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_VIE_QUOTIDIENNE,
                'content' => 'Comment s\'appelle la fête nationale française ?',
                'isMultiple' => false,
                'explanation' => 'Le 14 juillet est la fête nationale, célébrant la prise de la Bastille.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Le 14 juillet', 'isCorrect' => true],
                    ['content' => 'Le 8 mai', 'isCorrect' => false],
                    ['content' => 'Le 11 novembre', 'isCorrect' => false],
                    ['content' => 'Le 1er mai', 'isCorrect' => false],
                ],
            ],
            // Questions supplémentaires pour atteindre 40+
            [
                'theme' => ThemeFixtures::THEME_HISTOIRE,
                'content' => 'Qui a été le premier président de la Ve République ?',
                'isMultiple' => false,
                'explanation' => 'Charles de Gaulle a été élu premier président de la Ve République en 1958.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Charles de Gaulle', 'isCorrect' => true],
                    ['content' => 'Georges Pompidou', 'isCorrect' => false],
                    ['content' => 'François Mitterrand', 'isCorrect' => false],
                    ['content' => 'Vincent Auriol', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_INSTITUTIONS,
                'content' => 'Qui est le chef de l\'État en France ?',
                'isMultiple' => false,
                'explanation' => 'Le Président de la République est le chef de l\'État.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Le Président de la République', 'isCorrect' => true],
                    ['content' => 'Le Premier ministre', 'isCorrect' => false],
                    ['content' => 'Le Président de l\'Assemblée nationale', 'isCorrect' => false],
                    ['content' => 'Le Roi', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_INSTITUTIONS,
                'content' => 'Qui dirige le gouvernement ?',
                'isMultiple' => false,
                'explanation' => 'Le Premier ministre dirige l\'action du gouvernement.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Le Premier ministre', 'isCorrect' => true],
                    ['content' => 'Le Président de la République', 'isCorrect' => false],
                    ['content' => 'Le ministre de l\'Intérieur', 'isCorrect' => false],
                    ['content' => 'Le Président du Sénat', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_VALEURS,
                'content' => 'Qu\'est-ce que l\'égalité des droits ?',
                'isMultiple' => false,
                'explanation' => 'L\'égalité des droits signifie que tous les citoyens ont les mêmes droits devant la loi.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'Tous les citoyens ont les mêmes droits devant la loi', 'isCorrect' => true],
                    ['content' => 'Tous les citoyens gagnent le même salaire', 'isCorrect' => false],
                    ['content' => 'Tous les citoyens ont le même niveau d\'éducation', 'isCorrect' => false],
                    ['content' => 'Tous les citoyens ont la même religion', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_DROITS,
                'content' => 'Quel droit fondamental permet de s\'exprimer librement ?',
                'isMultiple' => false,
                'explanation' => 'La liberté d\'expression est un droit fondamental garanti par la Constitution.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'La liberté d\'expression', 'isCorrect' => true],
                    ['content' => 'La liberté de circulation', 'isCorrect' => false],
                    ['content' => 'La liberté de commerce', 'isCorrect' => false],
                    ['content' => 'La liberté d\'association', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_SYMBOLES,
                'content' => 'Qui a composé La Marseillaise ?',
                'isMultiple' => false,
                'explanation' => 'Rouget de Lisle a composé La Marseillaise en 1792 à Strasbourg.',
                'difficulty' => Question::DIFFICULTY_HARD,
                'answers' => [
                    ['content' => 'Rouget de Lisle', 'isCorrect' => true],
                    ['content' => 'Victor Hugo', 'isCorrect' => false],
                    ['content' => 'Napoléon Bonaparte', 'isCorrect' => false],
                    ['content' => 'Charles de Gaulle', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_GEOGRAPHIE,
                'content' => 'Combien de pays ont une frontière commune avec la France métropolitaine ?',
                'isMultiple' => false,
                'explanation' => 'La France partage ses frontières avec 8 pays: Belgique, Luxembourg, Allemagne, Suisse, Italie, Monaco, Espagne et Andorre.',
                'difficulty' => Question::DIFFICULTY_HARD,
                'answers' => [
                    ['content' => '8', 'isCorrect' => true],
                    ['content' => '6', 'isCorrect' => false],
                    ['content' => '5', 'isCorrect' => false],
                    ['content' => '7', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_HISTOIRE,
                'content' => 'Quel événement commémore le 11 novembre ?',
                'isMultiple' => false,
                'explanation' => 'Le 11 novembre 1918 marque l\'armistice de la Première Guerre mondiale.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'L\'armistice de 1918', 'isCorrect' => true],
                    ['content' => 'La fin de la Seconde Guerre mondiale', 'isCorrect' => false],
                    ['content' => 'La Révolution française', 'isCorrect' => false],
                    ['content' => 'La libération de Paris', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_HISTOIRE,
                'content' => 'Quel événement commémore le 8 mai ?',
                'isMultiple' => false,
                'explanation' => 'Le 8 mai 1945 marque la victoire des Alliés et la fin de la Seconde Guerre mondiale en Europe.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'La victoire de 1945', 'isCorrect' => true],
                    ['content' => 'La prise de la Bastille', 'isCorrect' => false],
                    ['content' => 'L\'armistice de 1918', 'isCorrect' => false],
                    ['content' => 'La fête du Travail', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_VIE_QUOTIDIENNE,
                'content' => 'Quel jour est férié en France pour la fête du Travail ?',
                'isMultiple' => false,
                'explanation' => 'Le 1er mai est la fête du Travail, jour férié en France.',
                'difficulty' => Question::DIFFICULTY_EASY,
                'answers' => [
                    ['content' => 'Le 1er mai', 'isCorrect' => true],
                    ['content' => 'Le 1er juin', 'isCorrect' => false],
                    ['content' => 'Le 15 août', 'isCorrect' => false],
                    ['content' => 'Le 14 juillet', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_INSTITUTIONS,
                'content' => 'Quel est le rôle du Conseil constitutionnel ?',
                'isMultiple' => false,
                'explanation' => 'Le Conseil constitutionnel vérifie la conformité des lois avec la Constitution.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'Vérifier la conformité des lois avec la Constitution', 'isCorrect' => true],
                    ['content' => 'Voter les lois', 'isCorrect' => false],
                    ['content' => 'Gouverner le pays', 'isCorrect' => false],
                    ['content' => 'Juger les criminels', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_VALEURS,
                'content' => 'Que signifie le principe de fraternité ?',
                'isMultiple' => false,
                'explanation' => 'La fraternité implique la solidarité et l\'entraide entre citoyens.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'La solidarité entre citoyens', 'isCorrect' => true],
                    ['content' => 'L\'obligation d\'avoir des frères et sœurs', 'isCorrect' => false],
                    ['content' => 'Le droit à l\'héritage', 'isCorrect' => false],
                    ['content' => 'Le lien familial uniquement', 'isCorrect' => false],
                ],
            ],
            [
                'theme' => ThemeFixtures::THEME_DROITS,
                'content' => 'Quel document prouve la nationalité française ?',
                'isMultiple' => false,
                'explanation' => 'Le certificat de nationalité française est le document officiel qui prouve la nationalité.',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'answers' => [
                    ['content' => 'Le certificat de nationalité française', 'isCorrect' => true],
                    ['content' => 'La carte d\'identité', 'isCorrect' => false],
                    ['content' => 'Le passeport', 'isCorrect' => false],
                    ['content' => 'Le permis de conduire', 'isCorrect' => false],
                ],
            ],
        ];
    }
}