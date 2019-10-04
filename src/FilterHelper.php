<?php

namespace Ozdemir\Datatables;


use Ozdemir\Datatables\DB\DatabaseInterface;

/**
 * Class FilterHelper
 * @package Ozdemir\Datatables
 */
class FilterHelper
{
    /**
     * @var Query
     */
    private $query;
    /**
     * @var Column
     */
    private $column;
    /**
     * @var DatabaseInterface
     */
    private $db;

    /**
     * FilterHelper constructor.
     * @param Query $query
     * @param Column $column
     * @param DatabaseInterface $db
     */
    public function __construct(Query $query, Column $column, DatabaseInterface $db)
    {
        $this->query = $query;
        $this->column = $column;
        $this->db = $db;
    }

    /**
     * @param $value
     * @return string
     */
    public function escape($value): string
    {
        return $this->db->escape($value, $this->query);
    }

    /**
     * @return string
     */
    public function searchValue(): string
    {
        return $this->column->searchValue();
    }

    /**
     * @return string
     */
    public function defaultFilter(): string
    {
        return $this->db->makeLikeString($this->query, $this->column, $this->searchValue());
    }

    /**
     * @param $low
     * @param $high
     * @return string
     */
    public function between($low, $high): string
    {
        $filter = [];
        if ($low) {
            $filter[] = $this->greaterThan($low);
        }
        if ($high) {
            $filter[] = $this->lessThan($high);
        }

        if (empty($filter)) {
            return $this->defaultFilter();
        }

        return implode(' AND ', $filter);
    }

    /**
     * @param $array
     * @return string
     */
    public function whereIn($array): string
    {
        $array = array_map(function ($value) {
            return $this->escape($value);
        }, $array);

        return $this->column->name.' IN ('.implode(', ', $array).')';
    }


    /**
     * @param $value
     * @return string
     */
    public function greaterThan($value): string
    {
        return $this->column->name.' >= '.$this->escape($value);
    }

    /**
     * @param $value
     * @return string
     */
    public function lessThan($value): string
    {
        return $this->column->name.' <= '.$this->escape($value);
    }

}