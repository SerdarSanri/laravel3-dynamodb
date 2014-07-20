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


Table **messages** with HASH and RANGE (int) key

to `hash` | date `range` | from | subject | message_body 
--- | --- | --- | --- | ---
**user1@test.com** | **1375538399** | user2@test.com | Hello User1 | Goodbye User1
**user2@test.com** | **1384167887** | somebody@otherdomain.com | Foo | Bar


Table **statistics** with HASH and RANGE (string) key

site `hash` | day `range` | visitors | unique_visitors | pageviews | unique_pageviews
--- | --- | --- | --- | --- | ---
**mydomain.com** | **2013-11-01 21:00:00** | 100 | 50 | 200 | 150
**mydomain.com** | **2013-11-01 23:00:00** | 90 | 40 | 100 | 95



DynamoDB Object

	$users = DynamoDB::table('users');
	$messages = DynamoDB::table('messages');

**Get Item**
	

	// getting an item with HASH key only
	DynamoDB::table('users')
		->where('email','=','test@test.com')
		->get()

	// getting an item from a HASH-RANGE table
	DynamoDB::table('messages')
		->where('to','=','user1@test.com')
		->where('date','=', (int) 1375538399 )
		->get()

	// specifying what attributes to return
	DynamoDB::table('users')
		->select('email','registered_at')
		->where('email','=','test@test.com')
		->get()

	// specifying what attributes to return, dynamically
	$query = DynamoDB::table('users')
	$query->select('email')
	$query->addSelect('registered_at')
	$query->where('email','=','test@test.com')
	$query->get()

**Insert Item (replaces existing items)**

	DynamoDB::table('users')->insert(array(
		'email' => 'test@test.com',
		'password' => 'qwert',
		'created_at' => time(),
	));
	
	DynamoDB::table('messages')->insert(array(
		'to' => 'test@test.com',
		'date' => time(),
		'subject' => 'Foo',
		'message' => 'Bar',
	));


**Update Item's Attribute(s)**

	// update multiple attributes in a HASH table
	DynamoDB::table('users')
		->where('email','=','test@test.com')
		->update(array('password' => 'qwert', 'firstname' => 'Smith'))
	
	// update 1 attribute in a HASH-RANGE table
	DynamoDB::table('messages')
		->where('to','=','user1@test.com')
		->where('date','=',1375538399)
		->update(array('seen' => "yes"))


**Increment Item's Attribute(s)**

	// increment 1 attribute in a HASH table 
	DynamoDB::table('users')
		->where('email','=','test@test.com')
		->increment(array('login_count' => 1))
		
	// increment multiple attributes in a HASH-RANGE table
	DynamoDB::table('statistics')
		->where('domain','=','mydomain.com')
		->where('day','=','2013-11-01')
		->increment(array(
			'visitors' => 1,
			'page_views' => 5,
			'unique_page_views' => 1,
		))

**Delete Item's Attribute(s)**

	DynamoDB::table('messages')
		->where('to','=','user1@test.com')
		->where('date','=', 1375538399)
		->delete('seen','subject');
	
**Delete Item**

	// delete an item from a HASH table
	DynamoDB::table('users')
		->where('email','=','test@test.com')
		->delete()
	
	// delete an item from a HASH-RANGE table
	DynamoDB::table('messages')
		->where('to','=','user1@test.com')
		->where('date','=', 1375538399 )
		->delete()

**Query** (only possible on HASH and RANGE tables)

	// base query to return all records from 2013-11-01 until now
	DynamoDB::table('statistics')
		->where('domain','=','mydomain.com')
		->where('day','>=','2013-11-01')
		->get()

	// only return specified fields and limit to 10 results
	DynamoDB::table('statistics')
		->select('unique_visitors','unique_pageviews')
		->where('domain','=','mydomain.com')
		->where('day','>=','2013-11-01')
		->take(10)
		->get()
	
	
