<?php

namespace Reach\Mongo\Behavior\Relation;

use Reach\Mongo\Behavior\Relation;
use Reach\Mongo\DocumentInterface;

class EmbedOne extends Relation
{

    public function make()
    {
        $model = $this->owner->{$this->_attribute};
        if ($model instanceof DocumentInterface) {
            $this->_ref = $model->getRawDocument();
        } else {
            $this->_ref = $model;
        }

        return $this->_ref;
    }

    public function resolve()
    {
        $model_class = $this->ref;
        $model = new $model_class();
        if ($model instanceof DocumentInterface) {
            $model = $model_class::instantiate($this->_ref);
        } else {
            foreach ($this->_ref as $attribute => $value) {
                $model->$attribute = $value;
            }
        }

        $this->_is_resolved = true;
        return $model;
    }
}