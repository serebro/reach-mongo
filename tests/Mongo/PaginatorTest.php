<?php

namespace Mongo;

use Model\Mongo\TestSchema;
use Reach\Mongo\Paginator;

class PaginatorTest extends \PHPUnit_Framework_TestCase
{

    private $_ids = [];

    public function setUp()
    {
        TestSchema::deleteAll([]);

        for ($i = 0; $i < 100; $i++) {
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

    public function testPaginator()
    {
        $resultSet = TestSchema::find();
        $paginator = new Paginator($resultSet, 10, 1);

        $this->assertInstanceOf('\Reach\Mongo\Paginator', $paginator);
        $this->assertEquals(10, $paginator->getLimit());
        $this->assertEquals(1, $paginator->getCurrentPage());
        $this->assertInstanceOf('\Reach\Mongo\ResultSet', $paginator->getResultSet());

        $paginator->setCurrentPage(2);
        $this->assertEquals(2, $paginator->getCurrentPage());
        $page = $paginator->getPaginate();
        $this->assertEquals(1, $page->first);
        $this->assertEquals(2, $page->current);
        $this->assertEquals(3, $page->next);
        $this->assertEquals(1, $page->prev);
        $this->assertEquals(10, $page->last);
        $this->assertEquals(10, $page->pages);
        $this->assertEquals(100, $page->total_items);
        $this->assertInstanceOf('\Reach\Mongo\ResultSet', $page->items);
        $this->assertEquals('Title10', $page->items->first()->title);
    }
}
 