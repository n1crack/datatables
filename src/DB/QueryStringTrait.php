<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Column;
use Ozdemir\Datatables\Iterators\ColumnCollection;
use Ozdemir\Datatables\Query;

trait QueryStringTrait
{
    /**
     * @param string $query
     * @param ColumnCollection $columns
     * @return string
     */
    public function makeQueryString(string $query, ColumnCollection $columns): string
    {
        return 'SELECT '.implode(', ', $columns->names())." FROM ($query)t";
    }

    /**
     * @param Query $query
     * @param string $column
     * @return string
     */
    public function makeDistinctQueryString(Query $query, string $column): string
    {
        return "SELECT $column FROM ($query)t GROUP BY $column";
    }

    /**
     * @param $filter
     * @return string
     */
    public function makeWhereString($filter)
    {
        return ' WHERE '.implode(' AND ', $filter);
    }

    /**
     * @param Query $query
     * @param Column $column
     * @param $word
     * @return string
     */
    public function makeLikeString(Query $query, Column $column, $word)
    {
        return $column->name.' LIKE '.$this->escape('%'.$word.'%', $query);
    }

    /**
     * @param $o
     * @return string
     */
    public function makeOrderByString($o)
    {
        return ' ORDER BY '.implode(',', $o);
    }

    /**
     * @param $take
     * @param $skip
     * @return string
     */
    public function makeLimitString($take, $skip)
    {
        return " LIMIT $take OFFSET $skip";
    }
}