<?php

use Model\Mongo\TestSchema;

class CollectionTest extends PhactoryTestCase
{

    public function testStats()
    {
        //$stats = TestSchema::getCollection()->stats();
        //$this->assertTrue(is_array($stats));
        //$this->assertEquals(['ok' => 0, 'errmsg' => 'Collection [reach_testing.test_model_mongo_document] not found.'], $stats);

        $model = new TestSchema(['title' => 'CollectionStatsTitle']);
        $model->save();
        $stats = TestSchema::getCollection()->stats();
        $this->assertTrue(is_array($stats));
        $this->assertEquals(1, $stats['ok']);
    }

    public function testBatchInsert()
    {
        $resultSet = new \Reach\ResultSet();
        $model = new \stdClass();
        $model->title = 'TitleStdClass';
        $resultSet->append($model);
        $model = new TestSchema(['title' => 'TestSchema1']);
        $resultSet->append($model);
        $model = new TestSchema(['title' => 'TestSchema2']);
        $resultSet->append($model);

        $result = TestSchema::batchInsert($resultSet);
        $this->assertInstanceOf('\Reach\ResultSet', $result);
        $this->assertEquals(2, $result->count());
        $this->assertFalse($result->first()->getIsNew());

        $resultSet = new \Reach\ResultSet();
        $model = new \stdClass();
        $model->title = 'TitleStdClass';
        $resultSet->append($model);

        $result = TestSchema::batchInsert($resultSet);
        $this->assertInstanceOf('\Reach\ResultSet', $result);
        $this->assertEquals(0, $result->count());
    }
}
