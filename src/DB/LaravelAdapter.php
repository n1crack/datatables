<?php namespace Ozdemir\Datatables\DB;

use DB;

/**
 * Class LaravelAdapter
 * @package Ozdemir\Datatables\DB
 */
class LaravelAdapter extends AbstractDatabase
{

    protected $escape = [];

    /**
     * @return $this
     */
    public function connect()
    {
        return $this;
    }

    /**
     * @param $query
     * @return array
     */
    public function query($query)
    {
        $data = DB::select($query, $this->escape);
        $row = [];

        foreach ($data as $item) {
            $row[] = (array)$item;
        }

        return $row;
    }

    /**
     * @param $query
     * @return int
     */
    public function count($query)
    {
        $query = "Select count(*) as rowcount," . substr($query, 6);
        $data = DB::select($query, $this->escape);

        return $data[0]->rowcount;
    }

    /**
     * @param $string
     * @return string
     */
    public function escape($string)
    {
        $this->escape[':escape' . (count($this->escape) + 1)] = '%' . $string . '%';

        return ":escape" . (count($this->escape));
    }

}
