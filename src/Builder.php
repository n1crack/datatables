<?php

namespace Ozdemir\Datatables;

use Ozdemir\Datatables\DB\DatabaseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Builder
 *
 * @package Ozdemir\Datatables
 */
class Builder
{
    /**
     * Bare query string, user input
     * @var string
     */
    public $bare;

    /**
     * Base sql query string
     * @var Query $query
     */
    public $query;

    /**
     * Filtered sql query string
     * @var Query
     */
    public $filtered;

    /**
     * Full sql query string
     * @var Query
     */
    public $full;

    /**
     * the query has default ordering
     * @var
     */
    protected $hasDefaultOrder = false;

    /**
     * the query has default ordering
     * @var ColumnCollection
     */
    private $columns;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var DatabaseInterface
     */
    private $db;

    /**
     * Builder constructor.
     *
     * @param $query
     * @param Request $request
     * @param DatabaseInterface $db
     */
    public function __construct($query, Request $request, DatabaseInterface $db)
    {
        $this->request = $request;
        $this->db = $db;

        $this->bare = rtrim($query, '; ');
    }

    /**
     * @param ColumnCollection $columns
     */
    public function init(ColumnCollection $columns)
    {
        $this->columns = $columns;
        $this->query = new Query('Select '.implode(', ', $this->columns->names())." from ({$this->bare})t");
        $this->hasDefaultOrder = $this->isQueryWithOrderBy($this->bare);
    }

    /**
     * @return bool
     */
    public function hasDefaultOrder()
    {
        return $this->hasDefaultOrder;
    }

    /**
     *
     */
    public function setFilteredQuery()
    {
        $this->filtered = new Query();
        $this->filtered->set($this->query.$this->filter($this->filtered));
    }

    /**
     *
     */
    public function setFullQuery()
    {
        $this->full = clone $this->filtered;
        $this->full->set($this->filtered.$this->orderBy().$this->limit());
    }

    /**
     * @param string $query
     * @return bool
     */
    protected function isQueryWithOrderBy($query)
    {
        return (bool)\count(preg_grep("/(order\s+by)\s+(.+)$/i", explode("\n", $query)));
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function filter(Query $query)
    {
        $filter = array_filter([
            $this->filterGlobal($query),
            $this->filterIndividual($query),
        ]);

        if (count($filter) > 0) {
            return ' WHERE '.implode(' AND ', $filter);
        }

        return '';
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function filterGlobal(Query $query)
    {
        $searchinput = $this->request->get('search')['value'];

        if ($searchinput === null || $searchinput === '') {
            return '';
        }

        $columns = $this->columns->getSearchableColumns();

        if (count($columns) === 0) {
            return '';
        }

        $search = [];
        $searchinput = preg_replace("/\W+/u", ' ', $searchinput);

        foreach (explode(' ', $searchinput) as $word) {
            $look = [];

            foreach ($columns as $column) {
                $look[] = $column->name.' LIKE '.$this->db->escape('%'. $word. '%', $query);
            }

            $search[] = '('.implode(' OR ', $look).')';
        }

        return implode(' AND ', $search);
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function filterIndividual(Query $query)
    {
        $columns = $this->columns->getSearchableColumnsWithSearchValue();

        if (\count($columns) === 0) {
            return '';
        }

        $look = [];

        foreach ($columns as $column) {
            if ($column->customFilter) {
                $filter = $column->customFilter;
                $customFilter = $filter(function($value) use ($query){
                        return $this->db->escape($value, $query);
                 }, $column->searchValue());

                if ($customFilter) {
                    $look[] = $customFilter;
                }
            } else {
                $look[] = $column->name.' LIKE '.$this->db->escape('%'. $column->searchValue() . '%', $query);
            }
        }

        return ' ('.implode(' AND ', $look).')';
    }

    /**
     * @return string
     */
    protected function limit()
    {
        $take = 10;
        $skip = (integer)$this->request->get('start');

        if ($this->request->get('length')) {
            $take = (integer)$this->request->get('length');
        }

        if ($take === -1 || !$this->request->get('draw')) {
            return '';
        }

        return " LIMIT $take OFFSET $skip";
    }

    /**
     * @return string
     */
    protected function orderBy()
    {
        $orders = $this->request->get('order') ?: [];

        $orders = array_filter($orders, function ($order) {
            return \in_array($order['dir'], ['asc', 'desc'],
                    true) && $this->columns->getByIndex($order['column'])->isOrderable();
        });

        $o = [];

        if (count($orders) === 0) {
            if ($this->hasDefaultOrder()) {
                return '';
            }
            $o[] = $this->columns->getByIndex(0)->name.' asc';
        }

        foreach ($orders as $order) {
            $o[] = $this->columns->getByIndex($order['column'])->name.' '.$order['dir'];
        }

        return ' ORDER BY '.implode(',', $o);
    }

}