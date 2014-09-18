<?php


class ConnectionTest extends PhactoryTestCase
{

    public function testConnection()
    {
        $dsn = self::$connection->getDsn();
        $this->assertEquals('mongodb://localhost:27017/reach_testing', $dsn);

        $mongoClient = self::$connection->createConnection($dsn, self::$config['options']);
        $this->assertInstanceOf('\MongoClient', $mongoClient);

        $db = self::$connection->getDb();
        $this->assertInstanceOf('\MongoDB', $db);

        $collection = self::$connection->getMongoCollection('user');
        $this->assertInstanceOf('\MongoCollection', $collection);

        $dbs = self::$connection->listDatabases();
        $this->assertEquals(['databases', 'totalSize', 'ok'], array_keys($dbs));

//        self::$connection->close();
//        $this->assertEquals([], PHPUnit_Framework_Assert::readAttribute(self::$connection, '_config'));
//        $this->assertEquals([], PHPUnit_Framework_Assert::readAttribute(self::$connection, '_collections'));
//        $this->assertNull(PHPUnit_Framework_Assert::readAttribute(self::$connection, '_mongoClient'));
    }
}
