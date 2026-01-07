<?php

namespace App\Security\Voter;

use App\Entity\Quiz;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class QuizVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof Quiz;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Quiz $quiz */
        $quiz = $subject;

        return match ($attribute) {
            self::VIEW, self::EDIT => $this->canAccess($quiz, $user),
            default => false,
        };
    }

    private function canAccess(Quiz $quiz, User $user): bool
    {
        if ($quiz->getUser() === $user) {
            return true;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return false;
    }
}
