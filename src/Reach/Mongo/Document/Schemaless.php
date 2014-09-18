<?php

namespace Reach\Mongo\Document;

use Exception;
use MongoId;
use Reach\EventableTrait;
use Reach\HookableTrait;
use Reach\Mongo\CollectionTrait;
use Reach\Mongo\DocumentInterface;
use Reach\Mongo\DocumentTrait;

class Schemaless implements DocumentInterface
{

    use EventableTrait;
    use HookableTrait;
    use CollectionTrait;
    use DocumentTrait;


    /** @var array */
    private $_attributes = [];


    final public function __construct(array $attributes = null)
    {
        if ($attributes) {
            $this->initAttributes($attributes);
        }
        $this->setIsNew(true);
    }

    protected function initAttributes(array $attributes)
    {
        $this->_attributes = [];
        foreach ($attributes as $property => $value) {
            $this->{$property} = $value;
            $this->_attributes[] = $property;
        }
    }

    public function attributes(array $include = null)
    {
        // todo $include

        $attributes = [];
        foreach ($this->_attributes as $attribute => $value) {
            $attributes[$attribute] = $this->getType($value);
        }

        return $attributes;
    }

    public function __call($method, $arguments)
    {
        $prefix = strtolower(substr($method, 0, 3));
        if ($prefix === 'get') {
            $attribute = lcfirst(substr($method, 3));
            if ($this->hasAttribute($attribute)) {
                return $this->$attribute;
            } else {
                return null;
            }
        } else if ($prefix === 'set') {
            $attribute = lcfirst(substr($method, 3));
            if (!empty($arguments[0])) {
                $this->$attribute = $arguments[0];
                if (!array_key_exists($attribute, $this->_attributes)) {
                    $this->_attributes[] = $attribute;
                }
            }
            return $this;
        }

        return call_user_func_array([static::getCollection(), $method], $arguments);
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
        if (!array_key_exists($property, $this->_attributes)) {
            $this->_attributes[] = $property;
        }
    }

    public static function getPrimaryKey()
    {
        return '_id';
    }

    /**
     * @param null $document
     * @return self
     */
    public static function instantiate($document = null)
    {
        $className = get_called_class();
        /** @var self $model */
        $model = new $className();
        if ($document) {
            foreach ($document as $property => $value) {
                $model->$property = $value;
            }
        }

        static::getCollection()->addToIdentityMap($model);
        return $model;
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

        $values = [];
        foreach ($this->_attributes as $property) {
            if ($this->hasAttribute($property)) {
                $values[$property] = $this->$property;
            }
        }

        if (empty($values)) {
            return false;
        }

        $this->_id = new MongoId($this->_id);

        if ($this->beforeInsert() === false) {
            return false;
        }

        if ($this->beforeSave() === false) {
            return false;
        }

        $values['_id'] = $this->_id;
        //$values = $this->getSerializedAttributes();
        $connection_name = $this->getConnectionName();
        if (!static::getCollection($connection_name)->insert($values, $options) !== false) {
            return false;
        }

        $this->setIsNew(false);
        $this->afterInsert();
        $this->afterSave();
        static::getCollection($this->getConnectionName())->addToIdentityMap($this);

        return true;
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

        $options['multiple'] = false;

        $values = [];
        foreach ($this->_attributes as $property) {
            if (isset($this->$property)) {
                $values[$property] = $this->$property;
            }
        }

        if (empty($values)) {
            return false;
        }

        $values['_id'] = $this->_id;

        $connection_name = $this->getConnectionName();
        if (!static::getCollection($connection_name)->save($values, $options) !== false) {
            return false;
        }

        $this->setIsNew(false);
        $this->afterUpdate();
        $this->afterSave();

        return true;
    }

    public function delete(array $options = [])
    {
        if ($this->getIsNew()) {
            return false;
        }

        if ($this->beforeDelete() === false) {
            return false;
        }

        $options['multiple'] = false;

        $connection_name = $this->getConnectionName();
        $result = static::getCollection($connection_name)->remove(['_id' => $this->_id], $options);

        static::getCollection()->removeFromIdentityMap($this);
        $this->_id = null;
        $this->setIsNew(true);
        $this->afterDelete();

        return true;
    }

    public function commit()
    {
    }

    public function hasAttribute($attribute)
    {
        return isset($this->$attribute);
    }

    public function getType($var)
    {
        if (is_object($var)) {
            return get_class($var);
        }
        if (is_null($var)) {
            return 'null';
        }
        if (is_string($var)) {
            return 'string';
        }
        if (is_array($var)) {
            return 'array';
        }
        if (is_int($var)) {
            return 'integer';
        }
        if (is_bool($var)) {
            return 'boolean';
        }
        if (is_float($var)) {
            return 'float';
        }
        if (is_resource($var)) {
            return 'resource';
        }
        //throw new NotImplementedException();
        return null;
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getRawDocument(array $attributes = null)
    {
        $doc = [];
        foreach ($this->attributes($attributes) as $attribute => $value) {
            $doc[$attribute] = $value;
        }

        return $doc;
    }
}
