<?php

namespace Model\Mongo\Behavior;

use Reach\Mongo\Document;

/**
 * Class TestFormatMongoDate
 * @package Model\Mongo\Behavior
 * @property \DateTime updatedAt
 */
class TestLangText extends Document
{

    public $lang_text;


    public function behaviors()
    {
        return [
            'lang_text' => [
                'class' => '\Reach\Mongo\Behavior\LangText',
            ],
        ];
    }

}
