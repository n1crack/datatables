<?php

namespace Ozdemir\Datatables;

use Ozdemir\Datatables\DB\DatabaseInterface;
use Ozdemir\Datatables\Iterators\ColumnCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Query Builder
 *
 * @package Ozdemir\Datatables
 */
class QueryBuilder
{
    /**
     * Base sql query string
     * @var Query
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
     * @var boolean
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
     * @var boolean
     */
    private $dataObject = false;

    /**
     *
     * @param string $query
     * @param Request $request
     * @param DatabaseInterface $db
     */
    public function __construct($query, Request $request, DatabaseInterface $db)
    {
        $this->request = $request;
        $this->db = $db;

        $columnNames = ColumnNameList::from($query);
        $this->setDataObject();

        $this->columns = new ColumnCollection();

        foreach ($columnNames as $name) {
            $this->columns->append(new Column($name));
        }

        $this->setQuery($query);
    }

    /**
     * Assign column attributes
     *
     */
    public function setColumnAttributes(): void
    {
        $columns = $this->request->get('columns');

        if ($columns) {
            $attributes = array_column($columns, null, 'data');

            foreach ($attributes as $index => $attr) {
                $this->columns->getOnlyVisibles()->get($index)->attr = $attr;
            }
        }
    }

    /**
     * @return bool
     */
    public function isDataObject(): bool
    {
        return $this->dataObject;
    }

    /**
     *
     */
    public function setDataObject(): void
    {
        if ($this->request->get('columns')) {
            $data = array_column($this->request->get('columns'), 'data');
            $rangeSet = array_map('strval', array_keys($data));
            $this->dataObject = array_intersect($data, $rangeSet) !== $data;
        }
    }

    /**
     * @return ColumnCollection
     */
    public function columns(): ColumnCollection
    {
        return $this->columns;
    }

    /**
     * @return bool
     */
    public function hasDefaultOrder(): bool
    {
        return $this->hasDefaultOrder;
    }

    /**
     * @param $query
     */
    public function setQuery($query): void
    {
        $this->query = new Query('Select '.implode(', ', $this->columns->names())." from ($query)t");
        $this->hasDefaultOrder = $this->hasOrderBy($query);
    }

    /**
     *
     */
    public function setFilteredQuery(): void
    {
        $this->filtered = new Query();
        $this->filtered->set($this->query.$this->filter($this->filtered));
    }

    /**
     *
     */
    public function setFullQuery(): void
    {
        $this->full = clone $this->filtered;
        $this->full->set($this->filtered.$this->orderBy().$this->limit());
    }

    /**
     * @param $column
     * @return Query
     */
    public function getDistinctQuery($column): Query
    {
        $distinct = clone $this->query;
        $distinct->set("SELECT $column FROM ({$this->query})t GROUP BY $column");

        return $distinct;
    }

    /**
     * @param string $query
     * @return bool
     */
    protected function hasOrderBy($query): bool
    {
        return (bool)\count(preg_grep("/(order\s+by)\s+(.+)$/i", explode("\n", $query)));
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function filter(Query $query): string
    {
        $filter = array_filter([
            $this->filterGlobal($query),
            $this->filterIndividual($query),
        ]);

        if (\count($filter) > 0) {
            return ' WHERE '.implode(' AND ', $filter);
        }

        return '';
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function filterGlobal(Query $query): string
    {
        $searchinput = $this->request->get('search')['value'];

        if ($searchinput === null || $searchinput === '') {
            return '';
        }

        $columns = $this->columns->getSearchableColumns();

        if (\count($columns) === 0) {
            return '';
        }

        $search = [];
        $searchinput = preg_replace("/\W+/u", ' ', $searchinput);

        foreach (explode(' ', $searchinput) as $word) {
            $look = [];

            foreach ($columns as $column) {
                $look[] = $column->name.' LIKE '.$this->db->escape('%'.$word.'%', $query);
            }

            $search[] = '('.implode(' OR ', $look).')';
        }

        return implode(' AND ', $search);
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function filterIndividual(Query $query): string
    {
        $columns = $this->columns->getSearchableColumnsWithSearchValue();

        if (\count($columns) === 0) {
            return '';
        }

        $look = [];

        foreach ($columns as $column) {
            if ($column->customFilter) {
                $filter = $column->customFilter;
                $customFilter = $filter(new FilterHelper($query, $column, $this->db));

                if ($customFilter) {
                    $look[] = $customFilter;
                }
            } else {
                $look[] = $column->name.' LIKE '.$this->db->escape('%'.$column->searchValue().'%', $query);
            }
        }
        if (empty($look)) {
            return '';
        }

        return ' ('.implode(' AND ', $look).')';
    }

    /**
     * @return string
     */
    protected function limit(): string
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
    protected function orderBy(): string
    {
        $orders = $this->request->get('order') ?: [];

        $orders = array_filter($orders, function ($order) {
            return \in_array($order['dir'], ['asc', 'desc'],
                    true) && $this->columns->getOnlyVisibles()->offsetGet($order['column'])->isOrderable();
        });

        $o = [];

        foreach ($orders as $order) {
            $id = $this->request->get('columns')[$order['column']]['data'];

            if ($this->columns->getOnlyVisibles()->isExists($id)) {
                $o[] = $this->columns->getOnlyVisibles()->get($id)->name.' '.$order['dir'];
            }
        }

        if (\count($o) === 0) {
            if ($this->hasDefaultOrder()) {
                return '';
            }
            $o[] = $this->defaultOrder();
        }

        return ' ORDER BY '.implode(',', $o);
    }

    /**
     * @return string
     */
    public function defaultOrder(): string
    {
        return $this->columns->getOnlyVisibles()->offsetGet(0)->name.' asc';
    }
}