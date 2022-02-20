<?php

/** @var Router $router */

use Laravel\Lumen\Routing\Router;

$router->group(['prefix' => 'users'], function () use ($router) {
    $router->get('/{id}/check', ['middleware' => 'auth', 'uses' => 'UserController@check']);
    $router->get('/check', ['middleware' => 'auth', 'uses' => 'UserController@check']);

    $router->get('/', ['middleware' => 'auth', 'uses' => 'UserController@getAll']);

    $router->post('/login', 'UserController@login');
    $router->post('/logout', ['middleware' => 'auth', 'uses' => 'UserController@logout']);
    $router->put('/refresh', 'UserController@refresh');

    $router->get('/{id}', ['middleware' => 'auth', 'uses' => 'UserController@get']);

    $router->post('/{id}/programs', ['middleware' => 'auth', 'uses' => 'ProgramController@createForUser']);
    $router->get('/{id}/programs', ['middleware' => 'auth', 'uses' => 'ProgramController@getAllForUser']);
    $router->delete('/{id}/programs', ['middleware' => 'auth', 'uses' => 'ProgramController@deleteForUser']);
    $router->get('/{id}/history', ['middleware' => 'auth', 'uses' => 'ProgramController@getProgramsHistory']);
    $router->post('/{id}/attach', ['middleware' => 'auth', 'uses' => 'ProgramController@attachToUserFromList']);

    $router->post('reset-password', 'UserController@resetPassword');
    $router->post('forget-password', 'UserController@forgetPassword');

    $router->put('/{id}', ['middleware' => 'auth', 'uses' => 'UserController@update']);
    $router->post('/owners/{ownerId}', ['middleware' => 'auth', 'uses' => 'UserController@create']);
    $router->post('/', ['middleware' => 'auth', 'uses' => 'UserController@create']);
    $router->delete('/', ['middleware' => 'auth', 'uses' => 'UserController@delete']);

    $router->get('/{id}/profile', ['middleware' => 'auth', 'uses' => 'UserProfileController@getByUserId']);

    $router->post('/{userId}/folders/{folderId}/renew', ['middleware' => 'auth', 'uses' => 'FolderController@renew']);
    $router->post('/{userId}/folders', ['middleware' => 'auth', 'uses' => 'FolderController@create']);
    $router->get('/{userId}/folders', ['middleware' => 'auth', 'uses' => 'FolderController@getAllByUserId']);

    $router->post('/{id}/owner', ['middleware' => 'auth', 'uses' => 'UserController@addChildren']);

});

$router->group(['prefix' => 'folders'], function () use ($router) {
    $router->delete('/{id}', ['middleware' => 'auth', 'uses' => 'FolderController@delete']);
    $router->get('/{id}', 'FolderController@get');

    $router->get('/{id}/h/{hash}', 'FolderController@get');
    $router->get('/{id}/download', ['middleware' => 'auth', 'uses' => 'FolderController@download']);
    $router->get('/{id}/download-link', ['middleware' => 'auth', 'uses' => 'FolderController@prepareDownload']);
    $router->get('/{id}/download/{hash}', 'FolderController@import');

    $router->get('/{folderId}/programs', ['middleware' => 'auth', 'uses' => 'ProgramController@getAllForFolder']);
    $router->post('/{folderId}/attach', ['middleware' => 'auth', 'uses' => 'ProgramController@attachToFolderFromList']);
    $router->post('/{folderId}/programs', ['middleware' => 'auth', 'uses' => 'ProgramController@createForFolder']);
    $router->delete('/{folderId}/programs', ['middleware' => 'auth', 'uses' => 'ProgramController@deleteFromFolder']);
});

$router->group(['prefix' => 'programs'], function () use ($router) {
    $router->get('/migrate', ['uses' => 'ProgramController@migrate']);
//    $router->get('/{id}/download', ['middleware' => 'auth', 'uses' => 'ProgramController@download']);
//    $router->delete('/{id}', ['middleware' => 'auth', 'uses' => 'ProgramController@delete']);
//    $router->delete('/', ['middleware' => 'auth', 'uses' => 'ProgramController@deleteAll']);
//    $router->get('/', ['middleware' => 'auth', 'uses' => 'ProgramController@getAll']);
});

$router->group(['prefix' => 'firmware'], function () use ($router) {
    $router->get('/', 'FirmwareController@getAll');
    $router->get('/latest', 'FirmwareController@getLatest');
    $router->get('/{version}/download', 'FirmwareController@download');

    $router->post('/', ['middleware' => 'auth', 'uses' => 'FirmwareController@create']);
    $router->put('/{id}', ['middleware' => 'auth', 'uses' => 'FirmwareController@update']);
    $router->delete('/{version}', ['middleware' => 'auth', 'uses' => 'FirmwareController@delete']);
});

$router->group(['prefix' => 'software'], function () use ($router) {
    $router->get('/', 'SoftwareController@getAll');
    $router->get('/latest', 'SoftwareController@getLatest');
    $router->get('/{version}/download', 'SoftwareController@download');

    $router->post('/', ['middleware' => 'auth', 'uses' => 'SoftwareController@create']);
    $router->put('/{id}', ['middleware' => 'auth', 'uses' => 'SoftwareController@update']);
    $router->delete('/{version}', ['middleware' => 'auth', 'uses' => 'SoftwareController@delete']);
});

$router->group(['prefix' => 'profiles'], function () use ($router) {
    $router->get('/', ['middleware' => 'auth', 'uses' => 'UserProfileController@getAll']);
    $router->get('/{id}', ['middleware' => 'auth', 'uses' => 'UserProfileController@get']);
});
