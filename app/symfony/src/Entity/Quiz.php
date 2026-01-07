<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Quiz
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: QuizConfiguration::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuizConfiguration $configuration = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finishedAt = null;

    #[ORM\Column]
    private int $totalQuestions = 0;

    #[ORM\Column]
    private int $correctAnswers = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $scorePercent = '0.00';

    #[ORM\Column]
    private bool $isPassed = false;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_IN_PROGRESS;

    #[ORM\Column]
    private int $currentQuestionIndex = 0;

    /** @var Collection<int, QuizAnswer> */
    #[ORM\OneToMany(targetEntity: QuizAnswer::class, mappedBy: 'quiz', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['answeredAt' => 'ASC'])]
    private Collection $quizAnswers;

    public function __construct()
    {
        $this->quizAnswers = new ArrayCollection();
        $this->startedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->startedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getConfiguration(): ?QuizConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?QuizConfiguration $configuration): static
    {
        $this->configuration = $configuration;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeInterface $finishedAt): static
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function setTotalQuestions(int $totalQuestions): static
    {
        $this->totalQuestions = $totalQuestions;
        return $this;
    }

    public function getCorrectAnswers(): int
    {
        return $this->correctAnswers;
    }

    public function setCorrectAnswers(int $correctAnswers): static
    {
        $this->correctAnswers = $correctAnswers;
        return $this;
    }

    public function getScorePercent(): string
    {
        return $this->scorePercent;
    }

    public function setScorePercent(string $scorePercent): static
    {
        $this->scorePercent = $scorePercent;
        return $this;
    }

    public function isPassed(): bool
    {
        return $this->isPassed;
    }

    public function setIsPassed(bool $isPassed): static
    {
        $this->isPassed = $isPassed;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getCurrentQuestionIndex(): int
    {
        return $this->currentQuestionIndex;
    }

    public function setCurrentQuestionIndex(int $currentQuestionIndex): static
    {
        $this->currentQuestionIndex = $currentQuestionIndex;
        return $this;
    }

    /** @return Collection<int, QuizAnswer> */
    public function getQuizAnswers(): Collection
    {
        return $this->quizAnswers;
    }

    public function addQuizAnswer(QuizAnswer $quizAnswer): static
    {
        if (!$this->quizAnswers->contains($quizAnswer)) {
            $this->quizAnswers->add($quizAnswer);
            $quizAnswer->setQuiz($this);
        }
        return $this;
    }

    public function removeQuizAnswer(QuizAnswer $quizAnswer): static
    {
        if ($this->quizAnswers->removeElement($quizAnswer)) {
            if ($quizAnswer->getQuiz() === $this) {
                $quizAnswer->setQuiz(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, QuizAnswer> */
    public function getFailedAnswers(): Collection
    {
        return $this->quizAnswers->filter(fn(QuizAnswer $qa) => !$qa->isCorrect());
    }

    /** @return Collection<int, QuizAnswer> */
    public function getCorrectQuizAnswers(): Collection
    {
        return $this->quizAnswers->filter(fn(QuizAnswer $qa) => $qa->isCorrect());
    }

    public function getDuration(): ?\DateInterval
    {
        if (!$this->finishedAt) {
            return null;
        }
        return $this->startedAt->diff($this->finishedAt);
    }

    public function getDurationInMinutes(): ?int
    {
        $duration = $this->getDuration();
        if (!$duration) {
            return null;
        }
        return ($duration->h * 60) + $duration->i;
    }

    public function calculateResults(): void
    {
        $this->correctAnswers = $this->getCorrectQuizAnswers()->count();
        $this->totalQuestions = $this->quizAnswers->count();

        if ($this->totalQuestions > 0) {
            $this->scorePercent = number_format(
                ($this->correctAnswers / $this->totalQuestions) * 100,
                2,
                '.',
                ''
            );
        }

        $config = $this->configuration;
        if ($config) {
            $this->isPassed = $this->correctAnswers >= $config->getMinimumCorrectAnswers();
        }
    }

    public function complete(): void
    {
        $this->finishedAt = new \DateTime();
        $this->status = self::STATUS_COMPLETED;
        $this->calculateResults();
    }

    public function __toString(): string
    {
        return sprintf(
            'Quiz #%d - %s (%s)',
            $this->id ?? 0,
            $this->user?->getFullName() ?? 'Unknown',
            $this->startedAt?->format('d/m/Y H:i') ?? ''
        );
    }
}