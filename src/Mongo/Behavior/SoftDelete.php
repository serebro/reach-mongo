<?php

namespace Reach\Mongo\Behavior;

use MongoDate;
use Reach\Behavior;
use Reach\Event;

class SoftDelete extends Behavior
{

    public $attribute = 'deleted_at';


    public function events()
    {
        return [
            'beforeDelete' => [$this, 'beforeDelete'],
        ];
    }

    /**
     * @param Event $event
     */
    public function beforeDelete(Event $event)
    {
        $this->remove();
        $event->is_valid = false;
    }

    /**
     * @throws \Exception
     * @return bool
     */
    public function remove()
    {
        if (!property_exists(get_class($this->owner), $this->attribute)) {
            throw new \Exception(
                'This property "' . $this->attribute . '" does not exist in this model "' . get_class(
                    $this->owner
                ) . '"'
            );
        }

        $this->owner->{$this->attribute} = new MongoDate();
        return $this->owner->save();
    }

    /**
     * @throws \Exception
     * @return bool
     */
    public function restore()
    {
        if (!property_exists(get_class($this->owner), $this->attribute)) {
            throw new \Exception(
                'This property "' . $this->attribute . '" does not exist in this model "' . get_class(
                    $this->owner
                ) . '"'
            );
        }

        $this->owner->{$this->attribute} = null;
        return $this->owner->save();
    }
}
