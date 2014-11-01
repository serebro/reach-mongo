<?php

namespace Reach\Mongo\Behavior\Generator;

use Reach\Behavior;
use Reach\Event;

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
     * @throws \Exception
     */
    public function beforeInsert(Event $event)
    {
        $primaryKey = $this->owner->getPrimaryKey();
        if (is_int($this->owner->$primaryKey)) {
            return null;
        }
        $id = $this->getNewId();
        $this->owner->$primaryKey = $id;
        return $id;
    }

    public function getNewId()
    {
        $collection_name = $this->owner->getCollectionName();
        $connection_name = $this->owner->getConnectionName();
        $collection = \Reach\Service\Container::get($connection_name)->getCollection($this->collection_name);
        $result = $collection->findAndModify(
            ['_id' => $collection_name],
            ['$inc' => ['sequence' => 1]],
            ['sequence' => 1],
            ['new' => true, 'upsert' => true]
        );

        if (empty($result)) {
            throw new \Exception('Unknown error'); // todo
        }

        return $result['sequence'];
    }
}
