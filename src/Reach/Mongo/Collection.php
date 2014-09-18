<?php

namespace Reach\Mongo;

use Exception;
use MongoCode;
use MongoCollection;
use MongoCursor;
use MongoDBRef;
use MongoId;
use Reach\Collection as BaseCollection;
use Reach\Mongo\Document\Schema;
use Reach\ResultSet;
use Reach\ResultSetInterface;

/**
 * Class Collection
 * @package Reach\Mongo
 * @method getSlaveOkay()
 * @method setSlaveOkay($ok = true)
 * @method getReadPreference()
 * @method setReadPreference($read_preference, array $tags)
 * @method setWriteConcern($w, $wtimeout = null)
 * @method getWriteConcern()
 * @method validate($scan_data = false)
 * @method insert($a, array $options = [])
 * @method update(array $criteria, array $newobj, array $options = [])
 * @method remove(array $criteria, array $options = [])
 * @method distinct ($key, array $query = null)
 * @method findAndModify (array $query, array $update = null, array $fields = null, array $options = null)
 * @method ensureIndex(array $keys, array $options = [])
 * @method deleteIndex($keys)
 * @method deleteIndexes()
 * @method getIndexInfo()
 * @method count($query = [])
 * @method save($a, array $options = [])
 */
class Collection extends BaseCollection
{

    /** @var MongoCollection */
    private $_mongoCollection;

    /** @var Connection */
    private $_connection;

    /** @var array */
    private $_identity_map = [];


    public function __construct(MongoCollection $mongoCollection, Connection $connection, $hydrate_class = '\stdClass')
    {
        $this->_mongoCollection = $mongoCollection;
        $this->_hydrate_class = $hydrate_class;
        $this->_connection = $connection;
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this->_mongoCollection, $name)) {
            return null;
        }

        $this->_connection->logging();
        return call_user_func_array([$this->_mongoCollection, $name], $arguments);
    }

    public function getMongoCollection()
    {
        return $this->_mongoCollection;
    }

    /**
     * @param array|CriteriaInterface $query
     * @param array                $fields
     * @return MongoCursor|ResultSetInterface
     */
    public function find($query = null, array $fields = [])
    {
        if ($query instanceof Query) {
            $query = $query->asArray();
        }

        $this->_connection->logging();
        $cursor = $this->_mongoCollection->find($query, $fields);

        if ($fields != []) {
            return $cursor;
        }

        $class = $this->_hydrate_class;
        if (in_array('resultSet', get_class_methods($class))) {
            return $class::resultSet($cursor);
        }

        $class = '\Reach\Mongo\ResultSet';
        return new $class($cursor, '\stdClass');
    }

    /**
     * @param mixed $query
     * @param array $fields
     * @param bool  $as_array
     * @return null|self
     */
    public function findOne($query = null, array $fields = [], $as_array = false)
    {
        $class = $this->_hydrate_class;
        $pk = $class::getPrimaryKey();

        $query = Criteria::normalize($query, $pk);

        // try to use identity map
        if (count($query) === 1 && array_key_exists($pk, $query)) {
            $id = $query[$pk];
            if ($model = $this->getIdentityMap($id)) {
                return $model;
            }
        }

        $this->_connection->logging();
        if ($document = $this->_mongoCollection->findOne($query, $fields)) {
            if ($as_array) {
                return $document;
            }

            $model = $class::instantiate($document);
            $model->setIsNew(false);
            $model->afterFind($document);
            return $model;
        }

        return null;
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
    public function search(
        $text,
        array $filter = [],
        $fields = [],
        $limit = null,
        $language = null,
        $options = []
    ) {
        $command = [
            'text'     => $this->_hydrate_class,
            'search'   => $text,
            'filter'   => $filter,
            'project'  => $fields,
            'limit'    => $limit,
            'language' => $language
        ];

        $this->_connection->logging();
        return $this->_connection->getDb()->command($command, $options);
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
    public function mapReduce($map, $reduce, array $out, array $query = [], array $command = [], $options = [])
    {
        $command = array_merge(
            $command,
            [
                'mapreduce' => $this->_hydrate_class,
                'map'       => is_string($map) ? new MongoCode($map) : $map,
                'reduce'    => is_string($reduce) ? new MongoCode($reduce) : $reduce,
                'out'       => $out,
                'query'     => $query,
            ]
        );

        $this->_connection->logging();
        $result = $this->_connection->getDb()->command($command, $options);

        if (isset($out['inline']) && $out['inline']) {
            return $result['results'];
        }

        return $this->_connection->getDb()->selectCollection($result['result'])->find();
    }

    /**
     * @deprecated
     * @param $query
     * @return array|null
     */
    public static function normalizeQueryId($query)
    {
        if (!is_array($query)) {
            if (!self::isValidMongoId($query)) {
                return null;
            }

            $query = ['_id' => self::ensureMongoId($query)];
        } else if (!empty($query['_id']) && is_string($query['_id'])) {
            $query['_id'] = self::ensureMongoId($query['_id']);
        }

        return $query;
    }

    /**
     * @param string|MongoId $id
     * @return bool
     */
    public static function isValidMongoId($id)
    {
        return MongoId::isValid($id);
    }

    /**
     * @param array|string $id
     * @return array|MongoId
     */
    public static function ensureMongoId($id)
    {
        if (is_array($id)) {
            foreach ($id as &$v) {
                if (!($v instanceof MongoId) && self::isValidMongoId($v)) {
                    $v = new MongoId($v);
                }
            }
            return $id;
        } else if (!($id instanceof MongoId) && self::isValidMongoId($id)) {
            return new MongoId($id);
        } else {
            return $id;
        }
    }

    /**
     * @param array $pipeline
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    public function aggregate(array $pipeline, array $options = [])
    {
        $command = [
            'aggregate' => $this->_mongoCollection->getName(),
            'pipeline'  => $pipeline
        ];

        $this->_connection->logging();
        $result = $this->_connection->getDb()->command($command, $options);

        if (!isset($result['ok']) || !$result['ok']) {
            throw new Exception($result['errmsg']);
        }

        return $result['result'];
    }

    /**
     * Delete document from collection by criteria
     * @static
     * @param array $criteria
     * @param array $options
     * @return bool|int - False or affected rows
     */
    public function deleteAll(array $criteria = [], array $options = [])
    {
        $this->_connection->logging();
        $res = $this->_mongoCollection->remove($criteria, $options);
        if (empty($res['ok']) || !isset($res['n'])) {
            return false;
        }

        return $res['n'];
    }

    /**
     * @param $id
     * @return bool|DocumentInterface
     */
    public function getIdentityMap($id)
    {
        $object_hash = (string)$id;

        return isset($this->_identity_map[$object_hash]) ? $this->_identity_map[$object_hash] : false;
    }

    /**
     * @param DocumentInterface $document
     * @return bool
     */
    public function addToIdentityMap(DocumentInterface $document)
    {
        $object_hash = (string)$document->_id;

        if (isset($this->_identity_map[$object_hash])) {
            return false;
        }

        if (count($this->_identity_map) > 999) { // todo get from config?!
            array_shift($this->_identity_map);
        }

        $this->_identity_map[$object_hash] = $document;

        return true;
    }

    public function clearIdentityMap()
    {
        $this->_identity_map = [];
    }

    /**
     * @param DocumentInterface $document
     * @return bool
     */
    public function removeFromIdentityMap(DocumentInterface $document)
    {
        $object_hash = (string)$document->_id;

        if (isset($this->_identity_map[$object_hash])) {
            unset($this->_identity_map[$object_hash]);
            return true;
        }

        return false;
    }

    /**
     * Delete the current collection and all its documents
     * https://github.com/crodas/ActiveMongo/blob/master/lib/ActiveMongo.php
     * @return bool
     */
    public function drop()
    {
        //$obj->trigger('before_drop');
        $this->_connection->logging();
        $result = $this->_mongoCollection->drop();
        //$obj->trigger('after_drop');
        if ($result['ok'] != 1) {
            //throw new Exception($result['errmsg']);
        }

        return true;
    }

    public function addIndexes(array $indexes)
    {
        $this->_connection->logging();
        foreach ($indexes as $index) {
            if (is_array($index)) {
                if (empty($index['key'])) {
                    continue;
                }
                $options = empty($index['options']) ? [] : $index['options'];
                $this->addIndex($index['key'], $options);
            } else {
                $this->addIndex([$index => 1]);
            }
        }
    }

    /**
     * @todo http://php.net/manual/en/mongocollection.ensureindex.php
     * @param       $key
     * @param array $options
     * @return bool
     */
    public function addIndex($key, array $options = [])
    {
        foreach ($key as $id => $name) {
            if (is_numeric($id)) {
                unset($key[$id]);
                $key[$name] = 1;
            }
        }

        $options = array_merge($options, ['background' => 1]);
        $this->_connection->logging();
        return $this->_mongoCollection->ensureIndex($key, $options);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_mongoCollection->getName();
    }

    /**
     * @param Schema $model
     * @return array
     */
    public function createDBRef(Schema $model)
    {
        return $model->getCollection()->getDb()->createDBRef($model->getCollectionName(), $model);
    }

    public function getDb()
    {
        return $this->_connection->getDb();
    }

    /**
     * @param array $ref
     * @return array|null
     */
    public function getDBRef(array $ref)
    {
        if (!MongoDBRef::isRef($ref)) {
            return null;
        }

        if (!$document = $this->_mongoCollection->getDBRef($ref)) {
            return null;
        }

        return $document;
    }

    public function stats()
    {
        return $this->getDb()->command(['collstats' => $this->getName()]);
    }

    /**
     * Perform a batchInsert of objects.
     * @param       $list         Collection of documents to insert
     * @param array $options
     * @return \Reach\ResultSet
     */
    public function batchInsert($list, array $options = [])
    {
        if (!is_array($list) && !($list instanceof \Traversable) && !($list instanceof ResultSet)) {
            throw new \InvalidArgumentException('Parameter "list" is not traversable using foreach');
        }

        $documents = [];
        $saved = [];
        foreach ($list as $model) {
            if ($model instanceof DocumentInterface) {
                if (!$model->getIsNew()) {
                    continue;
                }
                if ($model->beforeInsert() === false) {
                    continue;
                }
                if ($model->_id === null || self::isValidMongoId($model->_id)) {
                    $model->_id = new MongoId($model->_id);
                }
                $documents[] = $model->getRawDocument();
                $saved[] = $model;
            }
        }
        unset($list, $model);

        $result = new ResultSet();

        if (!count($documents)) {
            return $result;
        }

        $response = $this->_mongoCollection->batchInsert($documents, $options);
        unset($documents);

        if (empty($response['ok'])) {
            return $result;
        }

        foreach ($saved as $model) {
            $model->setIsNew(false);
            $model->afterInsert();
            $result->append($model);
        }

        return $result;
    }
}
