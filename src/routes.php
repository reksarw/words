<?php

// Version 1
$app->group('/v1', function() use ($app){
	include "modules/Quotes.php";
	include "modules/Jokes.php";
});