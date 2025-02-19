<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->post('/api/ceremony', 'Auth::ceremony');
$routes->post('/api/ceremony/login', 'Auth::login');
