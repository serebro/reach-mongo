<?php

namespace Reach\Mongo\Document;

use MongoCursor;
use MongoDate;
use MongoGridFS;
use MongoGridFSFile;
use Reach\HookableTrait;
use Reach\Model;
use Reach\Mongo\Collection;
use Reach\Mongo\CollectionTrait;
use Reach\Mongo\Connection;
use Reach\Mongo\ConnectionManager;
use Reach\Mongo\Criteria;
use Reach\Mongo\DocumentInterface;
use Reach\Mongo\DocumentTrait;
use Reach\Mongo\ResultSet;

class File extends Model implements DocumentInterface
{

    use HookableTrait;
    use CollectionTrait;
    use DocumentTrait;

    const SOURCE_FILENAME = 1;
    const SOURCE_CONTENT = 2;
    const SOURCE_UPLOAD = 3;


    /** @var MongoDate */
    public $uploadDate;

    /** @var int */
    public $length = 0;

    /** @var string */
    public $md5 = '';

    /** @var  MongoGridFSFile */
    protected $gridFSFile;

    private $_source_type = 0;

    private $_source = null;


    final public function __construct(array $attributes = null)
    {
        parent::__construct($attributes);
        $this->setIsNew(true);
    }

    public static function getPrimaryKey() {
        return '_id';
    }

    public static function findOne($query = [], array $fields = [], $as_array = false)
    {
        $pk = static::getPrimaryKey();
        $query = Criteria::normalize($query, $pk);
        $gridFSFile = static::getCollection()->findOne($query, $fields);
        $model = static::instantiate($gridFSFile->file);
        $model->gridFSFile = $gridFSFile;
        return $model;
    }

    /**
     * @return MongoGridFS
     */
    public static function getCollection($connection_name = null)
    {
        return static::getConnection($connection_name)->getGridFSCollection(static::getCollectionName());
    }

    /**
     * @return Connection
     */
    public static function getConnection($connection_name = null)
    {
        return ConnectionManager::getConnection($connection_name);
    }

    /**
     * @return string
     */
    public static function getCollectionName()
    {
        return str_replace(['\\', '/', '-'], '_', strtolower(get_called_class()));
    }

    /**
     * @param null $document
     * @return Document|null
     */
    public static function instantiate($document = null)
    {
        /** @var DocumentInterface $model */
        $model = parent::instantiate();
        foreach ($document as $attr => $value) {
            if ($model->hasAttribute($attr)) {
                $model->$attr = $value;
            }
        }
        //$model->commit();
        //static::getCollection()->addToIdentityMap($model);
        return $model;
    }

    public static function resultSet(MongoCursor $cursor)
    {
        return new ResultSet($cursor, get_called_class());
    }

    /**
     * @param int    $source_type
     * @param string $source
     * @return File
     */
    public function setContent($source_type, $source = null)
    {
        $this->_source_type = $source_type;
        $this->_source = $source;
        return $this;
    }

    /**
     * @return \stream
     */
    public function getResource()
    {
        return $this->gridFSFile->getResource();
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->gridFSFile->getBytes();
    }

    public function save(array $options = [])
    {
        if ($this->beforeSave() === false) {
            return false;
        }

        if ($this->_is_new) {
            $attrs = $this->getAttributes();
            unset($attrs['_id']);
            if ($this->_source_type === self::SOURCE_FILENAME) {
                $result = static::getCollection()->storeFile($this->_source, $attrs, $options);
            } else if ($this->_source_type === self::SOURCE_CONTENT) {
                $result = static::getCollection()->storeBytes($this->_source, $attrs, $options);
            } else {
                $result = static::getCollection()->storeUpload($this->_source, $attrs);
            }

            if ($result !== false) {
                $this->_source = null;
                $this->_id = $result;
                $this->gridFSFile = static::getCollection()->findOne(['_id' => $this->_id]);
                $this->setAttributes($this->gridFSFile->file);
                $this->setIsNew(false);
                $this->afterSave();
                return true;
            }
        } else {
        }

        return false;
    }

    public function delete(array $options = [])
    {
        // TODO: Implement delete() method.
    }

    public function commit()
    {
        // TODO: Implement commit() method.
    }

    public function getIsNew()
    {
        // TODO: Implement getIsNew() method.
    }

    public function setIsNew($value)
    {
        // TODO: Implement setIsNew() method.
    }

    public function getRawDocument(array $attributes = null)
    {
        // TODO: Implement getRawDocument() method.
    }
}
