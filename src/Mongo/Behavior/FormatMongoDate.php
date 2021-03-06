<?php

namespace Reach\Mongo\Behavior;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use MongoDate;
use Reach\Behavior;
use Reach\Event;

/**
 * Class FormatMongoDate
 *
 * <code>
 *    public function behaviors()
 *    {
 *        return [
 *            'updated' => ['class' => '\Reach\Mongo\Behavior\FormatMongoDate'],
 *        ];
 *    }
 * </code>
 *
 * Usage:
 * <code>
 *      echo $model->updated->format(DATE_ISO8601);
 *      $model->updated = new \MongoDate();
 *      $model->save();
 * </code>

 * @package Reach\Mongo\Behavior
 */
class FormatMongoDate extends Behavior
{

    /** @var string */
    public $sourceAttribute;

    /** @var string */
    public $attribute;

    /** @var MongoDate */
    private $_original_value;


    public function events()
    {
        return [
            'afterFind'  => [$this, 'afterFind'],
            'afterSave'  => [$this, 'afterFind'],
            'beforeSave' => [$this, 'beforeSave']
        ];
    }

    /**
     * @param Event $event
     * @throws \Exception
     */
    public function afterFind(Event $event)
    {
        if ($this->attribute === null) {
            $this->attribute = $this->behavior_name;
        }

        if (!property_exists(get_class($this->owner), $this->sourceAttribute)) {
            throw new InvalidArgumentException(
                'This property "' . $this->sourceAttribute . '" does not exist in this model "' . get_class(
                    $this->owner
                ) . '"'
            );
        }

        $this->_original_value = $this->owner->{$this->sourceAttribute};
        if ($this->_original_value instanceof MongoDate) {
            $timestamp = $this->_original_value->sec;
        } else if (is_numeric($this->_original_value)) {
            $timestamp = $this->_original_value;
        } else if (is_string($this->_original_value)) {
            $timestamp = strtotime($this->_original_value);
        } else {
            $timestamp = time();
        }

        $this->owner->{$this->attribute} = new DateTime('@' . $timestamp, new DateTimeZone('UTC'));
    }

    public function beforeSave(Event $event)
    {
        if (!property_exists(get_class($this->owner), $this->sourceAttribute)) {
            return;
        }

        if ($this->attribute === null) {
            return;
        }

        if ($this->owner->{$this->attribute} instanceof MongoDate) {
            // for MongoDate
            $this->owner->{$this->sourceAttribute} = $this->owner->{$this->attribute};
        } else if ($this->owner->{$this->attribute} instanceof DateTime) {
            // for DateTime
            $timestamp = $this->owner->{$this->attribute}->getTimestamp();
            if ($timestamp !== $this->_original_value->sec) {
                $this->owner->{$this->sourceAttribute} = new MongoDate($timestamp);
            }
        } else if (is_int($this->owner->{$this->attribute})) {
            // for integer
            $this->owner->{$this->sourceAttribute} = new MongoDate($this->owner->{$this->attribute});
        }

        unset($this->owner->{$this->attribute});
    }
}
