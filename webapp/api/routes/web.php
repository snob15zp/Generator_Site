<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Notifications\UserCreateNotification;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Notification;

$router->group(['prefix' => 'users'], function () use ($router) {
    $router->post('login', 'AuthController@login');
    $router->post('logout', ['middleware' => 'auth', 'uses' => 'AuthController@logout']);
});

$router->group(['prefix' => 'profiles'], function () use ($router) {
    $router->get('/', ['middleware' => 'auth', 'uses' => 'UserProfileController@getAll']);
    $router->get('/{id}', ['middleware' => 'auth', 'uses' => 'UserProfileController@get']);
});


$router->get('/test', function () {
    Notification::route('mail', 'test@mail.com')->notify(new UserCreateNotification((new UserFactory())->make()->toArray()));
});
