<?php

namespace Mongo;

use Reach\Mongo\ConnectionManager;

class ConnectionManagerTest extends \PHPUnit_Framework_TestCase
{

    public function testConnection()
    {
        $config = [
            'database' => 'reach_testing',
            'host'     => 'localhost',
            'port'     => 27017,
            //'username' => 'web',
            //'password' => 'server',
            'options'  => ['connect' => true, 'socketTimeoutMS' => 60000]
        ];

        ConnectionManager::registerConnection($config);
        $connection = ConnectionManager::getConnection();
        $this->assertInstanceOf('\Reach\Mongo\Connection', $connection);
        $this->assertTrue(method_exists($connection, 'getDb'));
        $this->assertInstanceOf('\MongoDB', $connection->getDb());

        $config2 = [
            'database' => 'reach_testing2',
        ];
        ConnectionManager::registerConnection($config2, 'another');
        $connection2 = ConnectionManager::getConnection('another');
        $this->assertInstanceOf('\Reach\Mongo\Connection', $connection2);
        $this->assertEquals('reach_testing2', $connection2->getDbName());

        $this->assertInstanceOf('\Reach\Mongo\Connection', ConnectionManager::getConnection('default'));
        $this->assertEquals('reach_testing', $connection->getDbName());
    }
}
