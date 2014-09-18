<?php

namespace Model\Mongo\Behavior;

use MongoDate;
use Reach\Mongo\Document;

/**
 * Class TestSoftDelete
 * @package Model\Mongo\Behavior
 * @property \DateTime updatedAt
 */
class TestSoftDelete extends Document
{

    public $title = '';

    public $updated;

    public $deleted;


    public function behaviors()
    {
        return [
            [
                'class' => '\Reach\Mongo\Behavior\SoftDelete',
                'attribute' => 'deleted',
            ]
        ];
    }

    public function beforeSave()
    {
        $this->updated = new MongoDate();
        parent::beforeUpdate();
    }
}