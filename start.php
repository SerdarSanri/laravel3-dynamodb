<?php

if (defined('DBK_DYNAMODB_LOADED'))
{
	return;	
}

define('DBK_DYNAMODB_LOADED', true);

Autoloader::map(array(
	'DynamoDB' => __DIR__.DS.'libraries'.DS.'dynamodb.php',
));

