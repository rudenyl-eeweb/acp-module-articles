<?php

/**
 * JWT protected routes
 */

$api = app('api.router');

$api->version('v1', ['middleware' => 'api.auth', 'providers' => 'jwt'], function ($api) {
    $api->get('/articles', '\Modules\Articles\Controllers\ArticlesController@index');
});
