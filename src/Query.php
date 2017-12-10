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
     * @var
     * Bare query string, user input
     */
    public $bare;

    /**
     * @var
     * Base sql query string without filters and orders
     */
    public $base;

    /**
     * @var
     * Full sql query string
     */
    public $full;

    /**
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
        $this->bare = rtrim($query, "; ");
    }

    /**
     * @param $columns
     * @return \Ozdemir\Datatables\Query
     */
    public function set($columns)
    {
        $this->base = "Select $columns from ({$this->bare})t";
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
        return (bool) count(preg_grep("/(order\s+by)\s+(.+)$/i", explode("\n", $query)));
    }
}