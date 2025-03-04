<?php

namespace App\Exceptions;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class AuthException extends CAMCSException
{
    public static function forCeremonyFailToInsertIndividual(Throwable|null $previous = null): CAMCSException
    {
        return new AuthException(
            'The ceremony wasn\'t able to even start. Maybe you already have an insignia?',
            previous: $previous
        );
    }

    public static function forCeremonyFailToInsertMetadata(Throwable|null $previous = null): CAMCSException
    {
        return new AuthException(
            'Maybe the gods don\'t like you. The ceremony was almost complete. You can try again.',
            previous: $previous
        );
    }

    public static function forLoginWrongCredentials(Throwable|null $previous = null)
    {
        return new AuthException(
            'Individual\'s soul or code is invalid.',
            ResponseInterface::HTTP_UNAUTHORIZED,
            $previous
        );
    }
}
