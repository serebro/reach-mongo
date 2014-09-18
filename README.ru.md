Reach - PHP MongoDB ODM (ORM для Mongo)
===
[![Total Downloads](http://img.shields.io/packagist/dt/serebro/Reach.svg?style=flat)](https://packagist.org/packages/serebro/Reach)
[![Build Status](http://img.shields.io/travis/serebro/Reach.svg?style=flat)](https://travis-ci.org/serebro/Reach)
[![Code Coverage](http://img.shields.io/coveralls/serebro/Reach.svg?style=flat)](https://coveralls.io/r/serebro/Reach)
[![License](http://img.shields.io/packagist/l/serebro/Reach.svg?style=flat)](https://packagist.org/packages/serebro/Reach)


Требования
---

- PHP >= 5.4;
- ext-mongo >= 1.3


Возможности
---

- Простота использования, скорость работы
- Поддерживает работу с документами без заранее заданной схемы
- **Использование итератора как результата поиска по коллекции, а не массив**
- "Ленивая" загрузка объектов в результатах поиска
- Возможность частичной загрузки данных из БД
- **Буферизация операций чтения и записи для уменьшения обращений к БД (unit of work)**
- Сохранение в БД только измененных атрибутов документа, а не целиком всего документа
- Встроенные документы (embeds) и связи (references)
- Использование событий (предопределенные:  `before_*()` ,  `after_*()` ; пользовательские:  `on()` ,  `off()` ,  `trigger()` )
- Возможность расширения функционала с помощью Поведений (behaviours)
- Пакетная вставка данных, пагинация, логирование
- Менеджер подключений к различным БД
- Дружственность к IDE


Установка
---
Подробнее об установке [c помощью composer](http://getcomposer.org), а также [packagist](https://packagist.org/packages/serebro/reach).

```JSON
{
    "require": {
        "serebro/reach": "dev-master"
    }
}
```


Примеры
---

```php
// Создать модель
$client = new Client();
$client->name = 'Google';

// или так
$client = new Client(['name' => 'Yandex']);

// а еще вот так
$client = new Client();
$client->setName('Google')->setDescription('Корпорация Добра');

// Сохранить
$client->save();

// Подсчитать количество активных клиентов
echo Client::count(['status' => Client::STATUS_ACTIVE]); // 1000

// Получить клиентов в виде массива
$array = Client::find()->limit(3)->asArray();

// Получить итератор для первых 10 активных клиентов упорядоченных по алфавиту 
$clients = Client::find(['status' => Client::STATUS_ACTIVE])->sort(['name' => 1])->limit(10);
echo $clients->count(); // 10
echo count($clients); // 10

// Получить массив из "_id" всех клиентов 
$array_of_mongo_ids = $clients->pluck('_id');

// Преобразовать список клиентов в массив исключив два атрибута
$array_document = $clients->toArray(['exclude' => ['balance', 'partners']]);

// Объединить результаты запросов
$clients_inactive = Client::find(['status' => Client::STATUS_INACTIVE])->limit(2);
$clients = $clients->mergeWith($clients_inactive);
echo $clients->count(); // 12

// Итерация по списку клиентов
foreach($clients as $client) {
	echo $client->name;
}

// или так
$clients->map(function($client){
	echo $client->name;
});

// Полнотекстовый поиск (необходимо установить соотвтествующий индекс)
$array = Client::search($text, $filter, $fields, $limit, $language);

// Дополнительная информация о запросе, используемы индексы и т.п.
$clients->explain();

// Получить одного клиента из БД
$client = Client::findOne(['status' => Client::STATUS_ACTIVE]);
$client = Client::findOne($mongoId);

// Удаление
$client->delete();
Client::deleteAll(['status' => Client::STATUS_INACTIVE]);
```


Описание модели
---
```php
class Client extents \Reach\Mongo\Document {
	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	
	// Attributes
	/** @var \MongoId */
	public $_id;

	/** @var int */
	public $sequence_id;

	public $name = '';
	public $balance = 0;
	public $description = '';

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
        ];
    }

	// Methods
	public static function getCollectionName() {
		return 'client';
	}
	
	public function beforeInsert() {
		$this->created_at = new \MongoDate();
		return parent::beforeInsert();
	}

	public function beforeUpdate() {
		$this->updated_at = new \MongoDate();
		return parent::beforeUpdate();
	}
	
	public function toArray(array $params = []) {
		$array = parent::toArray($params);
		$array['created_at'] = self::date($this->created_at->sec);
		$array['updated_at'] = self::date($this->updated_at->sec);
		return $array;
	}
}
```


Подключение
---

```php
$config = [
	'database' => 'testing',
	'host'     => 'localhost',
	'port'     => 27017,
	'username' => 'root', // или null
	'password' => 'wut5oot5fo1hec', // или null
	'options'  => ['connect' => true, 'socketTimeoutMS' => 60000],
	'connection_attempts' = 3,
	'connection_timeout'  = 1,
];

// Регистрация параметров соединения с базой данных
\Reach\Mongo\ConnectionManager::registerConnection($config);

// Получение соединения
$connection = \Reach\Mongo\ConnectionManager::getConnection();
$db_name = $connection->getDbName();

// Получение объекта MongoCollection для работы "напрямую" с драйвером
$clientMongoCollection = $connection->getMongoCollection('client');

// Подключение к другой базе данных
$connection_name = 'logs';
\Reach\Mongo\ConnectionManager::registerConnection($logs_config, $connection_name);
$logsСonnection = \Reach\Mongo\ConnectionManager::getConnection($connection_name);

// Получение объекта клиента с использованием другого подключения
$client = Client::getCollection('another_connection_name')->findOne($id);

```


Подключение к [Phalcon Framework](http://phalconphp.com)
---
```php
\Reach\Mongo\ConnectionManager::registerConnection($config->toArray());
```


Буферизация записи в БД (unit of work)
---
Результатом работы этого кода будет один запрос к БД на добавление клиента с именем "Google".
```php
\Reach\Mongo\Buffer::open();
$client = new Client(['name' => 'Yandex']);
$client->save();
$client->name = 'Google';
$client->save();
\Reach\Mongo\Buffer::flush();

// Отключить использование буфера
\Reach\Mongo\Buffer::close();
```


Использование событий
---
```php
$client = new Client();
$func1 = function($event){ echo 'fn #1 '; };
$func2 = function($event){ echo 'fn #2'; };
$client->on('add_new_partner', $func1);
$client->on('add_new_partner', $func2);

$client->partner[] = 'Google';
$client->trigger('add_new_partner'); // fn #1 fn #2
```

#### Список системных событий:
* init
* afterFind
* beforeInsert
* afterInsert
* beforeUpdate
* afterUpdate
* beforeSave
* afterSave
* beforeDelete
* afterDelete


Кеширование данных, используя MongoDB
---
```php
$cache = new \Reach\Mongo\Cache();
$ttl = 60; // секунд
$cache->set('key', ['info' => 123], $ttl);
$cache->get('key'); // ['info' => 123]
$cache->delete('key') // true
$cache->get('key'); // null

// Чтобы правильно инициализировать коллекцию нужно выполнить только один раз 
$cache->initCollection();
```


Пагинация
---
```php
$active = (int)$_GET['active']; // 1
$limit = (int)$_GET['limit']; // 10
$current_page = (int)$_GET['current']; // 2

$resultSet = Client::find(['active' => $active]); // Установка базового фильтра

$paginator = new \Reach\Mongo\Paginator($resultSet, $limit, $current_page); // Создание пагинатора
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


Производительность
---
- [Результаты тестирования Reach ActiveRecord](https://github.com/serebro/Reach/blob/master/tests/Mongo/Benchmarks/ReachResults.md)
- [Результаты тестирования](https://github.com/serebro/Reach/blob/master/tests/Mongo/Benchmarks/PhalconResults.md) (для сравнения) [Phalcon ODM](http://docs.phalconphp.com/en/latest/reference/odm.html)
- [Исходный код тестов тут](https://github.com/serebro/Reach/tree/master/tests/Mongo/Benchmarks)


Планы
---
- [Two Phase Commits (transaction)](http://docs.mongodb.org/manual/tutorial/perform-two-phase-commits/)
- Behaviors: soft delete, alnum id generator
- Queries: geo
- GridFS
