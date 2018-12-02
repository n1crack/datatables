<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Query;

interface DatabaseInterface
{
    public function __construct($config);

    /**
     * @return void
     */
    public function connect();

    /**
     * @param Query $query
     * @return array
     */
    public function query(Query $query);

    /**
     * @param Query $query
     * @return int
     */
    public function count(Query $query);

    /**
     * @param $string
     * @param Query $query
     * @return string
     */
    public function escape($string, Query $query);
}
