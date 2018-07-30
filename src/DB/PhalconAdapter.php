<?php

namespace Ozdemir\Datatables\DB;

use \Phalcon\Db;

class PhalconAdapter implements DatabaseInterface
{
    protected $db;
    protected $escape = [];

    public function __construct($di, $serviceName = "db")
    {
        $this->db = $di->get($serviceName);
    }

    public function connect()
    {
        return $this;
    }

    public function query($query)
    {
        $data = $this->db->query($query, $this->escape);
        return $data->fetchAll(Db::FETCH_ASSOC);
    }

    public function count($query)
    {
        $query = "Select count(*) as rowcount from ($query)t";
        $data = $this->db->query($query, $this->escape)->fetchAll();

        return $data[0]->rowcount;
    }

    public function escape($string)
    {
        $this->escape[':escape' . (count($this->escape) + 1)] = '%' . $string . '%';

        return ":escape" . (count($this->escape));
    }
}
