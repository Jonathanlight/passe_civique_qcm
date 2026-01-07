<?php

namespace App\Entity;

use App\Repository\GamificationLevelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GamificationLevelRepository::class)]
class GamificationLevel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 20)]
    private ?string $emoji = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $minQuizzesPassed = 0;

    #[ORM\Column]
    private int $minAverageScore = 0;

    #[ORM\Column]
    private int $displayOrder = 0;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $badgeColor = null;

    #[ORM\Column]
    private bool $isActive = true;

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

    public function getEmoji(): ?string
    {
        return $this->emoji;
    }

    public function setEmoji(string $emoji): static
    {
        $this->emoji = $emoji;
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

    public function getMinQuizzesPassed(): int
    {
        return $this->minQuizzesPassed;
    }

    public function setMinQuizzesPassed(int $minQuizzesPassed): static
    {
        $this->minQuizzesPassed = $minQuizzesPassed;
        return $this;
    }

    public function getMinAverageScore(): int
    {
        return $this->minAverageScore;
    }

    public function setMinAverageScore(int $minAverageScore): static
    {
        $this->minAverageScore = $minAverageScore;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getBadgeColor(): ?string
    {
        return $this->badgeColor;
    }

    public function setBadgeColor(?string $badgeColor): static
    {
        $this->badgeColor = $badgeColor;
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

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->emoji ?? '', $this->name ?? '');
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}