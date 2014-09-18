<?php

namespace Model\Mongo;

use Reach\Mongo\Document\Schema;

class DocumentSequenceId extends Schema
{
    public $_id;
    public $title;

    public function behaviors()
    {
        return [
            '_id' => ['class' => '\Reach\Mongo\Behavior\Generator\SequenceId'],
        ];
    }
}