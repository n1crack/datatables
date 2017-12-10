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

        $this->columns = new Columns($this->query->bare, $this->request);

        $this->query->set(implode(", ", $this->columns->list));

        return $this;
    }

    /**
     * @param $request
     * @return array
     */
    public function get($request)
    {
        switch ($request) {
            case 'columns':
                return $this->columns->list;
                break;
            case 'query':
                return $this->query->full;
                break;
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
            $this->columns->hide($name, true);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function execute()
    {
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

        $filterglobal = $this->filterglobal();
        $filterindividual = $this->filterindividual();

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
    protected function filterglobal()
    {
        $searchinput = $this->request->get('search')["value"];

        if ($searchinput == null) {
            return null;
        }

        $search = [];
        $searchinput = preg_replace("/[^\wá-žÁ-Ž]+/", " ", $searchinput);
        foreach (explode(' ', $searchinput) as $word) {
            $look = [];

            foreach ($this->columns->list as $column) {
                if ($this->columns->isSearchable($column)) {
                    $look[] = $column." LIKE ".$this->db->escape($word);
                }
            }

            $search[] = "(".implode(" OR ", $look).")";
        }

        return implode(" AND ", $search);
    }

    /**
     * @return null|string
     */
    protected function filterindividual()
    {
        $allcolumns = $this->request->get('columns');

        $search = " (";
        $look = [];

        if (! $allcolumns) {
            return null;
        }

        foreach ($this->columns->list as $name) {
            if ($this->columns->isSearching($name) != '' and $this->columns->isSearchable($name)) {
                $look[] = $name." LIKE ".$this->db->escape('%'.$this->columns->isSearching($name).'%')."";
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

            return $orders.$this->columns->list[0]." asc";
        }
        $takeorders = [];
        foreach ($dtorders as $order) {
            $col = $this->columns->list[$order['column']];

            if ($this->columns->isOrderable($col)) {
                $takeorders[] = $this->columns->list[$order['column']]." ".$dir[$order['dir']];
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
        $formatted_data = [];

        foreach ($this->data as $row) {
            $formatted_row = [];

            foreach ($this->columns->all(false) as $col) {
                $attr = $this->columns->attr($col->name)['data'];
                if (is_numeric($attr)) {
                    $formatted_row[] = $col->closure($row, $col->name);
                } else {
                    $formatted_row[$col->name] = $col->closure($row, $col->name);
                }
            }
            $formatted_data[] = $formatted_row;
        }

        $response['draw'] = (integer) $this->request->get('draw');
        $response['recordsTotal'] = $this->recordstotal;
        $response['recordsFiltered'] = $this->recordsfiltered;
        $response['data'] = $formatted_data;

        return $this->response($response, $json);
    }

    /**
     * @param $column
     * @param $closure
     * @return $this
     */
    public function add($column, $closure)
    {
        $added = $this->columns->add($column);
        $added->closure = $closure;

        return $this;
    }

    /**
     * @param $column
     * @param $closure
     * @return $this
     */
    public function edit($column, $closure)
    {
        $this->columns->get($column, false)->closure = $closure;

        return $this;
    }

    /**
     * @param $data
     * @param bool $json
     * @return string
     */
    protected function response($data, $json = true)
    {
        if ($json) {
            header('Content-type: application/json');

            return json_encode($data);
        }

        return $data;
    }
}