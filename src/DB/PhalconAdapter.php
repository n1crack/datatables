<?php

namespace Ozdemir\Datatables\DB;

use \Phalcon\Db;

/**
 * Class PhalconAdapter
 * @package Ozdemir\Datatables\DB
 */
class PhalconAdapter implements DatabaseInterface
{
    protected $db;
    protected $escape = [];

    // @todo this constructor does not satisfy the loose Database interface types / behavior expectations

    /**
     * PhalconAdapter constructor.
     * @param $di
     * @param string $serviceName
     */
    function __construct($di, $serviceName = "db")
    {
        $this->db = $di->get($serviceName);
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
        $data = $this->db->query($query, $this->escape);
        return $data->fetchAll(Db::FETCH_ASSOC);
    }

    /**
     * @param $query
     * @return int
     */
    public function count($query)
    {
        $query = "Select count(*) as rowcount from ($query)t";
        $data = $this->db->query($query, $this->escape)->fetchAll();

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
