<?php

namespace Reach\Mongo\Buffer;

use Reach\Mongo\Buffer;

abstract class Adapter
{

    const INSERT = 1;
    const UPDATE = 2;
    const DELETE = 3;


    /**
     * @abstract
     * @param int   $operation self::INSERT, self::UPDATE, self::DELETE
     * @param       $model
     * @param array $options
     * @return bool
     */
    abstract public function add($operation, $model, array $options = []);

    /**
     * @return void
     */
    abstract public function flush();

    /**
     * @return bool
     */
    final public function close()
    {
        if (!$this->isEmpty()) {
            return false;
        }

        $this->clear();
        return Buffer::close();
    }

    /**
     * @return bool
     */
    abstract public function isEmpty();

    /**
     * @return mixed
     */
    abstract public function clear();
}
