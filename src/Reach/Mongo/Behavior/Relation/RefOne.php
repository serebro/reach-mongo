<?php

namespace Reach\Mongo\Behavior\Relation;

use Reach\Mongo\Behavior\Relation;

class RefOne extends Relation
{

    public function make()
    {
        $model = $this->owner->{$this->_attribute};
        $collection = $this->owner->getCollection();
        return $this->_ref = $collection->createDBRef($model);
    }

    public function resolve()
    {
        $model_class = $this->ref;
        $document = $model_class::getDBRef($this->_ref);
        $model = $model_class::instantiate($document);
        $this->_is_resolved = true;
        return $model;
    }
}