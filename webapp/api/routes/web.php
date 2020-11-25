<?php

/** @var \Laravel\Lumen\Routing\Router $router */


$router->post('/users/login', 'AuthController@login');

$router->group(['namespace' => '\Rap2hpoutre\LaravelLogViewer'], function() use ($router) {
    $router->get('logs', 'LogViewerController@index');
});
