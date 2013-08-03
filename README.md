## DynamoDB Bundle for Laravel
=================

personal effort to create an Eloquent like bundle for Amazon DynamoDB


You must then auto-load the bundle in bundles.php:

	return array(
		'dynamodb' => array('auto'=>true)
	);
  
Edit your bundles/dynamodb/config/config.php

	return array(
		'key' => '<your amazon key>',
		'secret' => '<your amazon secret>',
		'region' => '<amazon zone>', // eg. eu-west-1
	);
  
#Usage
creating a DynamoDB object

	$query = DynamoDB::table('users');


Getting an Item
	

	// getting an item with HASH key only
	$query->getItem(array('email' => 'test@test.com'))

	// getting an item with a number HASH key
	$query->getItem(array('id' => (int) 1234 ))

	// getting an item from a HASH-RANGE table
	$query->getItem(array('username' => 'test', 'timestamp' => (int) 1375538399 ))

	// obtaining only specific attributes
	$query->select('id','username')->getItem(array('email' => 'test@test.com'))

	$query->select('id')->addSelect('username')->getItem(array('email' => 'test@test.com'))

Updating item's attributes

	...
	
Deleting item's attributes

	...
	
Deleting item

	...

