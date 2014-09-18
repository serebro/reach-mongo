<?php

namespace Model\Mongo;

class BehaviorLang extends \Reach\Behavior
{

    public function events()
    {
        return [
            'beforeConstruct' => [$this, 'beforeConstruct'],
            'beforeSave' => [$this, 'beforeSave'],
            'afterFind'  => [$this, 'afterFind']
        ];
    }

    public function beforeConstruct()
    {

    }

    public function beforeSave()
    {
    }

    public function afterFind()
    {
    }
}

