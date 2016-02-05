<?php

/**
 * JWT protected routes
 */

use Tymon\JWTAuth\JWTAuth;

use League\Fractal;
use Modules\Articles\Entities\Article;

$api = app('api.router');

$api->version('v1', ['middleware' => 'api.auth', 'providers' => 'jwt'], function ($api) {
	$api->resource('/articles', Modules\Articles\Controllers\ArticlesController::class);
});


/**
 * Internal requests sample
 */
app()->group(['prefix' => '/acp/articles', 'namespace' => 'Modules\Articles\Controllers'], function($app) {
	$dispatcher = app('api.dispatcher');

	$app->get('/', function() use ($dispatcher) {
		// DB::enableQueryLog();

		// echo '<pre>';
		// print_r(DB::getQueryLog());
		// echo '</pre>';

		// $credentials = [
		// 	'email' => 'test@test.com',
		// 	'password' => 'password'
		// ];
		// Auth::attempt($credentials, false, true);

		// $articles = $dispatcher->be(Auth::user())->get('api/articles');

		// return view('articles::index')->with('articles', $articles);
	});
});
