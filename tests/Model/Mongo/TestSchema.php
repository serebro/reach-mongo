<?php

namespace Model\Mongo;

use Reach\Mongo\Document\Schema;

class TestSchema extends Schema
{

    /** @var string */
    public $title = '';

    /** @var null */
    public $object = null;

    /** @var \MongoDate */
    public $created;

    /** @var bool */
    public $init_test = false;

    public $not_stored = true;

    public function attributes()
    {
        return [
            '_id'       => '',
            'title'     => '',
            'object'    => '',
            'init_test' => '',
            'created'   => '',
        ];
    }

    public static function getCollectionName()
    {
        return 'test_model_mongo_document';
    }

    public static function getParentCollectionName()
    {
        return parent::getCollectionName();
    }

    public function init()
    {
        $this->init_test = true;
    }

    public function beforeInsert()
    {
        $this->created = new \MongoDate();
        return parent::beforeInsert();
    }

    public function beforeSave()
    {
        if ($this->getIsNew()) {
            $this->created = new \MongoDate();
        }

        return true;
    }

    public function isValid($scenario = '')
    {
        if (!is_string($this->title)) {
            return false;
        }

        return true;
    }

    public function setIsNew($value)
    {
        parent::setIsNew($value);
    }

    public static function getIdentityMap($id)
    {
        return static::getCollection()->getIdentityMap($id);
    }

    public static function indexes()
    {
        return [
            'title',
            ['key' => ['init_test'], 'options' => ['name' => 'initTest']]
        ];
    }

    public static function query(\Reach\Mongo\CriteriaInterface $criteria = null)
    {
        return new TestSchemaQuery($criteria, get_called_class());
    }
}
