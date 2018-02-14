<?php

namespace Ozdemir\Datatables;

/**
 * Class Query
 *
 * @package Ozdemir\Datatables
 */
class Query
{
    /**
     * Bare query string, user input
     * @var
     */
    public $bare;

    /**
     * Base sql query string without filters and orders
     * @var
     */
    public $base;

    /**
     * Full sql query string
     * @var
     */
    public $full;

    /**
     * the query has default ordering
     * @var
     */
    protected $hasDefaultOrder = false;

    /**
     * Query constructor.
     *
     * @param $query
     */
    public function __construct($query)
    {
        $this->bare = rtrim($query, '; ');
    }

    /**
     * @param $columns array
     * @return Query
     */
    public function set($columns)
    {

        $this->base = 'Select '.implode(', ', $columns)." from ({$this->bare})t";
        $this->hasDefaultOrder = $this->isQueryWithOrderBy($this->bare);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasDefaultOrder()
    {
        return $this->hasDefaultOrder;
    }

    /**
     * @param $query
     * @return bool
     */
    protected function isQueryWithOrderBy($query)
    {
        return (bool)count(preg_grep("/(order\s+by)\s+(.+)$/i", explode("\n", $query)));
    }
}