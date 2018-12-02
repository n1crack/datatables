<?php

namespace Ozdemir\Datatables;

use Ozdemir\Datatables\DB\DatabaseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Datatables
 *
 * @package Ozdemir\Datatables
 */
class Datatables
{
    /**
     * @var \Ozdemir\Datatables\DB\DatabaseInterface
     */
    protected $db;

    /**
     * @var \Ozdemir\Datatables\ColumnCollection
     */
    protected $columns;

    /**
     * @var \Ozdemir\Datatables\Builder
     */
    protected $builder;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

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
     * @param \Ozdemir\Datatables\DB\DatabaseInterface $db
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     */
    public function __construct(DatabaseInterface $db, Request $request = null)
    {
        $this->db = $db->connect();
        $this->request = $request ?: Request::createFromGlobals();
    }

    /**
     * @param $column
     * @param callable $closure
     * @return Datatables
     */
    public function add($column, $closure): Datatables
    {
        $this->columns->add($column, $closure);

        return $this;
    }

    /**
     * @param $column
     * @param callable $closure
     * @return Datatables
     */
    public function edit($column, $closure): Datatables
    {
        $this->columns->edit($column, $closure);

        return $this;
    }

    /**
     * @param $column
     * @param callable $closure
     * @return Datatables
     */
    public function filter($column, $closure): Datatables
    {
        $this->columns->filter($column, $closure);

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
     * @param array $columns
     * @return Datatables
     */
    public function hide(...$columns): Datatables
    {
        foreach ($columns as $column) {
            if (\is_array($column)) {
                $this->hide(...$column);
            } else {
                $this->columns->get($column)->hide();
            }
        }

        return $this;
    }

    /**
     * @param string $query
     * @return Datatables
     */
    public function query($query): Datatables
    {
        $this->builder = new Builder($query, $this->request, $this->db);
        $this->columns = $this->builder->columns();

        return $this;
    }

    /**
     * @return Datatables
     */
    public function generate(): Datatables
    {
        $this->columns->setAttributes($this->request);
        $this->builder->setFilteredQuery();
        $this->builder->setFullQuery();
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
     * @return array
     */
    private function getDistinctData(): array
    {
        foreach ($this->distinctColumn as $column) {
            $distinct = clone $this->builder->query;
            $distinct->set("SELECT $column FROM ({$this->builder->query})t GROUP BY $column");

            $output[$column] = array_column($this->db->query($distinct), $column);
        }

        return $output;
    }

    /**
     *
     */
    public function setResponseData(): void
    {
        $this->response['draw'] = (integer)$this->request->get('draw');
        $this->response['recordsTotal'] = $this->db->count($this->builder->query);
        $this->response['recordsFiltered'] = $this->db->count($this->builder->filtered);
        $this->response['data'] = $this->getData();
        if (\count($this->distinctColumn) > 0 || \count($this->distinctData) > 0) {
            $this->response['distinctData'] =array_merge($this->response['distinctData']??[], $this->getDistinctData(), $this->distinctData);
        }
    }

    /**
     * @param $row
     * @return array
     */
    protected function prepareRowData($row): array
    {
        $columns = $this->columns->all(false);

        foreach ($columns as $column) {
            // column data gives the column index or column name
            if (is_numeric($column->data())) {
                $formatted_row[] = $column->closure($row);
            } else {
                $formatted_row[$column->name] = $column->closure($row);
            }
        }

        return $formatted_row;
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
        $response = new JsonResponse($this->response);

        return $response->send();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
