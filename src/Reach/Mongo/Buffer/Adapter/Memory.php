<?php

namespace Reach\Mongo\Buffer\Adapter;

use MongoId;
use Reach\Mongo\Buffer as Buffer;
use Reach\Mongo\Buffer\Adapter;
use Reach\Mongo\Document\Schema;

class Memory extends Adapter
{

    private $_storage = [];


    /**
     * @param int    $operation
     * @param Schema $model
     * @param array  $options
     * @return bool
     */
    public function add($operation, $model, array $options = [])
    {
        $class_name = get_class($model);
        $this->_storage["$model->_id.$class_name"][$operation] = [
            $model->_id,
            $model,
            $options,
        ];

        return true;
    }

    /**
     * @return bool
     */
    public function flush()
    {
        if ($this->isEmpty()) {
            return false;
        }

        foreach ($this->_storage as $item) {
            // Delete
            if (isset($item[Adapter::DELETE])) {
                if (!isset($item[Adapter::INSERT])) {
                    list($model_id, $model, $options) = $item[Adapter::DELETE];
                    $this->_flushDelete($model, $options, $model_id);
                    continue;
                }
                continue;
            }

            // Insert
            if (isset($item[Adapter::INSERT])) {
                list($model_id, $model, $options) = $item[Adapter::INSERT];
                $this->_flushInsert($model, $options, $model_id);
                continue;
            }

            // Update
            if (isset($item[Adapter::UPDATE])) {
                list($model_id, $model, $options) = $item[Adapter::UPDATE];
                $this->_flushUpdate($model, $options, $model_id);
                continue;
            }
        }

        $this->clear();
        return true;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->_storage);
    }

    /**
     * @param Schema   $model
     * @param array    $options
     * @param \MongoId $model_id
     * @return bool
     */
    private function _flushDelete(Schema $model, array $options = [], MongoId $model_id)
    {
        $class_name = get_class($model);
        return (bool)$class_name::deleteAll(['_id' => $model_id], $options);
    }

    /**
     * @param Schema $model
     * @param array  $options
     * @return bool
     */
    private function _flushInsert(Schema $model, array $options = [])
    {
        if ($model->forceInsert($options) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param Schema $model
     * @param array  $options
     * @return bool
     */
    private function _flushUpdate(Schema $model, array $options = [])
    {
        if ($model->forceUpdate($options) === false) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->_storage = [];
    }
}
