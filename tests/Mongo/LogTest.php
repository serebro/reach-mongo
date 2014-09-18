<?php

namespace Mongo;

use Reach\Mongo\ConnectionManager;

class LogTest extends \PHPUnit_Framework_TestCase
{

    public function testMongolog()
    {
        $config = [
            'database'       => 'reach_testing',
            'host'           => 'localhost',
            'port'           => 27017,
            'syslog' => [
                'class' => '\Reach\Mongo\Log',
            ],
            'logger' => [
                'class' => '\Reach\Mongo\Logger',
            ],
        ];
        ConnectionManager::registerConnection($config, 'mongolog');
        $connection = ConnectionManager::getConnection('mongolog');
        $connection->getCollection('test')->insert(['name' => 'John']);
    }

    public function testProfiling()
    {
//        $connection = ConnectionManager::getConnection();
//
//        $all = 2;
//        $slow = 1;
//        $none = 0;
//
//        $connection->getDb()->command(['profile' => $all/*, 'slowms' => 100*/]);
//        $connection->getCollection('test')->insert(['name' => 'John1']);
//        $connection->getCollection('test')->find();
//        $connection->getDb()->command(['profile' => 0]);
//        $log = $connection->getProfileLog(1);
    }
}
