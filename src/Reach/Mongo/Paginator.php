<?php

namespace Reach\Mongo;

use Reach\PaginatorInterface;
use stdClass;

class Paginator implements PaginatorInterface
{

    protected $_limit;

    protected $_resultSet;

    protected $_current_page;


    /**
     * @param ResultSet $resultSet
     * @param int       $limit
     * @param int       $current_page
     */
    public function __construct(ResultSet $resultSet, $limit = 10, $current_page = 1)
    {
        $this->_resultSet = $resultSet;
        $this->_current_page = max((int)$current_page, 0);
        $this->_limit = max((int)$limit, 0);
    }

    /**
     * Set the current page number
     * @param int $number
     * @return Paginator
     */
    public function setCurrentPage($number)
    {
        $this->_current_page = max((int)$number, 0);
        return $this;
    }

    /**
     * Returns a slice of the ResultSet for the pagination
     * @return stdClass with properties: first, current, next, prev, last, pages, items
     */
    public function getPaginate()
    {
        $page = new stdClass();
        $number_of_items = $this->_resultSet->count();
        $total_pages = ceil($number_of_items / $this->_limit);

        $page->first = 1;
        $page->current = $this->_current_page;
        $page->next = $this->_current_page < $total_pages ? $this->_current_page + 1 : $total_pages;
        $page->prev = $this->_current_page > 1 ? $this->_current_page - 1 : 1;
        $page->last = $total_pages;
        $page->pages = $total_pages;
        $page->total_items = $number_of_items;
        $page->items = $this->_resultSet
            ->skip($this->_limit * ($this->_current_page - 1))
            ->limit($this->_limit);

        return $page;
    }

    /**
     * Set rows limit
     * @param int $limit
     * @return Paginator
     */
    public function setLimit($limit)
    {
        $this->_limit = max((int)$limit, 0);
        return $this;
    }

    /**
     * Get current rows limit
     * @return int $limit
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * Get current page number
     */
    public function getCurrentPage()
    {
        return $this->_current_page;
    }

    /**
     * @param ResultSet $resultSet
     * @return Paginator
     */
    public function setResultSet(ResultSet $resultSet)
    {
        $this->_resultSet = $resultSet;
        return $this;
    }

    /**
     * @return Paginator
     */
    public function getResultSet()
    {
        return $this->_resultSet;
    }
}
