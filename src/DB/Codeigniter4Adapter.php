<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Query;

/**
 * Class Codeigniter4Adapter
 * @package Ozdemir\Datatables\DB
 */
class Codeigniter4Adapter extends DBAdapter
{
    /**
     * @var \CodeIgniter\Database\BaseConnection $db
     */
    protected $db;

    /**
     * @var $config
     */
    public function __construct($config = null)
    {

    }

    /**
     * @return $this
     */
    public function connect()
    {
        $this->db = \Config\Database::connect();

        return $this;
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function query(Query $query)
    {
        $sql = $this->db->query($query, $query->escapes);

        return $sql->getResultArray();
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function count(Query $query)
    {
        $sql = $this->db->query("Select count(*) as rowcount from ($query)t", $query->escapes);

        return (int)$sql->getRow()->rowcount;
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

    /**
     * @param $query
     * @return string
     */
    public function getQueryString($query)
    {
        if ($query instanceof \CodeIgniter\Database\BaseBuilder) {
            return $query->getCompiledSelect();
        }

        return $query;
    }
}
