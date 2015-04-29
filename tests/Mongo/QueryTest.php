<?php

namespace Mongo;

use Model\Mongo\TestSchema;

/**
 * Class QueryTest
 * @package Mongo
 */
class QueryTest extends \PhactoryTestCase
{

    public function testTest()
    {
        $criteria1 = new \Reach\Mongo\Criteria();
        $criteria1->addOr(['title' => 'Title1']);

        $expected = ['$query' => ['$or' => [['title' => 'Title1']]]];
        $this->assertEquals($expected, $criteria1->asArray());

        $criteria1->addOr(['title' => 'Title2']);
        $criteria1->addOr(['title' => 'Title3']);

        $criteria2 = new \Reach\Mongo\Criteria();
        $criteria2->add('created', '<', new \MongoDate());
        $criteria2->add(['created' => ['<' => new \MongoDate()]]);
        $criteria2->add($criteria1);
        $criteria2->where('this.created < (new Date())');
        $criteria2->orderBy(['title' => -1]);

        $query = TestSchema::query($criteria2);
        $this->assertInstanceOf('\Model\Mongo\TestSchemaQuery', $query);

        $resultSet = $query->all();
        $this->assertInstanceOf('\Reach\Mongo\ResultSet', $resultSet);

        $result = $resultSet->limit(2)->asArray();
        $this->assertEquals(2, count($result));
        $this->assertEquals('Title3', $result[0]->title);
        $this->assertEquals('Title2', $result[1]->title);

        $doc = $query->title1()->one();
        $this->assertInstanceOf('\Model\Mongo\TestSchema', $doc);
        $this->assertEquals('Title1', $doc->title);
    }
}
