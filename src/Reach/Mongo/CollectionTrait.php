<?php

namespace Reach\Mongo;

use MongoCollection;
use MongoCursor;
use MongoDBRef;
use MongoId;
use Reach\Mongo\Document\Schema;

trait CollectionTrait
{

    /**
     * @return string
     */
    public static function getCollectionName()
    {
        return str_replace(['\\', '/', '-'], '_', strtolower(get_called_class()));
    }

    /**
     * @param null $connection_name
     * @return MongoCollection
     */
    public static function getMongoCollection($connection_name = null)
    {
        return static::getConnection($connection_name)->getMongoCollection(static::getCollectionName());
    }

    /**
     * @param string $connection_name
     * @return Collection
     */
    public static function getCollection($connection_name = null)
    {
        if (!$connection = static::getConnection($connection_name)) {
            trigger_error(__METHOD__ . " Connection $connection_name is not registered.");
            return null;
        }

        return $connection->getCollection(static::getCollectionName(), get_called_class());
    }

    /**
     * @param null $connection_name
     * @return Connection
     */
    public static function getConnection($connection_name = null)
    {
        $connection_name = $connection_name ?: \Reach\Mongo\Connection::$default_connection_name;
        return \Reach\Service\Container::get($connection_name);
    }

    /**
     * @param string|MongoId $id
     * @return bool
     */
    public static function isValidMongoId($id)
    {
        return Collection::isValidMongoId($id);
    }

    /**
     * @param array|string $id
     * @return array|MongoId
     */
    public static function ensureMongoId($id)
    {
        return Collection::ensureMongoId($id);
    }

    /**
     * Counts the number of documents in this collection
     * @param array $query
     * @return int
     */
    public static function count($query = [])
    {
        return static::getCollection()->count($query);
    }

    /**
     * @param array $query
     * @param array $fields
     * @return MongoCursor|\Reach\ResultSet
     */
    public static function find($query = [], array $fields = [])
    {
        return static::getCollection()->find($query, $fields);
    }

    /**
     * @param array|string|MongoId $query
     * @param array                $fields
     * @param bool                 $as_array
     * @return null|self
     */
    public static function findOne($query = [], array $fields = [], $as_array = false)
    {
        return self::getCollection()->findOne($query, $fields, $as_array);
    }

    /**
     * @param array $criteria
     * @param array $data
     * @param array $options
     * @return bool
     */
    public static function updateAll(array $criteria, array $data, array $options = [])
    {
        return static::getCollection()->update($criteria, $data, $options);
    }

    /**
     * @param array $query
     * @param array $update
     * @param array $fields
     * @param array $options
     */
    public function findAndModify(array $query, array $update = null, array $fields = null, array $options = null)
    {
        return static::getCollection()->findAndModify($query, $update, $fields, $options);
    }

    /**
     * @param string $connection_name
     * @return bool
     */
    public static function drop($connection_name = null)
    {
        return self::getCollection($connection_name)->drop();
    }

    /**
     * @param string $field
     * @param array  $query
     * @return array
     */
    public static function distinct($field = '_id', array $query = [])
    {
        return static::getCollection()->distinct($field, $query);
    }

    /**
     * @param string $field
     * @param array  $query
     * @return int
     */
    public static function countDistinct($field = '_id', array $query = [])
    {
        return count(static::distinct($field, $query));
    }

    /**
     * @param $value
     * @return bool
     */
    public static function isMongoDBRef($value)
    {
        return MongoDBRef::isRef($value);
    }

    /**
     * @param Schema $model
     * @return array
     */
    public static function createDBRef(Schema $model)
    {
        return static::getCollection()->createDBRef($model);
    }

    /**
     * @param array $ref
     * @return array|null
     */
    public static function getDBRef(array $ref)
    {
        return static::getCollection()->getDBRef($ref);
    }

    /**
     * Search text content stored in the text index.
     * @param string  $text     A string of terms that MongoDB parses and uses to query the text index.
     * @param array   $filter   (optional) A query array, you can use any valid MongoDB query
     * @param array   $fields   (optional) Allows you to limit the fields returned by the query to only those specified.
     * @param integer $limit    (optional) Specify the maximum number of documents to include in the response.
     * @param string  $language (optional) Specify the language that determines for the search the list of stop words and the rules for the stemmer and tokenizer.
     * @param array   $options  Extra options for the command (optional).
     * @return array The results.
     */
    public static function search(
        $text,
        array $filter = [],
        $fields = [],
        $limit = null,
        $language = null,
        $options = []
    ) {
        return static::getCollection()->search($text, $filter, $fields, $limit, $language, $options);
    }

    /**
     * Shortcut to make map reduce.
     * @param mixed $map     The map function.
     * @param mixed $reduce  The reduce function.
     * @param array $out     The out.
     * @param array $query   The query (optional).
     * @param array $command
     * @param array $options Extra options for the command (optional).
     * @return array|MongoCursor
     */
    public static function mapReduce($map, $reduce, array $out, array $query = [], array $command = [], $options = [])
    {
        return static::getCollection()->mapReduce($map, $reduce, $out, $query, $command, $options);
    }

    public static function aggregate(array $pipeline, array $options = [])
    {
        return static::getCollection()->aggregate($pipeline, $options);
    }

    /**
     *  Return an array with all indexes
     * @return array
     */
    protected static function getIndexes()
    {
        return static::getCollection()->getIndexInfo();
    }

    /**
     * Delete document from collection by criteria
     * @static
     * @param array $criteria
     * @param array $options
     * @return bool|int - False or affected rows
     */
    public static function deleteAll(array $criteria = [], array $options = [])
    {
        return static::getCollection()->remove($criteria, $options);
    }

    /**
     * Perform a batchInsert of objects.
     * @param       $list         Collection of documents to insert
     * @param array $options
     * @return bool
     */
    public static function batchInsert($list, array $options = [])
    {
        return static::getCollection()->batchInsert($list, $options);
    }
}
