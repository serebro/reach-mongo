<?php

use Model\Mongo\TestRelDocument;
use Model\Mongo\TestSchema;

class BehaviorTest extends PhactoryTestCase
{

    public function testRelationDocumentSaveAndFetch()
    {
        $document = new TestSchema();
        $document->title = 'TestSchemaTitle';
        $document->object = new \stdClass();
        $document->save();

        $stdClassEmbedOne = new \stdClass();
        $stdClassEmbedOne->user_id = new \MongoId();
        $stdClassEmbedOne->title = 'stdClassTitle';

        $testSchema = TestSchema::findOne();
        $model = new TestRelDocument();
        $model->title = 'TestRelDocumentTitle';
        $model->testSchemaRefPk = $testSchema;
        $model->testSchemaRefOne = $testSchema;
        $model->stdClassEmbedOne = $stdClassEmbedOne;
        $model->testSchemaEmbedOne = $testSchema;
        $model->testSchemaRefMany = [$testSchema, $testSchema];
        $model->testSchemaEmbedMany = [$testSchema, $stdClassEmbedOne];
        $model->save();

        // Save
        $document = TestRelDocument::getMongoCollection()->findOne();
        $this->assertEquals('TestRelDocumentTitle', $document['title']);
        $this->assertEquals($testSchema->_id, $document['test_schema_id']);
        $this->assertEquals(
            ['$ref' => 'test_model_mongo_document', '$id' => $testSchema->_id],
            $document['ref_one']
        );
        $this->assertTrue(is_array($document['ref_many']));
        $this->assertEquals(
            [
                ['$ref' => 'test_model_mongo_document', '$id' => $testSchema->_id],
                ['$ref' => 'test_model_mongo_document', '$id' => $testSchema->_id],
            ],
            $document['ref_many']
        );
        $this->assertEquals(
            [
                'user_id' => $stdClassEmbedOne->user_id,
                'title'   => 'stdClassTitle',
            ],
            $document['std_embed_one']
        );
        $this->assertEquals($testSchema->title, $document['embed_one']['title']);
        $this->assertEquals($testSchema->_id, $document['embed_one']['_id']);
        $this->assertTrue(is_array($document['embed_many']));
        $this->assertEquals(
            [
                $testSchema->title,
                $stdClassEmbedOne->title,
            ],
            [
                $document['embed_many'][0]['title'],
                $document['embed_many'][1]['title']
            ]
        );

        // Fetch All
        //$testRelDocument = TestRelDocument::find();

        // Fetch One
        $testRelDocument = TestRelDocument::findOne();
        $model = $testRelDocument->testSchemaRefPk;
        $this->assertInstanceOf('\Model\Mongo\TestSchema', $model);
        $this->assertEquals('TestSchemaTitle', $model->title);

        $testRelDocument->testSchemaRefOne;
        $model = $testRelDocument->testSchemaRefOne;
        $this->assertInstanceOf('\Model\Mongo\TestSchema', $model);
        $this->assertEquals('TestSchemaTitle', $model->title);

        $models = $testRelDocument->testSchemaRefMany;
        $this->assertTrue(is_array($models));
        $this->assertInstanceOf('\Model\Mongo\TestSchema', $models[0]);
        $this->assertEquals('TestSchemaTitle', $models[0]->title);
        $this->assertCount(2, $models);

        $model = $testRelDocument->stdClassEmbedOne;
        $this->assertInstanceOf('\stdClass', $model);
        $this->assertEquals('stdClassTitle', $model->title);
        $this->assertEquals($stdClassEmbedOne->user_id, $model->user_id);

        $model = $testRelDocument->testSchemaEmbedOne;
        $this->assertInstanceOf('\Model\Mongo\TestSchema', $model);
        $this->assertEquals('TestSchemaTitle', $model->title);

        $models = $testRelDocument->testSchemaEmbedMany;
        $this->assertTrue(is_array($models));
        $this->assertInstanceOf('\Model\Mongo\TestSchema', $models[0]);
        $this->assertEquals('TestSchemaTitle', $models[0]->title);
        $this->assertCount(2, $models);

        // Delete cascade
        //$testRelDocument->delete();
    }

    public function testSequenceIdGenerator()
    {
        $model = new \Model\Mongo\DocumentSequenceId();
        $model->save();
        $this->assertEquals(1, $model->_id);

        $model = new \Model\Mongo\DocumentSequenceId();
        $model->save();
        $this->assertEquals(2, $model->_id);

        $this->assertEquals(3, $model->getNewId());
        $this->assertEquals(4, $model->getNewId());

        $model = new \Model\Mongo\DocumentSequenceId();
        $model->_id = $model->getNewId();
        $this->assertEquals(5, $model->_id);
        $model->save();
        $this->assertEquals(5, $model->_id);
    }
}
