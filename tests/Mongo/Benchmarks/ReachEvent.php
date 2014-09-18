<?php

namespace Mongo\Benchmarks;

use Athletic\AthleticEvent;
use Exception;
use Model\Mongo\TestSchema;
use Reach\Mongo\Buffer;
use Reach\Mongo\Connection;
use Reach\Mongo\ConnectionManager;
use Reach\Mongo\ResultSet;

class ReachEvent extends AthleticEvent
{

    /** @var Connection _connection */
    private $_connection;

    private $_ids = [];

    protected function classSetUp()
    {
        $config = [
            'database' => 'reach_testing',
            'host'     => 'localhost',
            'port'     => 27017,
            'options'  => ['connect' => true, 'socketTimeoutMS' => 60000]
        ];

        ConnectionManager::registerConnection($config);

        /** @var Connection _connection */
        $this->_connection = ConnectionManager::getConnection();

        TestSchema::getCollection()->drop();
        TestSchema::getCollection()->addIndexes(
            [
                [
                    'object.type',
                    'object.rnd',
                    'created',
                ]

            ]
        );

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

        $model = new TestSchema();
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
        $model = TestSchema::findOne($_id);
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
        $model = TestSchema::findOne($_id);

        Buffer::open();
        for ($i = 0; $i < 10; $i++) {
            $model->title = 'updating ' . $i;
            if (!$model->save()) {
                throw new Exception(__METHOD__);
            }
        }
        Buffer::flush();
        Buffer::close();
    }

    /**
     * @iterations 500
     */
    public function findingOneByIndexedField()
    {
        $model = TestSchema::findOne(['object.type' => 1]);

        if (!$model instanceof TestSchema) {
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
        $model = TestSchema::findOne($_id);

        if (!$model instanceof TestSchema) {
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
            $model = TestSchema::findOne($_id);

            if (!$model instanceof TestSchema) {
                throw new Exception(__METHOD__);
            }
        }
    }

    /**
     * @iterations 500
     */
    public function findingAllByIndexField()
    {
        $resultSet = TestSchema::find(['object.type' => 1]);

        if (!$resultSet instanceof ResultSet || $resultSet->count() < 1) {
            throw new Exception(__METHOD__);
        }

        if (!$model = $resultSet[rand(0, count($resultSet) - 1)]) {
            throw new Exception(__METHOD__);
        }
    }

    /**
     * @iterations 500
     */
    public function findingAllByIndexFieldAndToArray()
    {
        $array = TestSchema::find(['object.type' => 1])->asArray();
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
        $model = TestSchema::findOne($_id);

        if (!$model instanceof TestSchema) {
            throw new Exception(__METHOD__);
        }

        if (!$model->delete()) {
            throw new Exception(__METHOD__);
        }
    }
}
