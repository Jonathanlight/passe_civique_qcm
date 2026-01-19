<?php

namespace App\Entity;

use App\Enum\Difficulty;
use App\Repository\QuizConfigurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizConfigurationRepository::class)]
class QuizConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $questionsCount = 40;

    #[ORM\Column]
    private int $minimumCorrectAnswers = 30;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $minimumScorePercent = '75.00';

    #[ORM\Column(nullable: true)]
    private ?int $timeLimitMinutes = null;

    #[ORM\Column]
    private bool $shuffleQuestions = true;

    #[ORM\Column]
    private bool $shuffleAnswers = true;

    #[ORM\Column]
    private bool $showExplanationAfterAnswer = true;

    #[ORM\Column]
    private bool $showResultsAtEnd = true;

    #[ORM\Column]
    private bool $allowRetake = true;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(length: 20)]
    private string $difficulty = 'medium';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /** @var Collection<int, Quiz> */
    #[ORM\OneToMany(targetEntity: Quiz::class, mappedBy: 'configuration')]
    private Collection $quizzes;

    public function __construct()
    {
        $this->quizzes = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getQuestionsCount(): int
    {
        return $this->questionsCount;
    }

    public function setQuestionsCount(int $questionsCount): static
    {
        $this->questionsCount = $questionsCount;
        return $this;
    }

    public function getMinimumCorrectAnswers(): int
    {
        return $this->minimumCorrectAnswers;
    }

    public function setMinimumCorrectAnswers(int $minimumCorrectAnswers): static
    {
        $this->minimumCorrectAnswers = $minimumCorrectAnswers;
        return $this;
    }

    public function getMinimumScorePercent(): string
    {
        return $this->minimumScorePercent;
    }

    public function setMinimumScorePercent(string $minimumScorePercent): static
    {
        $this->minimumScorePercent = $minimumScorePercent;
        return $this;
    }

    public function getTimeLimitMinutes(): ?int
    {
        return $this->timeLimitMinutes;
    }

    public function setTimeLimitMinutes(?int $timeLimitMinutes): static
    {
        $this->timeLimitMinutes = $timeLimitMinutes;
        return $this;
    }

    public function isShuffleQuestions(): bool
    {
        return $this->shuffleQuestions;
    }

    public function setShuffleQuestions(bool $shuffleQuestions): static
    {
        $this->shuffleQuestions = $shuffleQuestions;
        return $this;
    }

    public function isShuffleAnswers(): bool
    {
        return $this->shuffleAnswers;
    }

    public function setShuffleAnswers(bool $shuffleAnswers): static
    {
        $this->shuffleAnswers = $shuffleAnswers;
        return $this;
    }

    public function isShowExplanationAfterAnswer(): bool
    {
        return $this->showExplanationAfterAnswer;
    }

    public function setShowExplanationAfterAnswer(bool $showExplanationAfterAnswer): static
    {
        $this->showExplanationAfterAnswer = $showExplanationAfterAnswer;
        return $this;
    }

    public function isShowResultsAtEnd(): bool
    {
        return $this->showResultsAtEnd;
    }

    public function setShowResultsAtEnd(bool $showResultsAtEnd): static
    {
        $this->showResultsAtEnd = $showResultsAtEnd;
        return $this;
    }

    public function isAllowRetake(): bool
    {
        return $this->allowRetake;
    }

    public function setAllowRetake(bool $allowRetake): static
    {
        $this->allowRetake = $allowRetake;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getDifficulty(): string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): static
    {
        $this->difficulty = $difficulty;
        return $this;
    }

    public function getDifficultyEnum(): Difficulty
    {
        return Difficulty::from($this->difficulty);
    }

    public function getDifficultyLabel(): string
    {
        return $this->getDifficultyEnum()->label();
    }

    public function getDifficultyColor(): string
    {
        return $this->getDifficultyEnum()->color();
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /** @return Collection<int, Quiz> */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}