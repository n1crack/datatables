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
    public function add($column, $closure)
    {
        $this->columns->add($column, $closure);

        return $this;
    }

    /**
     * @param $column
     * @param callable $closure
     * @return Datatables
     */
    public function edit($column, $closure)
    {
        $this->columns->edit($column, $closure);

        return $this;
    }

    /**
     * @param $column
     * @param callable $closure
     * @return Datatables
     */
    public function filter($column, $closure)
    {
        $this->columns->filter($column, $closure);

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns->names();
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->builder->full;
    }

    /**
     * @param array $columns
     * @return Datatables
     */
    public function hide(...$columns)
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
    public function query($query)
    {
        $this->builder = new Builder($query, $this->request, $this->db);
        $this->columns = $this->builder->columns();

        return $this;
    }

    /**
     * @return Datatables
     */
    public function generate()
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
    protected function getData()
    {
        $data = $this->db->query($this->builder->full);

        return array_map([$this, 'prepareRowData'], $data);
    }

    /**
     *
     */
    public function setResponseData()
    {
        $this->response['draw'] = (integer)$this->request->get('draw');
        $this->response['recordsTotal'] = $this->db->count($this->builder->query);
        $this->response['recordsFiltered'] = $this->db->count($this->builder->filtered);
        $this->response['data'] = $this->getData();
    }

    /**
     * @param $row
     * @return array
     */
    protected function prepareRowData($row)
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
    public function toJson()
    {
        $response = new JsonResponse($this->response);

        return $response->send();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
