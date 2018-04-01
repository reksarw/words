<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/jokes', function() use ($app){

	$app->get('/', function(Request $request, Response $response) {
		return $response->withJson(['is_ok' => 'Yep']);
	});
});