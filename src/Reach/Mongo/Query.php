<?php

namespace Reach\Mongo;

use Exception;

class Query extends Criteria implements QueryInterface
{

    protected $_hydrate_class;

    protected $_connection_name;


    public function __construct($criteria = null, $hydrate_class, $connection_name = null)
    {
        if (empty($hydrate_class)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_hydrate_class = $hydrate_class;
        $this->_connection_name = $connection_name;
        parent::__construct($criteria);
    }

    /**
     * @return array
     */
    public function explain()
    {
        $criteria = $this->_criteria;
        $criteria['$explain'] = true;
        $class = $this->_hydrate_class;
        $collection = $class::getCollection($this->_connection_name)->getMongoCollection();
        $cursor = iterator_to_array($collection->find($criteria));
        return reset($cursor);
    }

    /**
     * @param array $fields
     * @param bool  $as_array
     * @return null|DocumentInterface
     */
    public function findOne(array $fields = [], $as_array = false)
    {
        $class = $this->_hydrate_class;
        return $class::findOne($this, $fields, $as_array);
    }

    /**
     * @param array $fields
     * @return \Reach\Mongo\ResultSet
     */
    public function find(array $fields = [])
    {
        $class = $this->_hydrate_class;
        /** @var \Reach\Mongo\ResultSet $resultSet */
        $resultSet = $class::find($this, $fields);
        $resultSet->disableSort();
        return $resultSet;
    }
}
