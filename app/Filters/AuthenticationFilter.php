<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AuthenticationFilter implements FilterInterface
{
    /**
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $authenticationHeader = $request->getServer('HTTP_AUTHORIZATION');

        helper('jwt');
        $token = JWT_extractTokenFromHeader($authenticationHeader);

        if (is_null($token)) {
            return Services::response()
                ->setJSON(['error' => 'Missing authentication token.'])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        if (is_null(JWT_validateToken($token))) {
            return Services::response()
                ->setJSON(['error' => 'Invalid authentication token.'])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        return $request;
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
