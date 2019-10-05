<?php

namespace Ozdemir\Datatables\DB;

use DB;
use Ozdemir\Datatables\Query;


/**
 * Class LaravelAdapter
 * @package Ozdemir\Datatables\DB
 */
class LaravelAdapter extends DBAdapter
{
    /**
     * LaravelAdapter constructor.
     * @param null $config
     */
    public function __construct($config = null)
    {
    }

    /**
     * @return $this
     */
    public function connect()
    {
        return $this;
    }

    /**
     * @param Query $query
     * @return array
     */
    public function query(Query $query)
    {
        $data = DB::select($query, $query->escapes);
        $row = [];

        foreach ($data as $item) {
            $row[] = (array)$item;
        }

        return $row;
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function count(Query $query)
    {
        $data = DB::select("Select count(*) as rowcount from ($query)t", $query->escapes);

        return $data[0]->rowcount;
    }

    /**
     * @param $string
     * @param Query $query
     * @return string
     */
    public function escape($string, Query $query)
    {
        $query->escapes[':binding_'.(count($query->escapes) + 1)] = $string;

        return ':binding_'.count($query->escapes);
    }
}

