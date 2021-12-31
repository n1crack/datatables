<?php

namespace Ozdemir\Datatables;

use Closure;
use Ozdemir\Datatables\DB\DatabaseInterface;
use Ozdemir\Datatables\Http\Request;
use Ozdemir\Datatables\Iterators\ColumnCollection;

/**
 * Class Datatables
 *
 * @package Ozdemir\Datatables
 */
class Datatables
{
    /**
     * @var DatabaseInterface
     */
    protected $db;

    /**
     * @var ColumnCollection
     */
    protected $columns;

    /**
     * @var QueryBuilder
     */
    protected $builder;

    /**
     * @var Option
     */
    protected $options;

    /**
     * Custom escapes
     * @var array
     */
    public $escapes = [];

    /**
     * @var array
     */
    protected $response;

    /**
     * @var array
     */
    protected $distinctColumn = [];

    /**
     * @var array
     */
    protected $distinctData = [];

    /**
     * Datatables constructor.
     *
     * @param DatabaseInterface $db
     * @param Request $request
     */
    public function __construct(DatabaseInterface $db, Request $request = null)
    {
        $this->db = $db->connect();
        $this->options = new Option($request ?: Request::createFromGlobals());
    }

    /**
     * @param $column
     * @param Closure $closure
     * @return Datatables
     */
    public function add($column, Closure $closure): Datatables
    {
        $column = new Column($column);
        $column->closure = $closure;
        $column->interaction = false;
        $this->columns->append($column);

        return $this;
    }

    /**
     * @param $column
     * @param Closure $closure
     * @return Datatables
     */
    public function edit($column, Closure $closure): Datatables
    {
        $column = $this->columns->getByName($column);
        $column->closure = $closure;

        return $this;
    }

    /**
     * @param $column
     * @param Closure $closure
     * @return Datatables
     */
    public function filter($column, Closure $closure): Datatables
    {
        $column = $this->columns->getByName($column);
        $column->customFilter = $closure;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return Datatables
     */
    public function escape($key, $value): Datatables
    {
        $this->escapes[$key] = $value;

        return $this;
    }

    /**
     * @param $name
     * @return Datatables
     */
    public function setDistinctResponseFrom($name): Datatables
    {
        $this->distinctColumn[] = $name;

        return $this;
    }

    /**
     * @param $array
     * @return Datatables
     */
    public function setDistinctResponse($array): Datatables
    {
        $this->distinctData = $array;

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns->names();
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->builder->full;
    }

    /**
     * @param string $column
     * @return Datatables
     */
    public function hide(string $column, $searchable = false): Datatables
    {
        $this->columns->getByName($column)->hide($searchable);

        return $this;
    }

    /**
     * @param string $query
     * @return Datatables
     */
    public function query($query): Datatables
    {
        $query = $this->db->getQueryString($query);
        $this->builder = new QueryBuilder($query, $this->options, $this->db);
        $this->columns = $this->builder->columns();

        return $this;
    }

    /**
     * @return Datatables
     */
    public function generate(): Datatables
    {
        $this->builder->setColumnAttributes();
        $this->builder->setFilteredQuery();
        $this->builder->setFullQuery();
        $this->builder->setEscapes($this->escapes);

        $this->setResponseData();

        return $this;
    }

    /**
     * @return array
     */
    protected function getData(): array
    {
        $data = $this->db->query($this->builder->full);

        return array_map([$this, 'prepareRowData'], $data);
    }

    /**
     * @param $row
     * @return array
     */
    protected function prepareRowData($row): array
    {
        $keys = $this->builder->isDataObject() ? $this->columns->names() : array_keys($this->columns->names());

        $values = array_map(function (Column $column) use ($row) {
            return $column->value($row);
        }, $this->columns->visible()->getArrayCopy());

        return array_combine($keys, $values);
    }

    /**
     * @return array
     */
    private function getDistinctData(): array
    {
        foreach ($this->distinctColumn as $column) {
            $output[$column] = array_column($this->db->query($this->builder->getDistinctQuery($column)), $column);
        }

        return $output ?? [];
    }

    /**
     *
     */
    public function setResponseData(): void
    {
        $this->response['draw'] = $this->options->draw();
        $this->response['recordsTotal'] = $this->db->count($this->builder->query);

        if($this->builder->query->sql === $this->builder->filtered->sql) {
            $this->response['recordsFiltered'] = $this->response['recordsTotal'];
        } else {
            $this->response['recordsFiltered'] = $this->db->count($this->builder->filtered);
        }

        $this->response['data'] = $this->getData();

        if (\count($this->distinctColumn) > 0 || \count($this->distinctData) > 0) {
            $this->response['distinctData'] = array_merge($this->response['distinctData'] ?? [],
                $this->getDistinctData(), $this->distinctData);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        header('Content-type: application/json;');

        return json_encode($this->response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
