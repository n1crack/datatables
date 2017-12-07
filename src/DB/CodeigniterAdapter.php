<?php namespace Ozdemir\Datatables\DB;

/**
 * Class CodeigniterAdapter
 * @package Ozdemir\Datatables\DB
 */
class CodeigniterAdapter extends AbstractDatabase
{

    protected $escape = [];
    protected $CI;

    /**
     * CodeigniterAdapter constructor.
     * @param null $config
     */
    function __construct($config = null)
    {
        parent::__construct($config);
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
     * @param $query
     * @return array
     */
    public function query($query)
    {
        $data = $this->CI->db->query($query, $this->escape);

        return $data->result_array();
    }

    /**
     * @param $query
     * @return int
     */
    public function count($query)
    {
        $query = "Select count(*) as rowcount from ($query)t";
        $data = $this->CI->db->query($query, $this->escape)->result_array();

        return $data[0]['rowcount'];
    }

    /**
     * @param $string
     * @return string
     */
    public function escape($string)
    {
        $this->escape[] = '%' . $string . '%';

        return "?";
    }

}
