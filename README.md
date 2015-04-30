Reach - PHP MongoDB ODM
===

[![Total Downloads](http://img.shields.io/packagist/dt/serebro/reach-mongo.svg?style=flat)](https://packagist.org/packages/serebro/reach-mongo)
[![Build Status](http://img.shields.io/travis/serebro/reach-mongo.svg?style=flat)](https://travis-ci.org/serebro/reach-mongo)
[![Code Coverage](http://img.shields.io/coveralls/serebro/reach-mongo.svg?style=flat)](https://coveralls.io/r/serebro/reach-mongo)
[![License](http://img.shields.io/packagist/l/serebro/reach-mongo.svg?style=flat)](https://packagist.org/packages/serebro/reach-mongo)


Requirements
---

- PHP >= 5.4;
- ext-mongo >= 1.5

The key feature list:
---
- Simple, Powerful, Ultrafast
- Support of schema-less documents
- **Ability to use efficient result set iterator instead of raw arrays of results**
- Lazy loading/creating of documents
- Support for partial loading of documents from DB
- **Unit of work (Reduce the number of database requests/updates. Prevent redundant requests and multiple updates to the same document)**
- References and embeds
- Events (system:  `before*()` ,  `after*()` ; custom:  `on()` ,  `off()` ,  `trigger()` )
- Query builder
- Extensions: Reach can be customized infinitely with behaviours.
- Creating an auto-incrementing sequence field
- Batch inserting, Pagination, Logging
- Integration with IDEs


Installation
---

```JSON
{
    "require": {
        "serebro/reach-mongo": "dev-master"
    }
}
```
```php
$config = [
	'class'    => '\Reach\Mongo\Connection'
	'database' => 'testing',
	'host'     => 'localhost',
	'port'     => 27017,
];

\Reach\Service\Container::register('mongo', $config);
```


Basic usage
---

```php
<?php

// Create model
$client = new Client();
$client->name = 'Google';

// or
$client = new Client(['name' => 'Yandex']);

// or
$client = new Client();
$client->setName('Google')->setDescription('Good Corporation');

// Save
$client->save();

// Count the number of active clients
echo Client::count(['status' => Client::STATUS_ACTIVE]); // 1000

// Get customers as an array
$array = Client::find()->limit(3)->asArray();

// Get a list of the top 10 active clients sorted in alphabetical order
$clients = Client::find(['status' => Client::STATUS_ACTIVE])->sort(['name' => 1])->limit(10);
echo $clients->count(); // 10
echo count($clients); // 10

// Get an array of "_id" all clients
$array_of_mongo_ids = $clients->pluck('_id');

// Convert a list of customers in the array and delete the two attribute
$array_document = $clients->toArray(['exclude' => ['balance', 'partners']]);

// Merge the results of queries
$clients_inactive = Client::find(['status' => Client::STATUS_INACTIVE])->limit(2);
$clients = $clients->mergeWith($clients_inactive);
echo $clients->count(); // 12

// Iterate through the list of clients
foreach($clients as $client) {
	echo $client->name;
}

// or
$clients->map(function($client){
	echo $client->name;
});

// Additional information about the query used indexes, etc.
$clients->explain();

// Get one customer from the database
$client = Client::findOne(['status' => Client::STATUS_ACTIVE]);
$client = Client::findOne($mongoId);

// Deleting
$client->delete();
Client::deleteAll(['status' => Client::STATUS_INACTIVE]);
```


Create your model
---
```php
<?php

class Client extends \Reach\Mongo\Document
{
	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;

	// Attributes
	/** @var \MongoId */
	public $_id;

	/** @var int */
	public $sequence_id;

	public $status = self::STATUS_INACTIVE;
	public $name = '';
	public $balance = 0;
	
	/** @var \Reach\Mongo\Behavior\LangText */
	public $description;

	/** @var \City */
	public $city;

	/** @var \Partners[] */
	public $partners = [];

	/** @var \MongoDate */
	public $created_at;

	/** @var \MongoDate */
	public $updated_at;

	// Behaviors and relations
    public function behaviors()
    {
        return [
        	// Create an auto-incrementing sequence field
        	'sequence_id' => ['class' => '\Reach\Mongo\Behavior\Generator\SequenceId'],

            'city'     => ['class' => Relation::REF_PK,   'ref' => '\City',   'key' => 'city_id'],
            'partners' => ['class' => Relation::REF_MANY, 'ref' => '\Partner'],
            
            'description' => ['class' => '\Reach\Mongo\Behavior\LangText'],
        ];
    }

	// Methods
	public static function getCollectionName()
	{
		return 'client';
	}

	public function beforeInsert()
	{
		$this->created_at = new \MongoDate();
		return parent::beforeInsert();
	}

	public function beforeUpdate()
	{
		$this->updated_at = new \MongoDate();
		return parent::beforeUpdate();
	}

	public function toArray(array $params = [])
	{
		$array = parent::toArray($params);
		$array['created_at'] = self::date($this->created_at->sec);
		$array['updated_at'] = self::date($this->updated_at->sec);
		return $array;
	}
}
```


Query Builder
---

```php
<?php

$criteria1 = new \Reach\Mongo\Criteria();
$criteria1->addOr(['status' => Client::STATUS_INACTIVE]);
$criteria1->addOr('created', '<', new \MongoDate());

$criteria2 = new \Reach\Mongo\Criteria();
$criteria2->orderBy(['title' => -1]);
$criteria2->add($criteria1);

$query = TestSchema::query($criteria2);
$resultSet = $query->all()->limit(2);

```

**Criteria methods**
- `add()` - Joins query clauses with a logical AND returns all documents that match the conditions of both clauses.
- `addOr()` - Joins query clauses with a logical OR returns all documents that match the conditions of either clause.
- `addNor()` - Joins query clauses with a logical NOR returns all documents that fail to match both clauses.
- `where()` - Matches documents that satisfy a JavaScript expression.
- `orderBy()` - Returns a cursor with documents sorted according to a sort specification.
- `comment()` - Adds a comment to the query to identify queries in the database profiler output.
- `hint()` - Forces MongoDB to use a specific index.
- `maxScan()` - Limits the number of documents scanned.
- `maxTimeMS()` - Specifies a cumulative time limit in milliseconds for processing operations on a cursor.
- `max()` - Specifies an exclusive upper limit for the index to use in a query.
- `min()` - Specifies an inclusive lower limit for the index to use in a query.

**Query methods**
- `all()` -
- `one()` -
- `asArray()` - 

### Scopes
Two steps are required to define a scope.

**First** create a custom query class for your model and define the needed scope methods in this class.
```php
<?php 

class ClientQuery extends \Reach\Mongo\Query
{
	public function active($status = Client::STATUS_ACTIVE)
	{
		$this->add(['status' => $status]);
		return $this; // it is important
	}
}
```

**Second**, override \Reach\Mongo\Document::query() to use the custom query class instead of the regular.
```php
<?php

class Client extends \Reach\Mongo\Document
{
    public static function query(CriteriaInterface $criteria = null)
    {
        return new ClientQuery($criteria, get_called_class());
    }
}
```

**Using**
```php
<?php

$activeClients = Client::query()->active()->find();
$inactiveClients = Client::query()->active(false)->find();
```


Connecting
---
```php
<?php

$config = [
	'class'    => '\Reach\Mongo\Connection'
	'database' => 'testing',
	'host'     => 'localhost',
	'port'     => 27017,
	'username' => 'root', // or null
	'password' => 'wut5oot5fo1hec', // or null
	'options'  => ['connect' => true, 'socketTimeoutMS' => 60000],
	'connection_attempts' => 3,
	'connection_timeout'  => 1, // seconds
];

\Reach\Service\Container::register('mongo', $config);
```

**Getting instance of connection**
```php
<?php

$connection = \Reach\Service\Container::get('mongo');
$db_name = $connection->getDbName();

// Getting instance of native MongoDB class
$clientMongoCollection = $connection->getMongoCollection('client');
```

**Connecting to another database**
```php
<?php

$connection_name = 'logs';
\Reach\Service\Container::register($connection_name, $logs_config);
$logsСonnection = \Reach\Service\Container::get($connection_name);

// Getting document from another connection
$client = Client::getCollection($connection_name)->findOne($id);
```


Unit of work
---

```php
<?php

\Reach\Mongo\Buffer::open();
$client = new Client(['_id' => 1, 'name' => 'Yandex']);
$client->save();
$client->name = 'Google';
$client->save();
\Reach\Mongo\Buffer::flush();

\Reach\Mongo\Buffer::close();
```

Events
---
```php
<?php

$client = new Client();
$func1 = function($event){ echo 'fn #1 '; };
$func2 = function($event){ echo 'fn #2'; };
$client->on('add_new_partner', $func1);
$client->on('add_new_partner', $func2);

$client->partner[] = 'Google';
$client->trigger('add_new_partner'); // fn #1 fn #2
```

**System events**
* `init`
* `afterFind`
* `beforeInsert`
* `afterInsert`
* `beforeUpdate`
* `afterUpdate`
* `beforeSave`
* `afterSave`
* `beforeDelete`
* `afterDelete`


Caching with MongoDB
---
```php
<?php

$cache = new \Reach\Mongo\Cache();
$ttl = 60; // seconds
$cache->set('key', ['info' => 123], $ttl);
$cache->get('key'); // ['info' => 123]
$cache->delete('key') // true
$cache->get('key'); // null

// To initialize cache collection
$cache->initCollection();
```


Pagination
---
```php
<?php

$active = (int)$_GET['active']; // 1
$limit = (int)$_GET['limit']; // 10
$current_page = (int)$_GET['current']; // 2

$resultSet = Client::find(['active' => $active]); // Setting filter

$paginator = new \Reach\Mongo\Paginator($resultSet, $limit, $current_page); // Create pagination instance
$page = $paginator->getPaginate();

echo $page->first; // 1
echo $page->current; // 2
echo $page->next; // 3
echo $page->prev; // 1
echo $page->last; // 10
echo $page->pages; // 10
echo $page->total_items; // 100
echo count($page->items); // 10
$page->items; // \Reach\Mongo\ResultSet

```


Benchmarking
---
- [Reach](https://github.com/serebro/reach-mongo/blob/master/tests/Mongo/Benchmarks/ReachResults.md)
- [Phalcon ODM](https://github.com/serebro/reach-mongo/blob/master/tests/Mongo/Benchmarks/PhalconResults.md)
- [Source code](https://github.com/serebro/reach-mongo/tree/master/tests/Mongo/Benchmarks)


Roadmap
---
- [Two Phase Commits (transaction)](http://docs.mongodb.org/manual/tutorial/perform-two-phase-commits/)
- Behaviors: soft delete, alnum id generator
- Queries: geo
- GridFS
