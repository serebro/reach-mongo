<?php

namespace Reach\Mongo\Behavior\Relation;

use Reach\Mongo\Behavior\Relation;

class RefPk extends Relation
{

    public function make()
    {
        $model = $this->owner->{$this->_attribute};
        $primaryKey = $model->getPrimaryKey();
        $this->_ref = $model->$primaryKey;
        return $this->_ref;
    }

    public function resolve()
    {
        $model_class = $this->ref;
        $this->_is_resolved = true;
        $primaryKey = $model_class::getPrimaryKey();
        return $model_class::findOne([$primaryKey => $this->_ref]);
    }
}
