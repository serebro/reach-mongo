<?php

namespace Mongo;

use Model\Mongo\TestSchema;
use MongoId;
use Reach\Mongo\Collection;

class DocumentTest extends \PhactoryTestCase
{

    public function testConstructor()
    {
        $model = new TestSchema();
        $this->assertEmpty($model->title);
        $this->assertTrue($model->init_test);

        $model = new TestSchema();
        $this->assertTrue($model->init_test);
        $this->assertTrue($model->getIsNew());
    }

    public function testMagic()
    {
        $model = new TestSchema(['title' => 1]);

        // __get
        $this->assertTrue($model->isNew);

        // __set
        $model->isNew = false;
        $this->assertFalse($model->isNew);

        // __isset
        $this->assertTrue(isset($model->isNew));
    }

    public function testAttributes()
    {
        $model = new TestSchema();
        $attributes = $model->attributes();
        $this->assertEquals(
            [
                '_id'       => '',
                'title'     => '',
                'object'    => '',
                'init_test' => '',
                'created'   => '',
            ],
            $attributes
        );
    }

    public function testSetAttributes()
    {
        $model = new TestSchema();

        $obj = new \stdClass();
        $obj->title = 'Title1';
        $model->setAttributes($obj);
        $this->assertEquals('Title1', $model->title);

        $model->setAttributes(['title' => 'Title2']);
        $this->assertEquals('Title2', $model->title);

        $model->setAttributes(123);
        $this->assertEquals('Title2', $model->title);

        $model->setAttributes(null);
        $this->assertEquals('Title2', $model->title);
    }

    public function testGetAttributes()
    {
        $date = new \MongoDate();
        $model = new TestSchema(['title' => 'Title', 'created' => $date]);
        $actual = $model->getAttributes();
        $expected = [
            '_id'       => null,
            'title'     => 'Title',
            'object'    => null,
            'init_test' => true,
            'created'   => $date,
        ];
        $this->assertEquals($expected, $actual);

        $actual = $model->getAttributes(['title', 'object']);
        $expected = [
            'title'  => 'Title',
            'object' => null,
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testEnsureMongoId()
    {
        $mongoId1 = new MongoId();
        $mongoId2 = new MongoId();
        $this->assertEquals($mongoId1, $mongoId1);
        $this->assertEquals($mongoId1, Collection::ensureMongoId((string)$mongoId1));
        $this->assertEquals(
            [$mongoId1, $mongoId2],
            Collection::ensureMongoId([(string)$mongoId1, (string)$mongoId2])
        );
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Attribute "_id" was not defined.
     */
    public function testLoad()
    {
        $id = new MongoId();
        $model = new TestSchema(['_id' => $id, 'title' => 'TitleLoad']);
        $model->save();

        $model = new TestSchema(['_id' => $id]);
        $this->assertTrue($model->load());
        $this->assertEquals('TitleLoad', $model->title);

        TestSchema::getCollection()->clearIdentityMap();
        $model = new TestSchema(['_id' => $id]);
        $this->assertTrue($model->load());
        $this->assertEquals('TitleLoad', $model->title);

        $model = new TestSchema(['_id' => new MongoId()]);
        $this->assertFalse($model->load());

        // To test exception
        $model = new TestSchema();
        $model->load();
    }

    public function testGetConnection()
    {
        $this->assertInstanceOf('\Reach\Mongo\Connection', TestSchema::getConnection());
    }

    public function testGetCollection()
    {
        $this->assertEquals('model_mongo_testschema', TestSchema::getParentCollectionName());
        $this->assertEquals('test_model_mongo_document', TestSchema::getCollectionName());
        $this->assertInstanceOf('\Reach\Mongo\Collection', TestSchema::getCollection());
        $this->assertInstanceOf('\MongoCollection', TestSchema::getMongoCollection());
    }

    public function testIndexes()
    {
        TestSchema::drop();
        TestSchema::setup();
    }

    public function testSave()
    {
        $model = new TestSchema();
        $model->title = '123';
        $this->assertTrue($model->getIsNew());
        $this->assertTrue($model->save());
        $this->assertFalse($model->getIsNew());
        $this->assertInstanceOf('\MongoId', $model->_id);
        $this->assertNotNull($model->created);

        $model->title = '333';
        $this->assertTrue($model->save());
    }

    public function testFindOne()
    {
        $model = new TestSchema();
        $model->title = '123';
        $model->object = ['item' => 1];
        $model->save();
        $mongoId = $model->_id;

        $this->assertNull(TestSchema::findOne(123));
        $this->assertNull(TestSchema::findOne('123'));
        $this->assertNull(TestSchema::findOne(new MongoId()));
        $this->assertInstanceOf('\Reach\Mongo\Document\Schema', TestSchema::findOne(null));

        unset($model);
        $model = TestSchema::findOne($mongoId);
        $this->assertInstanceOf('\Reach\Mongo\Document\Schema', $model);
        $this->assertEquals($mongoId, $model->_id);

        $model = TestSchema::getIdentityMap($mongoId);
        $this->assertEquals($mongoId, $model->_id);
        TestSchema::getCollection()->clearIdentityMap();
        $this->assertFalse(TestSchema::getIdentityMap($mongoId));

        $model = TestSchema::findOne((string)$mongoId);
        $this->assertEquals($mongoId, $model->_id);

        TestSchema::getCollection()->clearIdentityMap();
        $model = TestSchema::findOne(['_id' => $mongoId]);
        $this->assertEquals($mongoId, $model->_id);

        TestSchema::getCollection()->clearIdentityMap();
        $model = TestSchema::findOne(['_id' => (string)$mongoId]);
        $this->assertEquals($mongoId, $model->_id);

        TestSchema::getCollection()->clearIdentityMap();
        $model = TestSchema::findOne($mongoId, ['title']);
        $this->assertEquals($mongoId, $model->_id);
        $this->assertEquals('123', $model->title);
        $this->assertEmpty($model->object);

        TestSchema::getCollection()->clearIdentityMap();
        $document = TestSchema::findOne($mongoId, ['title'], true);
        $this->assertEquals(['_id' => $mongoId, 'title' => '123'], $document);
    }

    public function testFind()
    {
        $resultSet = TestSchema::find(['a' => 123]);
        $this->assertInstanceOf('\Reach\ResultSetInterface', $resultSet);
        $this->assertEquals(0, $resultSet->count());

        $cursor = TestSchema::find(['a' => 123], ['title']);
        $this->assertInstanceOf('\MongoCursor', $cursor);
        $this->assertEquals(0, $cursor->count());

        $model = new TestSchema(['title' => 1, 'object' => ['a' => 1]]);
        $model->save();

        $model = new TestSchema(['title' => 2, 'object' => ['a' => 2]]);
        $model->save();

        $model = new TestSchema(['title' => 3, 'object' => ['a' => 2]]);
        $model->save();

        $resultSet = TestSchema::find(['title' => '111']);
        $this->assertInstanceOf('\Reach\Mongo\ResultSet', $resultSet);
        $this->assertEquals(0, count($resultSet));

        $resultSet = TestSchema::find(['title' => ['$gt' => 1]]);
        $this->assertInstanceOf('\Reach\Mongo\ResultSet', $resultSet);
        $this->assertEquals(2, count($resultSet));

        /** @var \MongoCursor $cursor */
        $cursor = TestSchema::find(['title' => ['$gt' => 1]], ['title']);
        $this->assertInstanceOf('\MongoCursor', $cursor);
        $this->assertEquals(2, $cursor->count());
        $cursor->getNext();
        $array = $cursor->getNext();
        $this->assertFalse(isset($array['object']));

        // Schema::count
        $this->assertEquals(2, TestSchema::count(['object.a' => 2]));
        $this->assertEquals(0, TestSchema::count(['object.a' => 0]));
    }

    public function testFormatDate()
    {
        $timestamp = 1406402147;
        $formatted_date = TestSchema::date($timestamp);
        $this->assertEquals('2014-07-26T19:15:47+0000', $formatted_date);
    }

    public function testEvents()
    {
        $that = $this;
        $that->sequence = [];
        $model = new TestSchema();
        $model->on(
            'beforeInsert',
            function ($event) use ($that, $model) {
                $that->assertInstanceOf('\Reach\Event', $event);
                $that->assertEquals($model->_id, $event->model->_id);
                $that->sequence[] = 0;
            }
        );
        $model->on(
            'afterInsert',
            function ($event) use ($that, $model) {
                $that->assertInstanceOf('\Reach\Event', $event);
                $that->assertEquals($model->_id, $event->model->_id);
                $that->sequence[] = 1;
            }
        );
        $model->on(
            'beforeUpdate',
            function ($event) use ($that, $model) {
                $that->assertInstanceOf('\Reach\Event', $event);
                $that->assertEquals($model->_id, $event->model->_id);
                $that->sequence[] = 2;
            }
        );
        $model->on(
            'afterUpdate',
            function ($event) use ($that, $model) {
                $that->assertInstanceOf('\Reach\Event', $event);
                $that->assertEquals($model->_id, $event->model->_id);
                $that->sequence[] = 3;
            }
        );
        $model->on(
            'beforeDelete',
            function ($event) use ($that, $model) {
                $that->assertInstanceOf('\Reach\Event', $event);
                $that->assertEquals($model->_id, $event->model->_id);
                $that->sequence[] = 4;
            }
        );
        $model->on(
            'afterDelete',
            function ($event) use ($that, $model) {
                $that->assertInstanceOf('\Reach\Event', $event);
                $that->assertEquals($model->_id, $event->model->_id);
                $that->sequence[] = 5;
                $that->assertEquals([0, 1, 2, 3, 2, 4, 5], $that->sequence);
            }
        );
        $model->save();
        $model->title = '123';
        $model->save();
        $model->save();
        $model->delete();
    }

    public function testAnotherConnection()
    {
        $model = TestSchema::getCollection('another')->findOne();
        $this->assertNull($model);

        $model = new TestSchema(['title' => '123']);
        $model->setConnectionName('another')->save();
        $model = TestSchema::getCollection('another')->findOne($model->_id);
        $this->assertInstanceOf('\Model\Mongo\TestSchema', $model);
        $this->assertEquals('123', $model->title);

        \Reach\Service\Container::get('another')->selectDB('reach_testing2')->drop();
        \Reach\Service\Container::get('another')->close();
    }

    public function testGetStringId()
    {
        $_id = new MongoId();
        $string_id = (string)$_id;

        $model = new TestSchema();
        $model->_id = $_id;
        $model->object = $_id;

        $this->assertEquals($string_id, $model->getStringId());
        $this->assertEquals($string_id, $model->getStringId('object'));
        $this->assertEquals($string_id, $model->stringId);
        $this->assertEquals('', $model->getStringId('undefined'));
    }

    public function testIdentityMapOverflow()
    {
        $model = new TestSchema(['title' => 'TitleFirst']);
        $model->save();
        $first_id = $model->_id;
        for ($i = 0; $i < 1000; $i++) {
            $model = new TestSchema(['title' => 'Title' . $i]);
            $model->save();
        }
        $this->assertFalse(TestSchema::getCollection()->getIdentityMap($first_id));
        TestSchema::deleteAll();
    }
}
