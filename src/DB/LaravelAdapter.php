<?php

namespace Ozdemir\Datatables\DB;

use DB;
use Ozdemir\Datatables\Column;
use Ozdemir\Datatables\Iterators\ColumnCollection;
use Ozdemir\Datatables\Query;


/**
 * Class LaravelAdapter
 * @package Ozdemir\Datatables\DB
 */
class LaravelAdapter extends DBAdapter
{
    /**
     * LaravelAdapter constructor.
     * @param  null  $config
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
     * @param  Query  $query
     * @return array
     */
    public function query(Query $query)
    {
        $data = DB::select($query, $query->escapes);
        $row = [];

        foreach ($data as $item) {
            $row[] = (array) $item;
        }

        return $row;
    }

    /**
     * @param  Query  $query
     * @return mixed
     */
    public function count(Query $query)
    {
        $data = DB::select("Select count(*) as rowcount from ($query)t", $query->escapes);

        return $data[0]->rowcount;
    }

    /**
     * @param $string
     * @param  Query  $query
     * @return string
     */
    public function escape($string, Query $query)
    {
        $query->escapes[':binding_'.(count($query->escapes) + 1)] = $string;

        return ':binding_'.count($query->escapes);
    }

    /**
     * The base adapter quotes identifiers with MySQL backticks, which are not
     * valid in PostgreSQL. Use the driver of the active connection to pick the
     * right identifier quoting.
     *
     * @param string $query
     * @param ColumnCollection $columns
     * @return string
     */
    public function makeQueryString(string $query, ColumnCollection $columns): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return 'SELECT "'.implode('", "', $columns->names())."\" FROM ($query)t";
        }

        return parent::makeQueryString($query, $columns);
    }

    /**
     * PostgreSQL's LIKE is case-sensitive and only works on text types, so use a
     * cast + ILIKE there (mirrors the dedicated PGSQL adapter).
     *
     * @param Query $query
     * @param Column $column
     * @param string $word
     * @return string
     */
    public function makeLikeString(Query $query, Column $column, string $word)
    {
        if (DB::getDriverName() === 'pgsql') {
            return $column->name.'::varchar ILIKE '.$this->escape('%'.$word.'%', $query);
        }

        return parent::makeLikeString($query, $column, $word);
    }

    /**
     * @param $query
     * @return string
     */
    public function getQueryString($query): string
    {
        if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            return vsprintf(str_replace('?', '%s', $query->toSql()),
                collect($query->getBindings())
                    ->map(function ($binding) {
                        return is_numeric($binding) ? $binding : "'$binding'";
                    })
                    ->toArray());
        }elseif ($query instanceof \Illuminate\Database\Eloquent\Collection) {
            throw new \Exception('The library does not support Eloquent Collections. Use the Eloquent Builder class instead.');
        }

        return $query;
    }
}
