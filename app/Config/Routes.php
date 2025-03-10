<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->post('/api/ceremony', 'Auth::ceremony');
$routes->post('/api/ceremony/login', 'Auth::login');

$routes->get('/api/individuals/profile', 'Individuals::profile');
$routes->post('/api/individuals/pray', 'Individuals::pray');
$routes->post('/api/individuals/spells/(:num)/release', 'Individuals::releaseSpell/$1');
$routes->post('/api/individuals/meditate', 'Individuals::meditate');

$routes->get('/api/spells', 'Spells::index');
$routes->post('/api/spells/(:num)/learn', 'Spells::learn/$1');
