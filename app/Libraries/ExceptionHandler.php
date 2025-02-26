<?php

namespace App\Libraries;

use Throwable;
use CodeIgniter\Debug\BaseExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ExceptionHandler extends BaseExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(Throwable $exception, RequestInterface $request, ResponseInterface $response, int $statusCode, int $exitCode): void
    {
        $exceptionCode = $exception->getCode();
        if ($exceptionCode >= 100 && $exceptionCode <= 599)
            $statusCode = $exceptionCode;

        $response
            ->setJSON([
                'status'    => $statusCode,
                'error'   => $exception->getMessage()
            ])
            ->setStatusCode($statusCode)
            ->send();

        exit($exitCode);
    }
}
