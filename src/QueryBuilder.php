<?php

namespace Ozdemir\Datatables;

use Ozdemir\Datatables\DB\DatabaseInterface;
use Ozdemir\Datatables\Iterators\ColumnCollection;

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
     * @var Option
     */
    private $options;

    /**
     * @var DatabaseInterface
     */
    private $db;

    /**
     * @var boolean
     */
    private $dataObject = false;

    /**
     * Used to add order by columns in front of 
     * users selected columns.  May be useful 
     * when some data should always be at the top
     *
     * @var string
     */
    private $orderByPrepend = "";

    /**
     * Used to add order by columns in back of 
     * users selected columns.  May not be useful 
     * but I added prepend so append seemed like 
     * it made sense.
     *
     * @var string
     */
    private $orderByAppend = "";

    /**
     *
     * @param string $query
     * @param Option $options
     * @param DatabaseInterface $db
     */
    public function __construct($query, Option $options, DatabaseInterface $db)
    {
        $this->options = $options;
        $this->db = $db;

        $columnNames = ColumnNameList::from($query);
        $this->dataObject = $this->checkAssoc();

        $this->columns = new ColumnCollection();

        foreach ($columnNames as $name) {
            $this->columns->append(new Column($name));
        }

        $this->setQuery($query);
        $this->hasDefaultOrder = $this->hasOrderBy($query);
    }

    /**
     * Assign column attributes
     *
     */
    public function setColumnAttributes(): void
    {
        $columns = $this->options->columns();
        if ($columns) {
            $attributes = array_column($columns, null, 'data');

            foreach ($attributes as $index => $attr) {
                if ($this->columns->visible()->isExists($index)) {
                    $this->columns->visible()->get($index)->attr = $attr;
                }
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
     * @return bool
     */
    public function checkAssoc(): bool
    {
        if (!$this->options->columns()) {
            return false;
        }

        $data = array_column($this->options->columns(), 'data');
        $rangeSet = array_map('strval', array_keys($data));

        return array_intersect($data, $rangeSet) !== $data;
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
        $this->query = new Query($this->db->makeQueryString($query, $this->columns));
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
     * @param $escapes
     */
    public function setEscapes($escapes): void
    {
        $this->query->escapes = array_merge($escapes, $this->query->escapes);
        $this->filtered->escapes = array_merge($escapes, $this->filtered->escapes);
        $this->full->escapes = array_merge($escapes, $this->full->escapes);
    }

    /**
     * @param $column
     * @return Query
     */
    public function getDistinctQuery($column): Query
    {
        $distinct = clone $this->query;
        $distinct->set($this->db->makeDistinctQueryString($this->query, $column));

        return $distinct;
    }

    /**
     * @param string $query
     * @return bool
     */
    protected function hasOrderBy($query): bool
    {
        return (bool)preg_match("/order by/i", $query);
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
            return $this->db->makeWhereString($filter);
        }

        return '';
    }

    /**
     * @param Query $query
     * @return string
     */
    protected function filterGlobal(Query $query): string
    {
        $searchinput = preg_replace("/\W+/u", ' ', $this->options->searchValue());
        $columns = $this->columns->searchable();

        if ($searchinput === null || $searchinput === '' || \count($columns) === 0) {
            return '';
        }

        $search = [];

        foreach (explode(' ', $searchinput) as $word) {
            $look = [];

            foreach ($columns as $column) {
                $look[] = $this->db->makeLikeString($query, $column, $word);
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
        $columns = $this->columns->individualSearchable();
        $look = [];

        foreach ($columns as $column) {
            $look[] = $this->columnFilter($column, new FilterHelper($query, $column, $this->db));
        }

        return implode(' AND ', $look);
    }

    /**
     * @return string
     */
    protected function limit(): string
    {
        $skip = $this->options->start();
        $take = $this->options->length() ?: 10;

        if ($take === -1 || !$this->options->draw()) {
            return '';
        }

        return $this->db->makeLimitString($take, $skip);
    }

    /**
     * @return string
     */
    protected function orderBy(): string
    {
        $orders = $this->options->order();

        $orders = array_filter($orders, function ($order) {
            return \in_array($order['dir'], ['asc', 'desc'],
                    true) && $this->columns->visible()->offsetGet($order['column'])->isOrderable();
        });

        $o = [];
        if(!empty($this->orderByPrepend))
            $o[] = $this->orderByPrepend;

        foreach ($orders as $order) {
            $id = $this->options->columns()[$order['column']]['data'];

            if ($this->columns->visible()->isExists($id)) {
                $o[] = $this->columns->visible()->get($id)->name.' '.$order['dir'];
            }
        }

        if (\count($o) === 0) {
            if ($this->hasDefaultOrder()) {
                return '';
            }
            $o[] = $this->defaultOrder();
        }

        if(!empty($this->orderByAppend))
            $o[] = $this->orderByAppend;

        return $this->db->makeOrderByString($o);
    }

    /**
     * Order by prepend getter / setter
     *
     * @param string $orderByString
     * @return string
     */
    public function orderByPrepend($orderByString = ""): string {
        if(!empty($orderByString))
            $this->orderByPrepend = $orderByString;

        return $this->orderByPrepend;
    }

    /**
     * Order by append getter / setter
     *
     * @param string $orderByString
     * @return void
     */
    public function orderByAppend($orderByString = ""): string {
        if(!empty($orderByString))
            $this->orderByAppend = $orderByString;

        return $this->orderByAppend;
    }

    /**
     * @return string
     */
    public function defaultOrder(): string
    {
        return $this->columns->visible()->offsetGet(0)->name.' asc';
    }

    /**
     * @param Column $column
     * @param FilterHelper $helper
     * @return string
     */
    public function columnFilter(Column $column, FilterHelper $helper): string
    {
        if ($column->hasFilter()) {
            return $column->customFilter->call($helper) ?? $helper->defaultFilter();
        }

        return $helper->defaultFilter();
    }
}