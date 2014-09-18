<?php

namespace Reach\Mongo;

use DateTime;
use DateTimeZone;
use MongoCursor;
use MongoDate;
use MongoId;
use stdClass;
use Traversable;

/**
 * Class DocumentTrait
 * @package Reach\Mongo
 * @property MongoId  _id
 * @property string   stringId
 * @property bool     isNew
 */
trait DocumentTrait
{

    public static $to_array_id_attribute = 'id';

    /** @var  MongoId */
    public $_id;

    /** @var bool */
    private $_is_new = true;

    /** @var string */
    private $_connection_name;


    public static function resultSet(MongoCursor $cursor)
    {
        return new ResultSet($cursor, get_called_class());
    }

    public static function query(CriteriaInterface $criteria = null)
    {
        return new Query($criteria, get_called_class());
    }

    public static function date($time = 'now')
    {
        if (is_int($time)) {
            $time = "@$time";
        }
        $time = new DateTime($time, new DateTimeZone('UTC'));
        return $time->format(DATE_ISO8601);
    }

    public function __call($name, $arguments)
    {
        // behaviors
        if (!empty($this->_behaviors)) {
            foreach ($this->_behaviors as $behavior) {
                if (!method_exists($behavior, $name)) {
                    continue;
                }

                return call_user_func_array([$behavior, $name], $arguments);
            }
        }

        // getter
        if ('get' === strtolower(substr($name, 0, 3))) {
            return $this->__get(lcfirst(substr($name, 3)));
        }

        // setter
        if ('set' === strtolower(substr($name, 0, 3)) && isset($arguments[0])) {
            return $this->__set(lcfirst(substr($name, 3)), $arguments[0]);
        }

        throw new \Exception('Document has no method "' . $name . '"');
    }

    public function init()
    {
        parent::init();
    }

    /**
     * @return bool
     */
    public function getIsNew()
    {
        return $this->_is_new;
    }

    /**
     * @param bool $value
     */
    public function setIsNew($value)
    {
        $this->_is_new = (bool)$value;
    }

    /**
     * @return MongoId
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $field
     * @return string
     */
    public function getStringId($field = '_id')
    {
        if (!property_exists(get_class($this), $field)) {
            //error_log(__METHOD__ . " MongoId field '$field' is not defined in class '" . get_called_class() . "'");
            return '';
        }

        return (string)$this->{$field};
    }

    /**
     * @return string
     */
    public function getConnectionName()
    {
        return $this->_connection_name ? $this->_connection_name : ConnectionManager::$default_connection_name;
    }

    /**
     * @param $connection_name
     * @return \Reach\Mongo\Document\Schema
     */
    public function setConnectionName($connection_name)
    {
        $this->_connection_name = $connection_name;
        return $this;
    }

    /**
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        return $this->getIsNew() ? $this->insert($options) : $this->update($options);
    }

    /**
     * @param string $attr
     * @param array  $params
     * @return bool
     */
    public function isIncludeAttr($attr, array $params)
    {
        if (isset($params['exclude']) && in_array($attr, $params['exclude'])) {
            return false;
        }

        if (isset($params['include'])) {
            return in_array($attr, $params['include']);
        }

        return true;
    }

    public function toJson(array $params = [])
    {
        return json_encode($this->toArray($params));
    }

    public function toArray(array $params = [])
    {
        $attributes = array_keys($this->attributes());
        if (!empty($params['include'])) {
            $attributes = array_intersect($attributes, $params['include']);
        }
        if (!empty($params['exclude'])) {
            $attributes = array_diff($attributes, $params['exclude']);
        }

        $attributes[] = '_id';

        return self::deepToArray($this->getAttributes($attributes));
    }

    /**
     *  To String
     *  If this object is treated as a string,
     *  it would return its ID.
     * @return string
     */
    public function __toString()
    {
        return $this->getStringId();
    }

    protected static function deepToArray($data)
    {
        if (!is_array($data) && !$data instanceof Traversable) {
            return null;
        }

        $result = [];
        foreach ($data as $key => $val) {
            if ($val instanceof MongoId) {
                $val = (string)$val;
            } else if ($val instanceof MongoDate) {
                $val = $val->sec;
            } else if ($val instanceof \Reach\Mongo\Document\Schema) {
                $val = $val->toArray();
            } else if (is_array($val) || $val instanceof stdClass) {
                $val = self::deepToArray($val);
            }

            $result[$key === '_id' ? static::$to_array_id_attribute : $key] = $val;
        }

        return $result;
    }

    public static function hexToBase64($hex)
    {
        return rtrim(strtr(base64_encode(pack('H*', $hex)), '+/', '-_'), '=');
    }

    public static function hexToBase10($hex)
    {
        if (strlen($hex) == 1) {
            return hexdec($hex);
        } else {
            $remain = substr($hex, 0, -1);
            $last = substr($hex, -1);
            return bcadd(bcmul(16, self::hexToBase10($remain)), hexdec($last));
        }
    }
}
