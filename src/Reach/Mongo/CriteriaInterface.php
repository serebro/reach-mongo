<?php

namespace Reach\Mongo;

interface CriteriaInterface
{

    public function add($criteria, $selector = null, $value = null);

    public function addOr($criteria, $selector = null, $value = null);

    public function addNor($criteria, $selector = null, $value = null);

    /**
     * @return array
     */
    public function asArray();
}
