<?php

namespace Reach\Mongo;

interface RelatableInterface {

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