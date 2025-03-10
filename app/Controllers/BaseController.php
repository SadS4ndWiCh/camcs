<?php

namespace App\Controllers;

use App\Models\IndividualModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = service('session');
    }

    /**
     * Get the currently authenticated individual.
     * 
     * @param bool $returnNull If `true` it should return `null` if isn't authenticated.
     * @return array|null
     * @throws Exception Missing authentication token
     * @throws Exception Invalid authentication token
     */
    public function getAuthenticated($returnNull = false)
    {
        $authenticationHeader = $this->request->getServer('HTTP_AUTHORIZATION');

        helper('jwt');

        $token = JWT_extractTokenFromHeader($authenticationHeader);
        if (is_null($token)) {
            if ($returnNull) return null;

            throw new Exception(
                'Missing authentication token',
                ResponseInterface::HTTP_UNAUTHORIZED
            );
        }

        $payload = JWT_validateToken($token);
        if (is_null($payload)) {
            if ($returnNull) return null;

            throw new Exception(
                'Invalid authentication token',
                ResponseInterface::HTTP_UNAUTHORIZED
            );
        }

        $individualModel = new IndividualModel();
        $individual = $individualModel->find($payload->id);

        if (is_null($individual)) {
            log_message('warning', 'Try to log in with a non-existing individual ID: {id}', [
                'id' => $payload->id,
                'ip' => $this->request->getIPAddress()
            ]);

            throw new Exception(
                'Invalid individual ID',
                ResponseInterface::HTTP_UNAUTHORIZED
            );
        }

        return $individual;
    }
}
