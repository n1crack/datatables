<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Column;
use Ozdemir\Datatables\Iterators\ColumnCollection;
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

    /**
     * @param string $query
     * @param ColumnCollection $columns
     * @return mixed
     */
    public function makeQueryString(string $query, ColumnCollection $columns);

    /**
     * @param Query $query
     * @param string $column
     * @return mixed
     */
    public function makeDistinctQueryString(Query $query, string $column);

    /**
     * @param array $filter
     * @return mixed
     */
    public function makeWhereString(array $filter);

    /**
     * @param Query $query
     * @param Column $column
     * @param string $word
     * @return mixed
     */
    public function makeLikeString(Query $query, Column $column, string $word);

    /**
     * @param array $o
     * @return mixed
     */
    public function makeOrderByString(array $o);

    /**
     * @param $take
     * @param $skip
     * @return mixed
     */
    public function makeLimitString(int $take, int $skip);
}
