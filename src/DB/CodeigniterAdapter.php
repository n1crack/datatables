<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Query;

/**
 * Class CodeigniterAdapter
 * @package Ozdemir\Datatables\DB
 */
class CodeigniterAdapter extends DBAdapter
{
    /**
     * @var
     */
    protected $CI;

    /**
     * CodeigniterAdapter constructor.
     * @param null $config
     */
    public function __construct($config = null)
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    /**
     * @return $this
     */
    public function connect()
    {
        return $this;
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function query(Query $query)
    {
        $data = $this->CI->db->query($query, $query->escapes);

        return $data->result_array();
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function count(Query $query)
    {
        $data = $this->CI->db->query("Select count(*) as rowcount from ($query)t", $query->escapes)->result_array();

        return $data[0]['rowcount'];
    }

    /**
     * @param $string
     * @param Query $query
     * @return string
     */
    public function escape($string, Query $query)
    {
        $query->escapes[] = $string;

        return '?';
    }
}

