<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'users'], function () use ($router) {
    $router->post('login', 'UserController@login');
    $router->post('reset-password', 'UserController@ressetPassword');

    $router->post('logout', ['middleware' => 'auth', 'uses' => 'UserController@logout']);
    $router->put('/{id}', ['middleware' => 'auth', 'uses' => 'UserController@update']);

    $router->get('/{userId}/folders', ['middleware' => 'auth', 'uses' => 'FolderController@getAllByUserId']);
    $router->post('/{userId}/folders', ['middleware' => 'auth', 'uses' => 'FolderController@create']);
});

$router->group(['prefix' => 'folders'], function () use ($router) {
    $router->delete('/{id}', ['middleware' => 'auth', 'uses' => 'FolderController@delete']);
    $router->post('/{folderId}/programs', ['middleware' => 'auth', 'uses' => 'ProgramController@create']);
});

$router->group(['prefix' => 'programs'], function () use ($router) {
});

$router->group(['prefix' => 'profiles'], function () use ($router) {
    $router->post('/', ['middleware' => 'auth', 'uses' => 'UserProfileController@create']);
    $router->put('/{id}', ['middleware' => 'auth', 'uses' => 'UserProfileController@update']);
    $router->delete('/{id}', ['middleware' => 'auth', 'uses' => 'UserProfileController@delete']);

    $router->get('/', ['middleware' => 'auth', 'uses' => 'UserProfileController@getAll']);
    $router->get('/{id}', ['middleware' => 'auth', 'uses' => 'UserProfileController@get']);
});


$router->get('/test', function () {
    phpinfo();

});
