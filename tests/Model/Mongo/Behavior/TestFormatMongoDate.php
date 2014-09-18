<?php

namespace Model\Mongo\Behavior;

use MongoDate;
use Reach\Mongo\Document;

/**
 * Class TestFormatMongoDate
 * @package Model\Mongo\Behavior
 * @property \DateTime updatedAt
 */
class TestFormatMongoDate extends Document
{

    public $updated;

    public function behaviors()
    {
        return [
            'updatedAt' => [
                'class'           => '\Reach\Mongo\Behavior\FormatMongoDate',
                'sourceAttribute' => 'updated',
                'attribute'       => 'updatedAt'
            ]
        ];
    }

    public function beforeSave()
    {
        $this->updated = new MongoDate();
        parent::beforeUpdate();
    }
}