<?php

namespace Mongo;

use Model\Mongo\FreeDocument;

class SchemalessDocumentTest extends \PhactoryTestCase
{

    public function testInitializeSettersGetters()
    {
        $model = new FreeDocument(
            [
                'int'   => 123,
                'obj'   => []
            ]
        );
        $model->setTitle('Title')->setDescription('Description');
        $model->value = 'value1';

        $this->assertInstanceOf('\Reach\Mongo\Document\Schemaless', $model);
        $this->assertEquals('Title', $model->title);
        $this->assertEquals('Title', $model->getTitle());
        $this->assertEquals('Description', $model->description);
        $this->assertEquals('value1', $model->value);
        $this->assertEquals(123, $model->int);
        $this->assertEquals([], $model->obj);
    }

    public function testInsertUpdate()
    {
        $model = new FreeDocument(['title' => 'Title', 'int' => 0]);
        $this->assertTrue($model->save());
        $id = $model->_id;
        $model->description = 'Description';
        $this->assertTrue($model->save());
        $this->assertEquals($id, $model->_id);
        $this->assertEquals('Description', $model->description);
    }

    public function testFind()
    {
        $model = new FreeDocument(['title' => 'Title', 'int' => 0]);
        $model->save();
        $id = $model->_id;
        $models = FreeDocument::find();
        $model = $models->first();
    }

}
