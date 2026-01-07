<?php

namespace App\Enum;

enum QuizStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case ABANDONED = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Termine',
            self::ABANDONED => 'Abandonne',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::ABANDONED => 'danger',
        };
    }
}
