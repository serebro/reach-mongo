<?php

namespace Model\Mongo;

use Phalcon\Mvc\Collection;

class TestDocumentPhalcon extends Collection
{

    /** @var string */
    public $title = '';

    /** @var null */
    public $object = null;

    /** @var \MongoDate */
    public $created;

    /** @var bool */
    public $init_test = false;

    public $not_stored = true;


    public function getSource()
    {
        return 'test_model_mongo_document';
    }

    public function beforeCreate()
    {
        $this->created = new \MongoDate();
    }

}
