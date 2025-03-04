<?php

namespace App\Libraries;

use Throwable;
use App\Exceptions\CAMCSException;
use CodeIgniter\Debug\BaseExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ExceptionHandler extends BaseExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(Throwable $exception, RequestInterface $request, ResponseInterface $response, int $statusCode, int $exitCode): void
    {
        if ($exception instanceof CAMCSException) {
            $statusCode = $exception->getCode();
        }

        $response
            ->setJSON([
                'status' => $statusCode,
                'error'  => $exception->getMessage()
            ])
            ->setStatusCode($statusCode)
            ->send();

        exit($exitCode);
    }
}
