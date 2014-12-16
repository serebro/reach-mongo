<?php

namespace Reach\Mongo\Behavior;

trait FieldTrait
{

    /** @var string */
    public $key;

    public function getKey($default)
    {
        return empty($this->key) ? $default : $this->key;
    }
}
