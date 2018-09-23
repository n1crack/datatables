<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Query;
use \Phalcon\Db;

/**
 * Class PhalconAdapter
 * @package Ozdemir\Datatables\DB
 */
class PhalconAdapter implements DatabaseInterface
{
    /**
     * @var
     */
    protected $db;

    /**
     * PhalconAdapter constructor.
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
        $data = $this->db->query($query, $query->escape);

        return $data->fetchAll(Db::FETCH_ASSOC);
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function count(Query $query)
    {
        $query = "Select count(*) as rowcount from ($query)t";
        $data = $this->db->query($query, $query->escape)->fetchAll();

        return $data[0]->rowcount;
    }

    /**
     * @param $string
     * @param Query $query
     * @return string
     */
    public function escape($string,Query $query)
    {
        $this->escape[':binding_'.(count($query->escape) + 1)] = '%'.$string.'%';

        return ':binding_'.count($query->escape);
    }
}
