<?php

namespace App\Exceptions;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class IndividualException extends CAMCSException
{
    public static function forPrayFailToUpdateSP(Throwable|null $previous = null)
    {
        return new IndividualException(
            'Something went wrong during the prayer. Do the prayer from deep in your heart.',
            previous: $previous
        );
    }

    public static function forLearnAnUnexistingSpell(Throwable|null $previous = null)
    {
        return new IndividualException(
            'You are trying to learn a spell that even exists. Take it seriously.',
            ResponseInterface::HTTP_NOT_FOUND,
            $previous
        );
    }

    public static function forLearnAnAlreadyLearnedSpell(Throwable|null $previous = null)
    {
        return new IndividualException(
            'You already learned this spell. Why are you trying to learn again?',
            ResponseInterface::HTTP_BAD_REQUEST,
            $previous
        );
    }

    public static function forLearnAnUnavailableSpell(Throwable|null $previous = null)
    {
        return new IndividualException(
            'You cannot learn this spell. Try to learn another that matches your insignia.',
            ResponseInterface::HTTP_BAD_REQUEST,
            $previous
        );
    }

    public static function forLearnWithoutEnoughPoints(Throwable|null $previous = null)
    {
        return new IndividualException(
            'You lack points. Get some job or pray to the gods.',
            ResponseInterface::HTTP_BAD_REQUEST,
            $previous
        );
    }

    public static function forLearnFailsToUpdateMetadata(Throwable|null $previous = null)
    {
        return new IndividualException(
            'Something went wrong in the learning process. You could try again.',
            previous: $previous
        );
    }

    public static function forLearnFailsToInsertSpellAsLearned(Throwable|null $previous = null)
    {
        return new IndividualException(
            'Something went wrong in the learning process. You could try again.',
            previous: $previous
        );
    }

    public static function forReleaseAnUnexistingSpell(Throwable|null $previous = null)
    {
        return new IndividualException(
            'You are trying to release a spell that even exists. Take it seriously.',
            ResponseInterface::HTTP_BAD_REQUEST,
            $previous
        );
    }

    public static function forReleaseNotLearnedSpell(Throwable|null $previous = null)
    {
        return new IndividualException(
            'You don\'t have this spell to release. Learn it or consider releasing another one.',
            ResponseInterface::HTTP_NOT_FOUND,
            $previous
        );
    }

    public static function forReleaseWithoutEnoughMana(Throwable|null $previous = null)
    {
        return new IndividualException(
            'You don\'t have mana enough to release this spell.',
            ResponseInterface::HTTP_BAD_REQUEST,
            $previous
        );
    }

    public static function forReleaseFailToUpdateMetadata(Throwable|null $previous = null)
    {
        return new IndividualException(
            'The spell has almost been cast. Try again.',
            previous: $previous
        );
    }

    public static function forMeditateFailToUpdateMetadata(Throwable|null $previous = null)
    {
        return new IndividualException(
            'You\'ve lost your focus.',
            previous: $previous
        );
    }

    public static function forFailsToGrabIndividualMetadata(Throwable|null $previous = null)
    {
        return new IndividualException(
            'The system wasn\'t able to grab your metadata.',
            previous: $previous
        );
    }
}
