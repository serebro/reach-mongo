<?php

namespace Reach\Mongo;

use Reach\Mongo\Behavior\SerializableInterface;

interface RelatableInterface extends SerializableInterface {

    /**
     * @return mixed
     */
    public function make();

    /**
     * @return mixed
     */
    public function resolve();

    /**
     * @param string $default
     * @return string
     */
    public function getKey($default);
}