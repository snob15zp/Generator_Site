<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'users'], function() use ($router)
{
    $router->post('login', 'AuthController@login');
});

$router->group(['namespace' => '\Rap2hpoutre\LaravelLogViewer'], function() use ($router) {
    $router->get('logs', 'LogViewerController@index');
});

$router->get('/test', function() {
    phpinfo();
});
