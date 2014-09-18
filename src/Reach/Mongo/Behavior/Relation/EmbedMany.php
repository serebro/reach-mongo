<?php

namespace Reach\Mongo\Behavior\Relation;

use Reach\Mongo\Behavior\Relation;
use Reach\Mongo\DocumentInterface;

class EmbedMany extends Relation
{

    public function make()
    {
        $this->_ref = [];
        $models = $this->owner->{$this->_attribute};
        foreach ($models as $model) {
            if ($model instanceof DocumentInterface) {
                $this->_ref[] = $model->getRawDocument();
            } else {
                $this->_ref[] = $model;
            }
        }

        return $this->_ref;
    }

    public function resolve()
    {
        $models = [];
        $model_class = $this->ref;
        foreach ($this->_ref as $document) {
            $model = new $model_class();
            if ($model instanceof DocumentInterface) {
                $models[] = $model_class::instantiate($document);
            } else {
                foreach ($document as $attribute => $value) {
                    $model->$attribute = $value;
                }
                $models[] = $model;
            }
        }

        $this->_is_resolved = true;

        return $models;
    }
}