<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Query;
use Phalcon\Db\Enum;

/**
 * Class Phalcon4Adapter
 *
 * Supports Phalcon 4 and 5, where the fetch-mode constants moved from
 * \Phalcon\Db to \Phalcon\Db\Enum. For Phalcon 3.x use PhalconAdapter instead.
 *
 * @package Ozdemir\Datatables\DB
 */
class Phalcon4Adapter extends DBAdapter
{
    /**
     * @var
     */
    protected $db;

    /**
     * Phalcon4Adapter constructor.
     * @param $di
     * @param string $serviceName
     */
    public function __construct($di, $serviceName = 'db')
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
     * @param Query $query
     * @return mixed
     */
    public function query(Query $query)
    {
        $data = $this->db->query($query->sql, $query->escapes);

        return $data->fetchAll(Enum::FETCH_ASSOC);
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function count(Query $query)
    {
        $data = $this->db->query("Select count(*) as rowcount from ($query->sql)t", $query->escapes)->fetchAll();

        return $data[0]['rowcount'];
    }

    /**
     * @param $string
     * @param Query $query
     * @return string
     */
    public function escape($string, Query $query)
    {
        $query->escapes[':binding_'.(count($query->escapes) + 1)] = $string;

        return ':binding_'.count($query->escapes);
    }
}
