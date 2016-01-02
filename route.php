<?php

/**
 * JWT protected routes
 */

$api = app('api.router');

$api->version('v1', ['middleware' => 'api.auth', 'providers' => 'jwt'], function ($api) {
	$api->group(['prefix' => '/articles', 'namespace' => 'Modules\Articles\Controllers'], function($api) {
    	$api->get('/', 'ArticlesController@index');
    	$api->post('/', 'ArticlesController@store');
    	$api->put('/{id}', 'ArticlesController@update');
    	$api->delete('/{id}', 'ArticlesController@destroy');
    });
});
