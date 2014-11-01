<?php

namespace Reach\Mongo;

/**
 * Class Buffer
 * @static
 * @package Reach\Mongo
 */
class Buffer
{

    /** @var Buffer\Adapter */
    private static $_adapter = null;

    /**
     * @param Buffer\Adapter $adapter Default: Buffer\Adapter\Memory
     * @return Buffer\Adapter
     */
    public static function open(Buffer\Adapter $adapter = null)
    {
        if ($adapter !== null && !$adapter instanceof Buffer\Adapter) {
            return false;
        }

        if (self::$_adapter && !self::$_adapter->isEmpty()) {
            return false;
        }

        return self::$_adapter = $adapter ? $adapter : new Buffer\Adapter\Memory();
    }

    /**
     * @param int      $operation
     * @param Schema $model
     * @param array    $options
     * @return bool
     */
    public static function add($operation, $model, array $options = [])
    {
        if (!$adapter = self::get()) {
            return false;
        }

        return $adapter->add($operation, $model, $options);
    }

    /**
     * @return Buffer\Adapter
     */
    public static function get()
    {
        return self::$_adapter;
    }

    /**
     * @return bool
     */
    public static function isEmpty()
    {
        if (!$adapter = self::get()) {
            return true;
        }

        return $adapter->isEmpty();
    }

    /**
     * return void
     */
    public static function clear()
    {
        if ($adapter = self::get()) {
            $adapter->clear();
        }
    }

    /**
     * @return void
     */
    public static function flush()
    {
        /** @var Buffer\Adapter $adapter */
        if ($adapter = self::get()) {
            $adapter->flush();
        }
    }

    /**
     * Called from Buffer\Adapter::close
     * return bool If "False" then need clear buffer
     */
    public static function close()
    {
        if (!$adapter = self::get()) {
            return false;
        }

        if (!$adapter->isEmpty()) {
            return false;
        }

        self::$_adapter = null;
        return true;
    }
}
