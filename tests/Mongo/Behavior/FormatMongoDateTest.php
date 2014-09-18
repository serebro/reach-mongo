<?php

namespace Mongo\Behavior;

use Model\Mongo\Behavior\TestFormatMongoDate;
use MongoDate;
use MongoId;

class FormatMongoDateTest extends \PhactoryTestCase
{

    public function testGetting()
    {
        $expected = new MongoDate();
        $model = new TestFormatMongoDate();
        $model->_id = new MongoId();
        $model->updated = $expected;
        TestFormatMongoDate::getMongoCollection()->save($model->getRawDocument());

        /** @var TestFormatMongoDate $model */
        $model = TestFormatMongoDate::findOne();
        $this->assertObjectHasAttribute('updatedAt', $model);
        $this->assertInstanceOf('\DateTime', $model->updatedAt);
        $this->assertEquals($expected->sec, $model->updatedAt->getTimestamp());

        $model->delete();
    }

    public function testSaving()
    {
        $model = new TestFormatMongoDate();
        $model->save();

        $this->assertObjectHasAttribute('updatedAt', $model);
        $this->assertInstanceOf('\DateTime', $model->updatedAt);

        $document = TestFormatMongoDate::getMongoCollection()->findOne(['_id' => $model->_id]);
        $this->assertEquals($model->updated, $document['updated']);
    }
}