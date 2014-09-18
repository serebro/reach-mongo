<?php

namespace Mongo\Benchmarks;

use Athletic\AthleticEvent;
use Exception;
use Model\Mongo\TestDocumentPhalcon;

class PhalconEvent extends AthleticEvent
{

    private $_ids = [];

    protected function classSetUp()
    {
        $di = new \Phalcon\DI\FactoryDefault();
        $di->set('collectionManager', function() {
            return new \Phalcon\Mvc\Collection\Manager();
        }, true);
        $di->set('mongo', function() {
            $mongo = new \MongoClient('localhost:27017', ['connect' => true, 'socketTimeoutMS' => 60000]);
            return $mongo->selectDB('reach_testing');
        }, true);


        /** @var \MongoDB $db */
        $db = $di->get('mongo');
        $collection = $db->selectCollection('test_model_mongo_document');
        $collection->drop();

        /**
         * @todo http://php.net/manual/en/mongocollection.ensureindex.php
         */
        $collection->ensureIndex(['object.type' => 1]);
        $collection->ensureIndex(['object.rnd' => 1]);
        $collection->ensureIndex(['created' => 1]);

        $this->_memory = memory_get_usage(true);
    }

    protected function classTearDown()
    {
        $memory = memory_get_usage(true);
        $size = $memory - $this->_memory;
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        echo 'Memory usage: ' . (@round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i]);
    }

    /**
     * @iterations 500
     */
    public function inserting()
    {
        $length = 100;
        $rnd = rand(0, 999999999);
        $randomString = substr(
            str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
            0,
            $length
        );

        $model = new TestDocumentPhalcon();
        $model->title = $randomString;
        $model->object = ['rnd' => $rnd, 'type' => $rnd > 999999999 / 2 ? 1 : 2];
        $model->save();
        $this->_ids[] = $model->_id;
    }

    /**
     * @iterations 500
     */
    public function updatingSimple()
    {
        $idx = rand(0, count($this->_ids) - 1);
        $_id = $this->_ids[$idx];
        $model = TestDocumentPhalcon::findById($_id);
        $model->title = 'updating';
        if (!$model->save()) {
            throw new Exception(__METHOD__);
        }
    }

    /**
     * @iterations 500
     */
    public function updating10times()
    {
        $idx = rand(0, count($this->_ids) - 1);
        $_id = $this->_ids[$idx];
        $model = TestDocumentPhalcon::findById($_id);

        for ($i = 0; $i < 10; $i++) {
            $model->title = 'updating ' . $i;
            if (!$model->save()) {
                throw new Exception(__METHOD__);
            }
        }
    }

    /**
     * @iterations 500
     */
    public function findingOneByIndexedField()
    {
        $model = TestDocumentPhalcon::findFirst(['object.type' => 1]);

        if (!$model instanceof TestDocumentPhalcon) {
            throw new Exception(__METHOD__);
        }
    }

    /**
     * @iterations 500
     */
    public function findingOneByPrimaryKey()
    {
        $idx = rand(0, count($this->_ids) - 1);
        $_id = $this->_ids[$idx];
        $model = TestDocumentPhalcon::findById($_id);

        if (!$model instanceof TestDocumentPhalcon) {
            throw new Exception(__METHOD__);
        }
    }

    /**
     * @iterations 500
     */
    public function findingOneByPrimaryKey10Times()
    {
        $idx = rand(0, count($this->_ids) - 1);
        $_id = $this->_ids[$idx];

        for ($i = 0; $i < 10; $i++) {
            $model = TestDocumentPhalcon::findById($_id);

            if (!$model instanceof TestDocumentPhalcon) {
                throw new Exception(__METHOD__);
            }
        }
    }

    /**
     * @iterations 500
     */
    public function findingAllByIndexField()
    {
        $array = TestDocumentPhalcon::find(['object.type' => 1]);
        if (!count($array)) {
            throw new Exception(__METHOD__);
        }

        $model = $array[rand(0, count($array)-1)];
    }

    /**
     * @iterations 500
     */
    public function findingAllByIndexFieldAndToArray()
    {
        $array = TestDocumentPhalcon::find(['object.type' => 1]);
        if (!count($array)) {
            throw new Exception(__METHOD__);
        }
    }

    /**
     * @iterations 500
     */
    public function deleteOneByPrimaryKey()
    {
        $idx = rand(0, count($this->_ids) - 1);
        $_id = $this->_ids[$idx];
        array_splice($this->_ids, $idx, 1);
        $model = TestDocumentPhalcon::findById($_id);

        if (!$model instanceof TestDocumentPhalcon) {
            throw new Exception(__METHOD__);
        }

        if (!$model->delete()) {
            throw new Exception(__METHOD__);
        }
    }

}