<?php

namespace Ozdemir\Datatables;

use Ozdemir\Datatables\DB\DatabaseInterface;
use Symfony\Component\HttpFoundation\Request;

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
     * @var
     */
    protected $data;

    /**
     * @var
     */
    protected $recordstotal;

    /**
     * @var
     */
    protected $recordsfiltered;

    /**
     * @var \Ozdemir\Datatables\Columns
     */
    protected $columns;

    /**
     * @var \Ozdemir\Datatables\Query
     */
    protected $query;

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
    function __construct(DatabaseInterface $db, Request $request = null)
    {
        $this->db = $db->connect();
        $this->request = $request ?: (Request::createFromGlobals());
    }

    /**
     * @param $query
     * @return $this
     */
    public function query($query)
    {
        $this->query = new Query($query);

        $this->columns = new Columns($this->query->bare);

        $this->query->set(implode(", ", $this->columns->names()));

        return $this;
    }

    /**
     * @param $column
     * @param $closure callable
     * @return $this
     */
    public function add($column, $closure)
    {
        $this->columns->add($column)->closure = $closure;

        return $this;
    }

    /**
     * @param $column
     * @param $closure callable
     * @return $this
     */
    public function edit($column, $closure)
    {
        $this->columns->get($column, false)->closure = $closure;

        return $this;
    }

    /**
     * @param $request
     * @return array|string
     */
    public function get($request)
    {
        switch ($request) {
            case 'columns':
                return $this->columns->names();
            case 'query':
                return $this->query->full;
        }
    }

    /**
     * @param $columns
     * @return $this
     */
    public function hide($columns)
    {
        if (! is_array($columns)) {
            $columns = func_get_args();
        }
        foreach ($columns as $name) {
            $this->columns->get($name)->hide();
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function execute()
    {
        $this->columns->attr($this->request);

        $this->recordstotal = $this->db->count($this->query->base); // unfiltered data count is here.
        $where = $this->filter();
        $this->recordsfiltered = $this->db->count($this->query->base.$where);  // filtered data count is here.

        $this->query->full = $this->query->base.$where.$this->orderby().$this->limit();
        $this->data = $this->db->query($this->query->full);

        return $this;
    }

    /**
     * @return null|string
     */
    protected function filter()
    {
        $search = '';

        $filterglobal = $this->filterGlobal();
        $filterindividual = $this->filterIndividual();

        if (! $filterindividual && ! $filterglobal) {
            return null;
        }

        $search .= $filterglobal;

        if ($filterindividual <> null && $filterglobal <> null) {
            $search .= ' AND ';
        }

        $search .= $filterindividual;
        $search = " WHERE ".$search;

        return $search;
    }

    /**
     * @return null|string
     */
    protected function filterGlobal()
    {
        $searchinput = $this->request->get('search')["value"];

        if ($searchinput == null) {
            return null;
        }

        $search = [];
        $searchinput = preg_replace("/[^\wá-žÁ-Ž]+/", " ", $searchinput);

        foreach (explode(' ', $searchinput) as $word) {
            $look = [];

            foreach ($this->columns->names() as $name) {
                if ($this->columns->get($name)->isSearchable()) {
                    $look[] = $name." LIKE ".$this->db->escape($word);
                }
            }

            $search[] = "(".implode(" OR ", $look).")";
        }

        return implode(" AND ", $search);
    }

    /**
     * @return null|string
     */
    protected function filterIndividual()
    {
        $allcolumns = $this->request->get('columns');

        $search = " (";
        $look = [];

        if (! $allcolumns) {
            return null;
        }

        foreach ($this->columns->names() as $name) {
            if ($this->columns->get($name)->searchValue() != '' and $this->columns->get($name)->isSearchable()) {
                $look[] = $name." LIKE ".$this->db->escape('%'.$this->columns->get($name)->searchValue().'%')."";
            }
        }

        if (count($look) > 0) {
            $search .= implode(" AND ", $look).")";

            return $search;
        }

        return null;
    }

    /**
     * @return null|string
     */
    protected function limit()
    {
        $take = 10;
        $skip = (integer) $this->request->get('start');

        if ($this->request->get('length')) {
            $take = (integer) $this->request->get('length');
        }

        if ($take == -1 || ! $this->request->get('draw')) {
            return null;
        }

        return " LIMIT $take OFFSET $skip";
    }

    /**
     * @return null|string
     */
    protected function orderby()
    {
        // todo : clean up, this code looks garbage.
        $dtorders = $this->request->get('order');
        $orders = " ORDER BY ";

        $dir = ['asc' => 'asc', 'desc' => 'desc'];

        if (! is_array($dtorders)) {
            if ($this->query->hasDefaultOrder()) {
                return null;
            }

            return $orders.$this->columns->names()[0]." asc";
        }

        $takeorders = [];

        foreach ($dtorders as $order) {
            $name = $this->columns->names()[$order['column']];

            if ($this->columns->get($name)->isOrderable()) {
                $takeorders[] = $name." ".$dir[$order['dir']];
            }
        }

        if (count($takeorders) == 0) {
            return null;
        }

        return $orders.implode(",", $takeorders);
    }

    /**
     * @param bool $json
     * @return string
     */
    public function generate($json = true)
    {
        $this->execute();
        $data = $this->formatData();

        return $this->response($data, $json);
    }

    /**
     * @return array
     */
    private function formatData()
    {
        $formatted_data = [];
        $columns = $this->columns->all(false);

        foreach ($this->data as $row) {
            $formatted_row = [];

            foreach ($columns as $column) {
                $attr = $column->attr('data');
                if (is_numeric($attr)) {
                    $formatted_row[] = $column->closure($row, $column->name);
                } else {
                    $formatted_row[$column->name] = $column->closure($row, $column->name);
                }
            }

            $formatted_data[] = $formatted_row;
        }

        return $formatted_data;
    }

    /**
     * @param $data
     * @param bool $json
     * @return string
     */
    protected function response($data, $json = true)
    {
        $response = [];
        $response['draw'] = (integer) $this->request->get('draw');
        $response['recordsTotal'] = $this->recordstotal;
        $response['recordsFiltered'] = $this->recordsfiltered;
        $response['data'] = $data;

        if ($json) {
            header('Content-type: application/json');

            return json_encode($response);
        }

        return $response;
    }
}