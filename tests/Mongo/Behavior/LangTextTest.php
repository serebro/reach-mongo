<?php

namespace Mongo\Behavior;

use Model\Mongo\Behavior\TestLangText;
use MongoId;

class LangTextTest extends \PhactoryTestCase
{

    public function testLangText()
    {
        $model = new TestLangText();
        $model->_id = new MongoId();

        $model->lang_text->en = 'textEN';
        $actual = $model->lang_text->get('en');
        $this->assertEquals('textEN', $actual);
        $model->lang_text->set('text2EN', 'en');
        $this->assertEquals('text2EN', $model->lang_text->en);

        $actual = $model->getRawDocument();
        $expected = [
            'lang_text' => ['en' => 'text2EN'],
            '_id' => $model->_id,
        ];
        $this->assertEquals($expected, $actual);

        TestLangText::getMongoCollection()->save($actual);

        /** @var TestLangText $model */
        $model = TestLangText::findOne();
        $this->assertObjectHasAttribute('lang_text', $model);
        $this->assertInstanceOf('\Reach\Mongo\Behavior\LangText', $model->lang_text);
        $this->assertEquals('text2EN', $model->lang_text->en);

        $model->delete();
    }
}