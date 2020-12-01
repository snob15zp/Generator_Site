<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Notifications\UserCreateNotification;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

$router->group(['prefix' => 'users'], function () use ($router) {
    $router->post('login', 'UserController@login');
    $router->post('reset-password', 'UserController@ressetPassword');

    $router->post('logout', ['middleware' => 'auth', 'uses' => 'UserController@logout']);
    $router->put('/{id}', ['middleware' => 'auth', 'uses' => 'UserController@update']);
});

$router->group(['prefix' => 'profiles'], function () use ($router) {
    $router->post('/', ['middleware' => 'auth', 'uses' => 'UserProfileController@create']);
    $router->put('/{id}', ['middleware' => 'auth', 'uses' => 'UserProfileController@update']);

    $router->get('/', ['middleware' => 'auth', 'uses' => 'UserProfileController@getAll']);
    $router->get('/{id}', ['middleware' => 'auth', 'uses' => 'UserProfileController@get']);
});


$router->get('/test', function () {
    $user = \App\Models\User::query()->find(2);
    Notification::route('mail', 'test@mail.com')->notify(new UserCreateNotification($user, Str::random(8)));
});
