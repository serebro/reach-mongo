<?php

namespace Reach\Mongo;

use Exception;

/**
 * Class ConnectionManager
 * @package Reach\Mongo
 */
class ConnectionManager
{

    public static $default_connection_name = 'mongo';

    private static $_connections_config = [];

    private static $_connections = [];


    /**
     * @param string $connection_name
     * @return Connection
     */
    public static function getConnection($connection_name = null)
    {
        $connection_name = empty($connection_name) ? self::$default_connection_name : $connection_name;
        if (!array_key_exists($connection_name, self::$_connections)) {
            self::$_connections[$connection_name] = new Connection(self::$_connections_config[$connection_name]);
        }
        return self::$_connections[$connection_name];
    }

    /**
     * @param array|string $connection_name
     * @return bool
     */
    public static function closeConnection($connection_name = null)
    {
        if (null === $connection_name) {
            $names = [self::$default_connection_name];
        } else if ('_all_' === $connection_name) {
            $names = array_keys(self::$_connections_config);
        } else if (is_string($connection_name)) {
            $names = [$connection_name];
        } else if (is_array($connection_name)) {
            $names = $connection_name;
        } else {
            return false;
        }

        foreach ($names as $connection_name) {
            if (!empty(self::$_connections[$connection_name])) {
                self::$_connections[$connection_name]->close();
                unset(self::$_connections[$connection_name]);
            }
        }

        return true;
    }

    public function __call($name, $arguments)
    {
        if (empty($arguments) || empty($arguments[0])) {
            return;
        }

        if ($name == 'registerConnection') {
            $config = $arguments[0];
            $connection_name = isset($arguments[1]) ? $arguments[1] : null;
            self::registerConnection($config, $connection_name);
        }
    }

    /**
     * @param array  $config
     * @param string $connection_name
     * @throws Exception
     */
    public static function registerConnection(array $config, $connection_name = null)
    {
        if (!extension_loaded('mongo')) {
            throw new Exception('The mongo extension is required');
        }

        $connection_name = empty($connection_name) ? self::$default_connection_name : $connection_name;
        if (!isset($config['option'])) {
            $config['option'] = [];
        }
        self::$_connections_config[$connection_name] = $config;
        if (array_key_exists($connection_name, self::$_connections)) {
            unset(self::$_connections[$connection_name]);
        }
    }

    /**
     * @param string $connection_name
     * @return array
     */
    public static function getConfig($connection_name = null) {
        if (array_key_exists($connection_name, self::$_connections)) {
            return self::$_connections[$connection_name];
        }

        return [];
    }

    public static function setConnection(Connection $connection, $connection_name)
    {
        $connection_name = empty($connection_name) ? self::$default_connection_name : $connection_name;
        self::$_connections[$connection_name] = $connection;
    }
}