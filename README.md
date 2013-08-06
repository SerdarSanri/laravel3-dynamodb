## DynamoDB Bundle for Laravel
=================

personal effort to create an Eloquent like bundle for Amazon DynamoDB

#Installation
add files into bundles folder and then auto-load the bundle in bundles.php:

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



Table **users** with HASH key only 

email `hash` | password | registed_at
--- | --- | ---
**test@test.com** | test123 | *1375538399*


Table **messages** with HASH and RANGE key

to `hash` | date `range` | from | subject | message_body 
--- | --- | --- | --- | ---
**user1@test.com** | **1375538399** | user2@test.com | Hello User1 | Goodbye User1

DynamoDB Object

	$users = DynamoDB::table('users');
	$messages = DynamoDB::table('messages');

Getting an Item
	

	// getting an item with HASH key only
	DynamoDB::table('users')
		->getItem(array('email' => 'test@test.com'))

	// getting an item from a HASH-RANGE table
	DynamoDB::table('messages')
		->getItem(array('to' => 'user1@test.com', 'date' => (int) 1375538399 ))

	// specifying what attributes to return
	DynamoDB::table('users')
		->select('email','registered_at')
		->getItem(array('email' => 'test@test.com'))

	// specifying what attributes to return, dynamically
	$query = DynamoDB::table('users')
	$query->select('email')
	$query->addSelect('registered_at')
	$query->getItem(array('email' => 'test@test.com'))

Updating item's attributes

	// update multiple attribute values of a HASH table
	DynamoDB::table('users')
		->where('email','=','test@test.com')
		->update(array('password' => 'qwert', 'firstname' => 'Smith'))
	
	// update one item attribute in a HASH-RANGE table
	DynamoDB::table('messages')
		->where('to','=','user1@test.com')
		->where('date','=',1375538399)
		->update(array('seen' => "yes"))
	
Deleting item's attributes

	...
	
Deleting item

	// delete an item from a HASH table
	DynamoDB::table('users')
		->where('email','=','test@test.com')
		->delete()
	
	// delete an item from a HASH-RANGE table
	DynamoDB::table('messages')
		->where('to','=','user1@test.com')
		->where('date','=', 1375538399 )
		->delete()

