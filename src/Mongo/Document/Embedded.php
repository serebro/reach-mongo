<?php

namespace Reach\Mongo\Document;

use Reach\Behavior;
use Reach\Model;
use Reach\Mongo\Behavior\SerializableInterface;
use Reach\Mongo\DocumentInterface;
use Reach\Mongo\EmbeddedDocumentInterface;
use Reach\Mongo\RelatableInterface;

/**
 * Class Embedded
 * @property DocumentInterface $parent
 * @package Reach\Mongo\Document
 */
abstract class Embedded extends Model implements EmbeddedDocumentInterface, DocumentInterface
{

    /** @var Schema */
    protected $_parent;


    public function __construct($attributes = null)
    {
        parent::__construct($attributes);
        $this->setIsNew(true);
    }

    public static function getPrimaryKey()
    {
        return null;
    }

    /**
     * @return DocumentInterface
     */
    public function getParent()
    {
        return $this->_parent;
    }

    public function setParent(DocumentInterface $document)
    {
        $this->_parent = $document;
    }

    public function setAttribute($attribute, $value)
    {
        $setter = 'set' . $attribute;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return $this;
        }

        $this->$attribute = $value;
        return $this;
    }

    public function getAttribute($attribute)
    {
        $getter = 'get' . $attribute;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        $this->ensureBehaviors();
        foreach ($this->_behaviors as $behavior) {
            if ($behavior->behavior_name === $attribute && $behavior instanceof RelatableInterface) {
                /** @var \Reach\Mongo\RelatableInterface $behavior */
                if ($this->getIsNew() || $behavior->isResolved()) {
                    return $this->$attribute;
                } else {
                    return $this->$attribute = $behavior->resolve();
                }
            }

            if (property_exists(get_class($behavior), $attribute)) {
                return $behavior->$attribute;
            }
        }

        return $this->$attribute;
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getRawDocument(array $attributes = null)
    {
        $document = [];
        foreach ($this->attributes() as $attribute => $type) {
            if ($attributes && array_search($attribute, $attributes) === false) {
                continue;
            }

            if ($this->isMongoFriendlyType($this->$attribute)) {
                $document[$attribute] = $this->$attribute;
                continue;
            }

            if ($this->$attribute instanceof DocumentInterface) {
                $document[$attribute] = $this->$attribute->getRawDocument();
            } else {
                $document[$attribute] = serialize($this->$attribute);
            }
        }

        $this->ensureBehaviors();
        $class = get_class($this);
        foreach ($this->_behaviors as $behavior) {
            /** @var Behavior $behavior */
            $attribute = $behavior->behavior_name;
            if ($behavior instanceof SerializableInterface) {
                unset($document[$attribute]);

                if (isset($behavior->key)) {
                    /** @var \Reach\Mongo\RelatableInterface $behavior */
                    $attribute = $behavior->getKey($attribute);
                }

                $document[$attribute] = $behavior->serialize();
            }
        }

        return $document;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isMongoFriendlyType($value)
    {
        return  is_scalar($value) ||
                is_array($value) ||
                is_null($value) ||
                $value instanceof \MongoId ||
                $value instanceof \MongoRegex ||
                $value instanceof \MongoDate ||
                $value instanceof \MongoTimestamp ||
                $value instanceof \MongoInt32 ||
                $value instanceof \MongoInt64 ||
                $value instanceof \MongoBinData ||
                get_class($value) === 'stdClass' ||
                get_class($value) === '\stdClass';
    }

    public function save(array $options = [])
    {
    }
}
