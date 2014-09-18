<?php

namespace Reach\Mongo;

use Exception;
use MongoCode;

class Criteria implements CriteriaInterface
{

    protected $_criteria = [];

    protected static $selectors = [
        '>'      => '$gt',
        '>='     => '$gte',
        '<'      => '$lt',
        '=<'     => '$lte',
        '!='     => '$ne',
        '<>'     => '$ne',
    ];

    /**
     * @param mixed  $query
     * @param string $pk
     * @return array
     */
    public static function  normalize($query = null, $pk = '_id')
    {
        if ($query === null) {
            $query = [];
        } else if ($query instanceof Criteria) {
            $query = $query->asArray();
            $query = $query['$query'];
        }

        if (is_scalar($query) || $query instanceof \MongoId) {
            $query = Collection::isValidMongoId($query) ? Collection::ensureMongoId($query) : $query;
            $query = [$pk => $query];
        } else if (!empty($query[$pk])) {
            $query[$pk] = Collection::isValidMongoId($query[$pk]) ? Collection::ensureMongoId(
                $query[$pk]
            ) : $query[$pk];
        } else if (empty($query)) {
            $query = [];
        }

        return $query;
    }

    public function __construct($criteria = null)
    {
        if (empty($criteria)) {
            return;
        }

        if (!$criteria instanceof CriteriaInterface && !is_array($criteria)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($criteria instanceof CriteriaInterface) {
            $criteria = $criteria->asArray();
        }

        $this->_criteria = $criteria;
    }

    /**
     * Joins query clauses with a logical AND returns all documents that match the conditions of both clauses.
     * @param mixed  $criteria
     * @param string $selector
     * @param mixed  $value
     * @throws Exception
     * @return Criteria
     */
    public function add($criteria, $selector = null, $value = null)
    {
        return $this->_buildQuery('$and', $criteria, $selector, $value);
    }

    /**
     * Joins query clauses with a logical OR returns all documents that match the conditions of either clause.
     * @param mixed  $criteria
     * @param string $selector
     * @param mixed  $value
     * @return Criteria
     * @throws Exception
     */
    public function addOr($criteria, $selector = null, $value = null)
    {
        return $this->_buildQuery('$or', $criteria, $selector, $value);
    }

    /**
     * Joins query clauses with a logical NOR returns all documents that fail to match both clauses.
     * @param mixed  $criteria
     * @param string $selector
     * @param mixed  $value
     * @throws Exception
     * @return Criteria
     */
    public function addNor($criteria, $selector = null, $value = null)
    {
        return $this->_buildQuery('$nor', $criteria, $selector, $value);
    }

    /**
     * Matches documents that satisfy a JavaScript expression.
     * @param string|MongoCode $code
     * @param array            $scope
     * @throws Exception
     * @return CriteriaInterface
     */
    public function where($code, array $scope = [])
    {
        if (!is_string($code) && !($code instanceof MongoCode)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($code)) {
            $code = new MongoCode($code, $scope);
        }

        $this->_criteria['$where'] = $code;

        return $this;
    }

    /**
     * Returns a cursor with documents sorted according to a sort specification.
     * @param array $order
     * @throws Exception
     * @return CriteriaInterface
     */
    public function orderBy(array $order)
    {
        if (!is_array($order)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!isset($this->_criteria['$orderby'])) {
            $this->_criteria['$orderby'] = [];
        }

        $this->_criteria['$orderby'] = array_merge($this->_criteria['$orderby'], $order);
        return $this;
    }

    /**
     * Adds a comment to the query to identify queries in the database profiler output.
     * @param string $comment
     * @throws Exception
     * @return CriteriaInterface
     */
    public function comment($comment)
    {
        if (!is_string($comment)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_criteria['$comment'] = $comment;
        return $this;
    }

    /**
     * Forces MongoDB to use a specific index.
     * @param array $hint
     * @throws Exception
     * @return CriteriaInterface
     */
    public function hint(array $hint)
    {
        if (!is_array($hint)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_criteria['$hint'] = $hint;
        return $this;
    }

    /**
     * Limits the number of documents scanned.
     * @param int $maxScan
     * @throws Exception
     * @return CriteriaInterface
     */
    public function maxScan($maxScan)
    {
        if (!is_numeric($maxScan)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_criteria['$maxScan'] = $maxScan;
        return $this;
    }

    /**
     * Specifies a cumulative time limit in milliseconds for processing operations on a cursor.
     * @param int $maxTimeMS
     * @throws Exception
     * @return CriteriaInterface
     */
    public function maxTimeMS($maxTimeMS)
    {
        if (!is_numeric($maxTimeMS)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_criteria['$maxTimeMS'] = $maxTimeMS;
        return $this;
    }

    /**
     * Specifies an exclusive upper limit for the index to use in a query.
     * @param int $max
     * @throws Exception
     * @return CriteriaInterface
     */
    public function max($max)
    {
        if (!is_numeric($max)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_criteria['$maxTimeMS'] = $max;
        return $this;
    }

    /**
     * Specifies an inclusive lower limit for the index to use in a query.
     * @param int $min
     * @throws Exception
     * @return CriteriaInterface
     */
    public function min($min)
    {
        if (!is_numeric($min)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_criteria['$min'] = $min;
        return $this;
    }

    /**
     * Forces the query to use the index on the _id field.
     * @return CriteriaInterface
     */
    public function snapshot()
    {
        $this->_criteria['$snapshot'] = true;
        return $this;
    }

    public function getOrderBy()
    {
        return isset($this->_criteria['$orderby']) ? $this->_criteria['$orderby'] : [];
    }

    public function getQuery()
    {
        return isset($this->_criteria['$query']) ? $this->_criteria['$query'] : [];
    }

    /**
     * @return array
     */
    public function asArray()
    {
        return $this->_criteria;
    }

    public function _buildQuery($operator, $criteria, $selector = null, $value = null)
    {
        if (!$criteria instanceof CriteriaInterface && !is_array($criteria) && !$selector && !$value) {
            throw new Exception('Invalid parameter type.');
        }

        if ($selector && $value && !is_string($criteria)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($criteria) && !is_string($selector)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($criteria instanceof CriteriaInterface) {
            $criteria = $criteria->asArray();
        } else if (is_string($criteria)) {
            if (in_array($selector, ['=', '=='])) {
                $c = [$criteria => $value];
            } else {
                $c['$query'] = $this->_prepareCriteria([$criteria => [$selector => $value]]);
            }
            $criteria = $c;
        } else {
            $c['$query'] = $this->_prepareCriteria($criteria);
            $criteria = $c;
        }

        if (!isset($this->_criteria['$query'][$operator])) {
            $this->_criteria['$query'][$operator] = [];
        }

        $this->_criteria['$query'][$operator][] = $criteria['$query'];

        return $this;
    }

    public function convertSelectors($key)
    {
        $k = strtolower($key);
        if (array_key_exists($k, self::$selectors)) {
            $key = self::$selectors[$k];
        }

        return $key;
    }

    private function _prepareCriteria(array $criteria = [])
    {
        if (empty($criteria)) {
            return [];
        }

        $result = [];
        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $value = $this->_prepareCriteria($value);
            }
            $key = $this->convertSelectors($key);
            $result[$key] = $value;
        }

        return $result;
    }
}
