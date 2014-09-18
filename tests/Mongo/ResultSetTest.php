<?php

namespace Mongo;

use Model\Mongo\TestSchema;

class ResultSetTest extends \PhactoryTestCase
{

    private $_ids = [];

    public function setUp()
    {
        TestSchema::deleteAll([]);

        for ($i = 0; $i < 10; $i++) {
            $model = new TestSchema(
                [
                    'title'  => 'Title' . $i,
                    'object' => ['id' => $i, 'name' => 'Obj' . $i]
                ]
            );
            $model->save();
            $this->_ids[] = $model->id;
        }
    }

    public function tearDown()
    {
        TestSchema::deleteAll([]);
    }

    public function testHasNext()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet1 */
        $resultSet1 = TestSchema::find()->sort(['title' => 1])->limit(2);

        $this->assertInstanceOf('\Reach\Mongo\ResultSet', $resultSet1);
        $this->assertEquals('Model\Mongo\TestSchema', $resultSet1->getObjectClassName());

        $resultSet2 = $resultSet1->append(new TestSchema(['title' => 'TitleAppend']));
        $this->assertEquals(3, $resultSet2->count());
    }

    public function testGetIds()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet */
        $resultSet = TestSchema::find()->sort(['title' => 1])->limit(3);
        $tmp = $resultSet->first();
        $array = $resultSet->getIds();
        $this->assertInternalType('array', $array);
        $this->assertEquals($this->_ids[2], $array[2]);
    }

    public function testGetMongoIds()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet */
        $resultSet = TestSchema::find()->limit(3);
        $tmp = $resultSet->first();
        $array = $resultSet->getMongoIds();
        $this->assertInternalType('array', $array);
        $this->assertInstanceOf('\MongoId', $array[2]);
    }

    public function testToArray()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet */
        $resultSet = TestSchema::find()->sort(['title' => 1])->limit(3);
        $array = $resultSet->toArray();
        $this->assertInternalType('array', $array);
        $this->assertInternalType('string', $array[0]['id']);
        $this->assertEquals('Title2', $array[2]['title']);
        $this->assertCount(3, $array);
    }

    public function testPluck()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet */
        $resultSet = TestSchema::find()->sort(['title' => 1])->limit(3);
        $array = $resultSet->pluck('_id');
        $this->assertInternalType('array', $array);
        $this->assertCount(3, $array);
        $this->assertInstanceOf('\MongoId', $array[0]);
        $this->assertEquals([$this->_ids[0], $this->_ids[1], $this->_ids[2]], $array);

        $array = $resultSet->pluck('object.id');
        $this->assertInternalType('array', $array);
        $this->assertEquals([0, 1, 2], $array);
    }

    public function testFind()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet */
        $resultSet = TestSchema::find()->sort(['title' => 1])->limit(3);
        $model = $resultSet->find(
            function ($model) {
                return $model->title == 'Title1';
            }
        );
        $this->assertInstanceOf('\Reach\Mongo\Document\Schema', $model);
        $this->assertEquals('Title1', $model->title);
        $this->assertNull(
            $resultSet->find(
                function () {
                    return false;
                }
            )
        );
    }

    public function testToJson()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet */
        $resultSet = TestSchema::find()->sort(['title' => 1])->limit(1);

        //$time = new \DateTime("@{$resultSet[0]->created->sec}", new \DateTimeZone('UTC'));
        //$date = $time->format(DATE_W3C);

        $json = $resultSet->toJson();
        $expected = '[{"id":"' . ((string)$this->_ids[0]) . '","title":"Title0","object":{"id":0,"name":"Obj0"},"init_test":true,"created":' . $resultSet->first()->created->sec . '}]';
        $this->assertEquals($expected, $json);
    }

    public function testCurrent()
    {
        $resultSetAsCursor = TestSchema::find(['title' => 'Title0'], ['title' => 1])->limit(1);
        $resultSetAsCursor->rewind();
        $model = $resultSetAsCursor->current();
        $this->assertInternalType('array', $model);
        $this->assertEquals($this->_ids[0], $model['_id']);
    }

    public function testAppendAndMergeWith()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet1 */
        $resultSet1 = TestSchema::find(['title' => 'attribute_not_exists']);
        $this->assertEquals(0, $resultSet1->count());

        // Countable
        $this->assertEquals(0, count($resultSet1));

        $resultSet1 = TestSchema::find()->sort(['title' => 1])->limit(2);

        // Append
        $resultSet2 = $resultSet1->append(new TestSchema(['title' => 'TitleAppend']));
        $this->assertEquals(3, count($resultSet2));
        $this->assertEquals('Title0', $resultSet2->first()->title);
        $this->assertEquals('Title1', $resultSet2[1]->title);
        $this->assertEquals('TitleAppend', $resultSet2[2]->title);

        // MergeWith
        $resultSet1 = TestSchema::find()->sort(['title' => 1])->limit(2)->skip(2);
        $resultSet2->mergeWith($resultSet1);
        $this->assertEquals(5, $resultSet2->count());

        // Iterator
        $counter = 0;
        foreach ($resultSet2 as $model) {
            $counter++;
            $this->assertInstanceOf('\Reach\Mongo\Document\Schema', $model);
        }
        $this->assertEquals(5, $counter);

        // ArrayAccess
        $this->assertEquals('Title2', $resultSet2[3]->title);
        $this->assertEquals('Title3', $resultSet2[4]->title);
    }

    public function testMap()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet */
        $resultSet = TestSchema::find();

        /** @var \Reach\ResultSet $resultSet */
        $resultSet2 = $resultSet->map(
            function ($model, $i) {
                $model->title = 'NewTitle' . $i;
                return $model;
            }
        );
        $this->assertInstanceOf('\Reach\ResultSet', $resultSet2);
        $this->assertEquals(10, count($resultSet2));
        $this->assertEquals('NewTitle9', $resultSet2[9]->title);

        $resultSet2 = $resultSet->map([$this, 'mapTestFn']);
        $this->assertInstanceOf('\Reach\ResultSet', $resultSet2);
        $this->assertEquals(10, count($resultSet2));
        $this->assertEquals('NewTitle9', $resultSet2[9]->title);
    }

    public function testFilter()
    {
        /** @var \Reach\Mongo\ResultSet $resultSet */
        $resultSet = TestSchema::find();
        $resultSet->next();
        $resultSet->next();
        $resultSet->next();
        $id = (string)$resultSet->current()->_id;

        /** @var \Reach\ResultSet $resultSet */
        $resultSet2 = $resultSet->filter(
            function ($model, $key) use($id) {
                return $model->title === 'Title2' && $key === $id;
            }
        );
        $this->assertInstanceOf('\Reach\ResultSet', $resultSet2);
        $this->assertEquals(1, count($resultSet2));
        $this->assertEquals('Title2', $resultSet2->first()->title);
    }

    public function testAsArray()
    {
        $resultSet = TestSchema::find();
        $array = $resultSet->asArray();
        $this->assertEquals(0, key($array));

        $resultSet = TestSchema::find()->sort(['title' => 1]);
        $array = $resultSet->export(true);
        $this->assertEquals((string)$this->_ids[0], key($array));
    }

    public function mapTestFn($model, $i)
    {
        $model->title = 'NewTitle' . $i;
        return $model;
    }
}
