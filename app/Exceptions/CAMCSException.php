<?php

namespace App\Exceptions;

use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use Throwable;

class CAMCSException extends Exception
{
    public function __construct(
        string $message,
        int $code = ResponseInterface::HTTP_INTERNAL_SERVER_ERROR,
        Throwable|null $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
