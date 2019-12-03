<?php

namespace Ozdemir\Datatables;

/**
 * Class Query
 * @package Ozdemir\Datatables
 */
class Query
{
    /**
     * Bare query string, user input
     * @var
     */
    public $escapes = [];

    /**
     * Query string
     * @var
     */
    public $sql;

    /**
     * Builder constructor.
     *
     * @param $query
     */
    public function __construct($query = '')
    {
        $this->sql = $query;
    }

    /**
     * Builder constructor.
     *
     * @param $query
     */
    public function set($query): void
    {
        $this->sql = $query;
    }

    /**
     *
     */
    public function __toString()
    {
        return $this->sql;
    }

}