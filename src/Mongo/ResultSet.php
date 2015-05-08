<?php

namespace Reach\Mongo;

use Countable;
use Exception;
use Iterator;
use MongoCursor;
use Reach\Mongo\Document\Schema;
use Reach\ResultSet as BaseResultSet;
use Reach\ResultSetInterface;

/**
 * Class ResultSet
 * @package Reach\Mongo
 * @method ResultSet awaitData ($wait = true)
 * @method hasNext()
 * @method getReadPreference ()
 * @method ResultSet limit($num)
 * @method ResultSet partial ($okay = true)
 * @method ResultSet setFlag ($flag, $set = true)
 * @method ResultSet setReadPreference ($read_preference, array $tags)
 * @method ResultSet skip($num)
 * @method ResultSet slaveOkay($okay = true)
 * @method ResultSet tailable($tail = true)
 * @method ResultSet immortal($liveForever = true)
 * @method ResultSet timeout($ms)
 * @method dead()
 * @method ResultSet snapshot()
 * @method ResultSet hint(array $key_pattern)
 * @method ResultSet addOption($key, $value)
 * @method doQuery()
 * @method reset()
 * @method explain()
 * @method ResultSet fields(array $f)
 * @method info()
 * @method ResultSet batchSize($batchSize)
 */
class ResultSet implements ResultSetInterface, Countable, Iterator
{

    /** @var MongoCursor */
    private $_cursor;

    /** @var string */
    private $_class_name;

    private static $_fn = [
        'awaitData',
        'limit',
        'partial',
        'setFlag',
        'setReadPreference',
        'skip',
        'slaveOkay',
        'tailable',
        'immortal',
        'timeout',
        'snapshot',
        'sort',
        'hint',
        'addOption',
        'fields',
        'batchSize',
    ];

    private $_sort_disable = false;

    public function __construct(MongoCursor $cursor, $class_name)
    {
        $this->_class_name = $class_name;
        $this->_cursor = $cursor;
    }

    ///////////////////////////////////////////////////

    public function __call($name, $arguments)
    {
        if (!method_exists($this->_cursor, $name)) {
            return null;
        }

        $result = call_user_func_array([$this->_cursor, $name], $arguments);

        if (in_array($name, self::$_fn)) {
            return $this;
        }

        return $result;
    }

    /**
     * @param array $fields
     * @throws Exception
     * @return $this
     */
    public function sort(array $fields)
    {
        if ($this->_sort_disable) {
            throw new Exception(
                '\Reach\Mongo\ResultSet::sort(' . json_encode(
                    $fields
                ) . ') can not be used because QueryBuilder was used'
            );
        }

        $this->_cursor->sort($fields);
        return $this;
    }

    /**
     * @return null|Schema|DocumentInterface
     */
    public function instantiate($document)
    {
        if ($document) {
            $class_name = $this->_class_name;
            if (in_array('instantiate', get_class_methods($class_name))) {
                /** @var DocumentInterface $model */
                $model = $class_name::instantiate($document);
                $model->setIsNew(false);
                $model->afterFind($document);
                $model->commit();
            } else {
                $model = (object)$document;
            }
            return $model;
        } else {
            return null;
        }
    }

    /**
     * @return null|Schema|DocumentInterface
     */
    public function current()
    {
        return $this->instantiate($this->_cursor->current());
    }

    public function valid()
    {
        return $this->_cursor->valid();
    }

    public function rewind()
    {
        $this->_cursor->rewind();
    }

    /**
     * Counts the number of results for this query
     * @link http://www.php.net/manual/en/mongocursor.count.php
     * @param bool $all Send cursor limit and skip information to the count function, if applicable.
     * @return int The number of documents returned by this cursor's query.
     */
    public function count($all = true)
    {
        return $this->_cursor->count($all);
    }

    public function key()
    {
        return $this->_cursor->key();
    }

    public function next()
    {
        $this->_cursor->next();
    }

    ///////////////////////////////////////////////////

    /**
     * @return MongoCursor
     */
    public function asCursor()
    {
        return $this->_cursor;
    }

    /**
     * @return Schema|DocumentInterface
     */
    public function first()
    {
        $this->rewind();
        return $this->current();
    }

    public function getObjectClassName()
    {
        return $this->_class_name;
    }

    /**
     * @param ResultSetInterface $resultSet
     * @return \Reach\ResultSet
     */
    public function mergeWith(ResultSetInterface $resultSet)
    {
        $newResultSet = new BaseResultSet($this->asArray());
        $resultSet->rewind();
        while ($model = $resultSet->current()) {
            /** @var $model Schema|DocumentInterface */
            $newResultSet->append($model);
            $resultSet->next();
        }

        return $newResultSet;
    }

    /**
     * @param DocumentInterface $model
     * @return \Reach\ResultSet
     */
    public function append(DocumentInterface $model)
    {
        $array_of_documents = [];
        foreach ($this->_cursor as $doc) {
            $array_of_documents[] = $this->instantiate($doc);
        }
        $array_of_documents[] = $model;

        return new BaseResultSet($array_of_documents);
    }

    /**
     * @return array
     */
    public function getIds()
    {
        $result = [];
        $this->rewind();
        while ($model = $this->current()) {
            /** @var $model DocumentInterface */
            $primaryKey = $model::getPrimaryKey();
            $result[] = (string)$model->$primaryKey;
            $this->next();
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getMongoIds()
    {
        $result = [];
        $this->rewind();
        while ($model = $this->current()) {
            /** @var $model Schema */
            $result[] = $model->_id;
            $this->next();
        }

        return $result;
    }

    /**
     * @param string $attribute - Example: "field.sub_field.sub_sub_field"
     * @return array
     */
    public function pluck($attribute)
    {
        $results = [];
        $key_separator = '.';
        $keys = explode($key_separator, $attribute);

        $this->rewind();
        while ($model = $this->current()) {
            /** @var $model Schema */

            $x = 0;
            $result = $model;
            while (isset($keys[$x])) {
                if ($x === count($keys) - 1) {
                    $result = $result[$keys[$x]];
                } else {
                    $result = $result[$keys[$x]] = $result[$keys[$x]] ?: [];
                }
                $x++;
            }

            $results[] = $result;
            $this->next();
        }

        return $results;
    }

    /**
     * @param $fn
     * @return Schema|null
     */
    public function find($fn)
    {
        $this->rewind();
        while ($model = $this->current()) {
            /** @var $model Schema */
            if (call_user_func_array($fn, [$model])) {
                return $model;
            }

            $this->next();
        }

        return null;
    }

    /**
     * @param $fn
     * @return \Reach\ResultSet
     */
    public function filter($fn)
    {
        $resultSet = new BaseResultSet();
        $this->rewind();
        while ($item = $this->current()) {
            /** @var $item Schema */
            $key = $this->key();
            if (call_user_func_array($fn, [$item, $key])) {
                $resultSet->append($item);
            }

            $this->next();
        }

        return $resultSet;
    }

    /**
     * @param callable $fn (Schema $item, int $i)
     * @return \Reach\ResultSet
     */
    public function map($fn)
    {
        $i = 0;
        $resultSet = new BaseResultSet();
        foreach ($this as $item) {
            $resultSet->append(call_user_func_array($fn, [$item, $i++]));
        }

        return $resultSet;
    }

    /**
     * Export all data
     * @param bool $use_keys
     * @return array
     */
    public function export($use_keys = false)
    {
        return iterator_to_array($this->_cursor, $use_keys);
    }

    /**
     * @return DocumentInterface[]
     */
    public function asArray()
    {
        $array_of_documents = [];
        foreach ($this->_cursor as $doc) {
            $array_of_documents[] = $this->instantiate($doc);
        }

        return $array_of_documents;
    }

    /**
     * @param array $params
     * @return string
     */
    public function toJson(array $params = [])
    {
        if (method_exists($this, 'toArray')) {
            return json_encode($this->toArray($params));
        } else {
            $result = [];
            $this->rewind();
            /** @var $item Schema */
            while ($item = $this->current()) {
                $result[$this->_current] = $item;
                $this->next();
            }
            return json_encode($result);
        }
    }

    /**
     * @param array $params
     * @return array
     */
    public function toArray(array $params = [])
    {
        $result = [];

        $this->rewind();
        while ($item = $this->current()) {
            /** @var $item Schema */
            if (method_exists($item, 'toArray')) {
                $result[] = $item->toArray($params);
            } else {
                $result[] = $item;
            }

            $this->next();
        }

        return $result;
    }

    public function disableSort()
    {
        $this->_sort_disable = true;
    }
}
