<?php

namespace Reach\Mongo\Behavior\Relation;

use Reach\Mongo\Behavior\Relation;

class RefMany extends Relation
{

    public function make()
    {
        $refs = [];
        $models = $this->owner->{$this->_attribute};
        foreach ($models as $model) {
            $refs[] = $model->getCollection()->createDBRef($model);
        }

        return $this->_ref = $refs;
    }

    public function resolve()
    {
        $models = [];
        $model_class = $this->ref;
        foreach ($this->_ref as $ref) {
            $document = $model_class::getDBRef($ref);
            $models[] = $model_class::instantiate($document);
        }
        $this->_is_resolved = true;

        return $models;
    }
}