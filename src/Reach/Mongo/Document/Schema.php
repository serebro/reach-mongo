<?php

namespace Reach\Mongo\Document;

use Exception;
use MongoId;
use Reach\HookableTrait;
use Reach\Mongo\Buffer;
use Reach\Mongo\Buffer\Adapter;
use Reach\Mongo\CollectionTrait;
use Reach\Mongo\DocumentTrait;

/**
 * @depends (PHP 5 >= 5.4.0)
 * @depends \Reach\Mongo\Buffer\Adapter\Memory
 */
abstract class Schema extends Embedded
{

    use HookableTrait;
    use DocumentTrait;
    use CollectionTrait;

    /** @var bool saved attributes */
    private $_saved = [];


    final public function __construct(array $attributes = null)
    {
        parent::__construct($attributes);
    }

    public function __clone()
    {
        $this->_saved = [];
    }

    /**
     * Examples:
     *  array(
     *      'username',
     *        array('key' => array('email' => 1), 'options' => array('unique' => 1, 'dropDups' => 1)),
     *        array('key' => array('first_name', 'last_name'), 'options' => array('name' => 'full_name')),
     *  )
     * @return array
     */
    protected static function indexes()
    {
        return [];
    }

    public static function getPrimaryKey()
    {
        return '_id';
    }

    /**
     * @param string $property
     * @return Schema|null
     */
    public function getDBRefModel($property)
    {
        if (!isset($this->$property)) {
            return null;
        }

        if (!$document = static::getDBRef($this->$property)) {
            return null;
        }

        if (isset(static::$relations[$property]['class']) && class_exists(static::$relations[$property]['class'])) {
            $class = static::$relations[$property]['class'];
        } else {
            return null;
        }

        /** @var self $model */
        $model = $class::instantiate($document);
        $model->setIsNew(false);
        $model->afterFind();

        return $model;
    }

    /**
     * @param null $document
     * @return Schema|null
     */
    public static function instantiate($document = null)
    {
        /** @var Schema $model */
        $model = parent::instantiate($document);
        $model->commit($document);
        static::getCollection()->addToIdentityMap($model);
        return $model;
    }

    public function commit(array $document = null)
    {
        $this->_saved = $document ?: $this->getRawDocument();
    }

    ///**
    // * @param Schema $model
    // * @param array    $query
    // * @param array    $fields
    // * @return MongoCursor|ResultSetInterface
    // */
    //public static function findAllBy(Schema $model, array $query = [], array $fields = [])
    //{
    //    $results = new ResultSet();
    //    $class = get_called_class();
    //    if (!$class::$relations) {
    //        return $results;
    //    }
    //
    //    $model_class = get_class($model);
    //
    //    $property = null;
    //    foreach ($class::$relations as $key => $options) {
    //        if (trim($options['model'], '\\') == trim($model_class, '\\') && $options['rel'] == Relation::DBREF) {
    //            $property = $key;
    //            break;
    //        }
    //    }
    //
    //    if (!$property) {
    //        return $results;
    //    }
    //
    //    $query = array_merge($query, ["$property.\$id" => $model->id]);
    //    return static::find($query, $fields);
    //}

    /**
     * Drop all data and ensure indexes
     */
    public static function setup()
    {
        static::getCollection()->drop();

        $indexes = static::indexes();
        if (!empty($indexes)) {
            static::getCollection()->addIndexes($indexes);
        }
    }

    /**
     * Saves an object to this collection
     * @param array $options
     * @return bool
     */
//	protected function forceSave(array $options = array()) {
//		if ($this->beforeSave() === false) {
//			return false;
//		}
//
//		$values = $this->getAttributes();
//		if ($this->getIsNew()) {
//			$values['_id'] = new \MongoId($values['_id']);
//		}
//
//		if (self::getCollection()->save($values, $options) === false) {
//			return false;
//		}
//
//		$this->_saved = $values;
//		if ($this->getIsNew()) {
//			$this->_id = $values['_id'];
//			$this->setIsNew(false);
//		}
//
//		$this->afterSave();
//
//		return true;
//	}

    public function setAttributes($values)
    {
        if (!$this->isValid($values)) {
            return false;
        }

        parent::setAttributes($values);
        return true;
    }

    /**
     * Validating model
     * @param mixed  $values
     * @param string $scenario
     * @return bool
     */
    public function isValid($values, $scenario = '')
    {
        return true;
    }

    /**
     * @param $attr
     * @return null|mixed
     */
    protected function getOldAttribute($attr)
    {
        return isset($this->_saved[$attr]) ? $this->_saved[$attr] : null;
    }

    /**
     * @param array $options
     * @throws Exception
     * @return bool
     */
    public function insert(array $options = [])
    {
        if (!$this->getIsNew()) {
            throw new Exception('The document can not be inserted to database because it is not new.');
        }


        if ($this->_id === null || self::isValidMongoId($this->_id)) {
            $this->_id = new MongoId($this->_id);
        }

        if ($this->beforeInsert() === false) {
            return false;
        }

        if ($this->beforeSave() === false) {
            return false;
        }

        if (!Buffer::add(Adapter::INSERT, $this, $options)) {
            if (!$this->forceInsert($options)) {
                return false;
            }
        }

        $this->setIsNew(false);
        $this->afterInsert();
        $this->afterSave();
        static::getCollection($this->getConnectionName())->addToIdentityMap($this);

        return true;
    }

    protected function forceInsert(array $options = [])
    {
        $document = $this->getRawDocument();
        $connection_name = $this->getConnectionName();
        if (static::getCollection($connection_name)->insert($document, $options) !== false) {
            $this->commit($document);
            return true;
        }

        return false;
    }

    public function update(array $options = [])
    {
        if ($this->getIsNew()) {
            throw new Exception('The document can not be updated to database because it is new.');
        }

        if ($this->beforeUpdate() === false) {
            return false;
        }

        if ($this->beforeSave() === false) {
            return false;
        }

        $savedValues = $this->getChangedAttributes();
        if ($savedValues === []) {
            return true;
        }

        $options['multiple'] = false;

        if (!Buffer::add(Adapter::UPDATE, $this, $options)) {
            if (!$this->forceUpdate($options)) {
                return false;
            }
        }

        $this->setIsNew(false);
        $this->afterUpdate();
        $this->afterSave();

        return true;
    }

    /**
     * @return array|bool
     */
    protected function getChangedAttributes()
    {
        return static::arrayDiffMulti($this->_saved, $this->getRawDocument());
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function forceUpdate(array $options = [])
    {
        $diff = $this->getChangedAttributes();
        $values = $this->getRawDocument(array_keys($diff));
        $data = $this->parseUpdateParams($values);
        $connection_name = $this->getConnectionName();
        if (static::getCollection($connection_name)->update(['_id' => $this->_id], $data, $options) !== false) {
            $this->commit();
            return true;
        }

        return false;
    }

    /**
     * @param array $values
     * @return array
     */
    public function parseUpdateParams(array $values)
    {
        $result = [];
        $params = $this->attrParams(); // todo: Recursive
        foreach ($values as $key => $val) {
            if (isset($params[$key]['operation_update'])) {
                if ($params[$key]['operation_update'] == '$inc') {
                    $val = $val - $this->_saved[$key];
                }
                $result[$params[$key]['operation_update']][$key] = $val;
            } else {
                $result['$set'][$key] = $val;
            }
        }

        return $result;
    }

    public function attrParams()
    {
        return [];
    }

    /**
     * Remove record from this collection
     * @param array $options
     * @throws Exception
     * @return bool
     */
    public function delete(array $options = [])
    {
        if ($this->getIsNew()) {
            return false;
        }

        if ($this->beforeDelete() === false) {
            return false;
        }

        $options['multiple'] = false;

        if (!Buffer::add(Adapter::DELETE, $this, $options)) {
            if (!$this->forceDelete($options)) {
                return false;
            }
        }

        static::getCollection()->removeFromIdentityMap($this);
        $this->_id = null;
        $this->setIsNew(true);
        $this->afterDelete();

        return true;
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function forceDelete(array $options = [])
    {
        $connection_name = $this->getConnectionName();
        return static::getCollection($connection_name)->deleteAll(['_id' => $this->_id], $options);
    }
}
