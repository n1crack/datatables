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
     * @return $this
     */
    public function add($column, $closure)
    {
        $this->columns->add($column, $closure);

        return $this;
    }

    /**
     * @param $column
     * @param callable $closure
     * @return $this
     */
    public function edit($column, $closure)
    {
        $this->columns->edit($column, $closure);

        return $this;
    }

    /**
     * @param $column
     * @param callable $closure
     * @return $this
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
     * @param $columns
     * @return $this
     */
    public function hide($columns)
    {
        if (!is_array($columns)) {
            $columns = func_get_args();
        }

        foreach ($columns as $name) {
            $this->columns->get($name)->hide();
        }

        return $this;
    }

    /**
     * @param $query
     * @return $this
     */
    public function query($query)
    {
        $this->builder = new Builder($query, $this->request, $this->db);
        $this->columns = new ColumnCollection($this->builder);

        return $this;
    }

    /**
     * @param bool $json
     * @return JsonResponse | array
     */
    public function generate($json = true)
    {
        $this->columns->setAttributes($this->request);
        $this->builder->setFilteredQuery();
        $this->builder->setFullQuery();

        $response = [];
        $response['draw'] = (integer)$this->request->get('draw');
        $response['recordsTotal'] = $this->db->count($this->builder->query);
        $response['recordsFiltered'] = $this->db->count($this->builder->filtered);
        $response['data'] = $this->getData();

        return $this->response($response, $json);
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
     * @param $row
     * @return array
     */
    protected function prepareRowData($row)
    {
        $columns = $this->columns->all(false);

        foreach ($columns as $column) {
            // data gives the column index or column name
            $attr = $column->attr('data');
            if (is_numeric($attr)) {
                $formatted_row[] = $column->closure($row);
            } else {
                $formatted_row[$column->name] = $column->closure($row);
            }
        }

        return $formatted_row;
    }

    /**
     * @param $response
     * @param bool $json
     * @return JsonResponse | array
     */
    protected function response($response, $json = true)
    {
        if ($json) {
            $response = new JsonResponse($response);

            return $response->send();
        }

        return $response;
    }
}
