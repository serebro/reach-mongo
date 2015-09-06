<?php

namespace Reach\Mongo\Behavior\Generator;

use Exception;
use Reach\Behavior;
use Reach\Event;
use Reach\Service\Container;

class SequenceId extends Behavior
{

    /** @var string */
    public $collection_name = '_sequence_ids';


    public function events()
    {
        return [
            'beforeInsert' => [$this, 'beforeInsert']
        ];
    }

    /**
     * @param Event $event
     * @return null|int
     * @throws Exception
     */
    public function beforeInsert(Event $event)
    {
        $field = $this->behavior_name;
        $pk = $this->owner->getPrimaryKey();
        if ($this->owner->getIsNew() && $this->owner->{$pk} === null) {
            $id = $this->getNewId();
        } else {
            $id = $this->owner->{$pk};
        }
        $this->owner->$field = $id;
        return $id;
    }

    public function getNewId()
    {
        $collection_name = $this->owner->getCollectionName();
        $connection_name = $this->owner->getConnectionName();
        $collection = Container::getDI()->get($connection_name)->getCollection($this->collection_name);
        $result = $collection->findAndModify(
            ['_id' => $collection_name],
            ['$inc' => ['sequence' => 1]],
            ['sequence' => 1],
            ['new' => true, 'upsert' => true]
        );

        if (empty($result)) {
            throw new Exception('Unknown error'); // todo
        }

        return $result['sequence'];
    }
}
