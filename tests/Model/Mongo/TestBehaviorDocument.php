<?php

namespace Model\Mongo;

use Reach\Mongo\Document\Schema;

class TestBehaviorDocument extends Schema
{

    public $title;

    public $created;


    public function behaviors()
    {
        return [
            'BehaviorLang' => [
                'class' => '\Model\Mongo\BehaviorLang',
                'attr'  => 'title'
            ],
        ];
    }

    public static function getCollectionName()
    {
        return 'test_model_mongo_behavior_document';
    }

    public function beforeSave()
    {
        if ($this->getIsNew()) {
            $this->created = new \MongoDate();
        }

        return true;
    }
}
