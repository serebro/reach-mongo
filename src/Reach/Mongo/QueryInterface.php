<?php

namespace Reach\Mongo;

use Reach\ResultSetInterface;

interface QueryInterface extends CriteriaInterface
{

    /**
     * @param mixed  $query
     * @param string $hydrate_class
     */
    public function __construct($query = null, $hydrate_class);

    /**
     * @param array $fields
     * @param bool  $as_array
     * @return null|DocumentInterface
     */
    public function findOne(array $fields = [], $as_array = false);

    /**
     * @param array $fields
     * @return ResultSetInterface
     */
    public function find(array $fields = []);
}