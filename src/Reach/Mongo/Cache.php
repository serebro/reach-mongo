<?php

namespace Reach\Mongo;

use MongoDate;

class Cache extends \Reach\Cache
{

    public $connection_name = 'default';

    public $collection_name = 'cache';


    /**
     * @todo http://php.net/manual/en/mongocollection.ensureindex.php
     */
    public function initCollection()
    {
        $collection = $this->getCollection();
        $collection->ensureIndex(['expired' => 1], ['expireAfterSeconds' => 0]);
        $collection->ensureIndex(['key' => 1], ['unique' => 1]);
        $collection->ensureIndex(['key' => 1, 'expired' => 1]);
    }

    /**
     * @return \MongoCollection
     */
    protected function getCollection()
    {
        return $this->getConnection()->getMongoCollection($this->collection_name);
    }

    /**
     * @return \Reach\Mongo\Connection
     */
    protected function getConnection()
    {
        return ConnectionManager::getConnection($this->connection_name);
    }

    /**
     * @param $hashedKey
     * @return null|mixed
     */
    protected function storageGet($hashedKey)
    {
        $res = $this->getCollection()->findOne(
            [
                'key'     => $hashedKey,
                'expired' => ['$gt' => new MongoDate()]
            ]
        );
        if ($res) {
            return $res['data'];
        }

        return null;
    }

    /**
     * @param $hashedKey
     * @return bool
     */
    protected function storageExists($hashedKey)
    {
        return !!$this->getCollection()->count(['key' => $hashedKey]);
    }

    /**
     * @param $hashedKey
     * @return bool
     */
    protected function storageDelete($hashedKey)
    {
        $result = $this->getCollection()->remove(['key' => $hashedKey]);
        return !empty($result['ok']);
    }

    /**
     * @param     $hashedKey
     * @param     $value
     * @param int $duration seconds
     * @return mixed
     */
    protected function storageSet($hashedKey, $value, $duration = null)
    {
        if (null === $duration) {
            $duration = $this->ttl;
        }

        $result = $this->getCollection()->update(
            [
                'key' => $hashedKey,
            ],
            [
                'key'     => $hashedKey,
                'expired' => new MongoDate($duration + time()),
                'now'     => new MongoDate(time()),
                'data'    => $value,
            ],
            ['upsert' => true]
        );

        return !empty($result['ok']);
    }
}
