<?php

namespace Model\Mongo;

use Reach\Mongo\Document\Schemaless;

class FreeDocument extends Schemaless
{

    public static function getCollectionName()
    {
        return 'free_model_mongo_document';
    }
}
