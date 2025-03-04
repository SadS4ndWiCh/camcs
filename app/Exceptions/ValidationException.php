<?php

namespace App\Exceptions;

use Throwable;
use App\Exceptions\CAMCSException;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Validation\ValidationInterface;

class ValidationException extends CAMCSException
{
    public function __construct(private ValidationInterface $validator, string $message, int $code = ResponseInterface::HTTP_BAD_REQUEST, Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function forRequestValidationError(ValidationInterface $validator, Throwable|null $previous = null)
    {
        return new ValidationException(
            $validator,
            'Follow the rules and send correct data.',
            previous: $previous
        );
    }

    public function getJSON()
    {
        return [
            'status' => $this->code,
            'error'  => $this->message,
            'fields' => $this->validator->getErrors()
        ];
    }
}
