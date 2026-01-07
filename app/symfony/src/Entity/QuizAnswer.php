<?php

namespace App\Entity;

use App\Repository\QuizAnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizAnswerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class QuizAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'quizAnswers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'quizAnswers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Question $question = null;

    #[ORM\Column(type: Types::JSON)]
    private array $selectedAnswerIds = [];

    #[ORM\Column]
    private bool $isCorrect = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $answeredAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $timeSpentSeconds = null;

    public function __construct()
    {
        $this->answeredAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->answeredAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;
        return $this;
    }

    public function getSelectedAnswerIds(): array
    {
        return $this->selectedAnswerIds;
    }

    public function setSelectedAnswerIds(array $selectedAnswerIds): static
    {
        $this->selectedAnswerIds = $selectedAnswerIds;
        return $this;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(bool $isCorrect): static
    {
        $this->isCorrect = $isCorrect;
        return $this;
    }

    public function getAnsweredAt(): ?\DateTimeInterface
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(\DateTimeInterface $answeredAt): static
    {
        $this->answeredAt = $answeredAt;
        return $this;
    }

    public function getTimeSpentSeconds(): ?int
    {
        return $this->timeSpentSeconds;
    }

    public function setTimeSpentSeconds(?int $timeSpentSeconds): static
    {
        $this->timeSpentSeconds = $timeSpentSeconds;
        return $this;
    }

    public function checkCorrectness(): bool
    {
        if (!$this->question) {
            return false;
        }

        $correctIds = $this->question->getCorrectAnswers()
            ->map(fn(Answer $a) => $a->getId())
            ->toArray();

        sort($correctIds);
        $selected = $this->selectedAnswerIds;
        sort($selected);

        $this->isCorrect = $correctIds === $selected;
        return $this->isCorrect;
    }

    /**
     * Get the Answer entities that were selected by the user
     * @return array<Answer>
     */
    public function getSelectedAnswers(): array
    {
        if (!$this->question) {
            return [];
        }

        return $this->question->getAnswers()
            ->filter(fn(Answer $a) => in_array($a->getId(), $this->selectedAnswerIds))
            ->toArray();
    }

    /**
     * Check if a specific answer was selected
     */
    public function wasAnswerSelected(Answer $answer): bool
    {
        return in_array($answer->getId(), $this->selectedAnswerIds);
    }

    public function __toString(): string
    {
        return sprintf(
            'Réponse à: %s',
            $this->question?->__toString() ?? 'Question inconnue'
        );
    }
}