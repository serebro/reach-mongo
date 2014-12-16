<?php

namespace Reach\Mongo\Behavior;

use Reach\Behavior;
use Traversable;

class LangText extends Behavior implements SerializableInterface
{

    use FieldTrait;

    protected $default_lang = 'en';

    protected $langs = [];

    private $_old_value;

    public function events()
    {
        return [
            'afterConstruct' => [$this, 'afterConstruct'],
        ];
    }

    public function __set($attribute, $value)
    {
        $this->set($value, $attribute);
    }

    public function __get($attribute)
    {
        return $this->get($attribute);
    }

    /**
     * @param string $lang - current language
     * @return string
     */
    public function get($lang)
    {
        if (isset($this->$lang)) {
            return $this->$lang;
        }

        return null;
    }

    /**
     * @param string $value
     * @param string $lang - current language
     * @return $this
     */
    public function set($value, $lang = null)
    {
        if (!$lang) {
            $lang = $this->default_lang;
        }
        if (array_search($lang, $this->langs) === false) {
            $this->langs[] = $lang;
        }

        $this->$lang = $value;
        return $this;
    }

    public function afterConstruct()
    {
        $field = $this->behavior_name;
        if (isset($this->owner->{$field})) {
            $this->_old_value = $this->owner->{$field};
        }

        if (is_array($this->_old_value) || $this->_old_value instanceof Traversable) {
            foreach ($this->_old_value as $lang => $value) {
                $this->set($value, $lang);
            }
        }

        $this->owner->{$field} = $this;
    }

    /**
     * @param $lang
     * @return $this
     */
    public function setDefaultLang($lang)
    {
        $this->default_lang = $lang;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultLang()
    {
        return $this->default_lang;
    }


    public function serialize()
    {
        $result = [];
        foreach ($this->langs as $lang) {
            $result[$lang] = $this->$lang;
        }

        return $result;
    }

    public function unserialize($serialized)
    {
        if (!is_array($serialized)) {
            return null;
        }

        foreach ($serialized as $lang => $value) {
            $this->$lang = $value;
        }
    }
}
