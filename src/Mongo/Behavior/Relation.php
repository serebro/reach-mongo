<?php

namespace Reach\Mongo\Behavior;

use Reach\Behavior;
use Reach\Event;
use Reach\Mongo\RelatableInterface;

abstract class Relation extends Behavior implements RelatableInterface
{

    use FieldTrait;

    const EMBED_ONE = '\Reach\Mongo\Behavior\Relation\EmbedOne';
    const EMBED_MANY = '\Reach\Mongo\Behavior\Relation\EmbedMany';
    const REF_PK = '\Reach\Mongo\Behavior\Relation\RefPk';
    const REF_ONE = '\Reach\Mongo\Behavior\Relation\RefOne';
    const REF_MANY = '\Reach\Mongo\Behavior\Relation\RefMany';

    /** @var string */
    public $attr;

    /** @var string */
    public $ref;

    /** @var string */
    protected $_class;

    /** @var  string */
    protected $_attribute;

    /** @var bool */
    protected $_is_resolved = false;

    protected $_ref;


    public function events()
    {
        return [
            'beforeConstruct' => [$this, 'beforeConstruct'],
            'afterFind' => [$this, 'afterFind'],
        ];
    }

    public function isResolved()
    {
        return $this->_is_resolved;
    }

    public function setRef($ref)
    {
        $this->_ref = $ref;
        return $this;
    }

    public function getRef()
    {
        return $this->_ref;
    }

    public function beforeConstruct(Event $event)
    {
        $this->_class = get_class($this->owner);
        $this->_attribute = $this->attr ?: $this->behavior_name;
    }

    public function afterFind(Event $event)
    {
        // map attribute
        $key = $this->getKey($this->_attribute);
        $this->_ref = $event->document[$key];
        $is_force = array_key_exists($this->_attribute, $this->owner->attributes());
        if ($is_force) {
            $this->owner->{$this->_attribute} = $this->resolve();
        }
    }

    public function serialize()
    {
        return $this->make();
    }

    public function unserialize($serialized)
    {
    }

    /**
     * @return mixed
     */
    abstract public function make();

    /**
     * @return mixed
     */
    abstract public function resolve();
}
