<?php

use Phactory\Mongo\Phactory;
use Reach\Mongo\Connection;
use Reach\Mongo\ConnectionManager;

/**
 * Test Case Base Class for using Phactory *
 */
abstract class PhactoryTestCase extends \PHPUnit_Framework_TestCase
{

    /** @var  Connection */
    protected static $connection;

    /** @var  Phactory */
    protected static $phactory;

    /** @var  array */
    protected static $config;


    public static function setUpBeforeClass()
    {
        self::$config = [
            'database' => 'reach_testing',
            'host'     => 'localhost',
            'port'     => 27017,
            //'username' => 'web',
            //'password' => 'server',
            'options'  => ['connect' => true, 'socketTimeoutMS' => 60000]
        ];

        ConnectionManager::registerConnection(self::$config);
        self::$connection = ConnectionManager::getConnection();

        if (!self::$phactory) {
            if (!self::$connection->getDb() instanceof \MongoDB) {
                throw new \Exception('Could not connect to MongoDB');
            }

            self::$phactory = new Phactory(self::$connection->getDb());
            self::$phactory->reset();
        }

        //set up Phactory db connection
        self::$phactory->reset();
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$connection->getDb()->getCollectionNames() as $collection) {
            self::$connection->getDb()->$collection->drop();
        }
    }

    protected function setUp()
    {
    }

    protected function tearDown()
    {
        self::$phactory->recall();
    }
}