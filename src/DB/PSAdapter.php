<?php namespace Ozdemir\Datatables\DB;

use Db;

class PSAdapter implements DatabaseInterface
{

    protected $Db;
    protected $config;
    protected $escape = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function connect()
    {
        $this->Db = Db::getInstance();
        return $this;
    }

    public function query($query, $array = true, $user_cache = true)
    {
        return $this->Db->executeS($query, $array, $user_cache);
    }

    public function count($query)
    {
        $query = "Select count(*) as rowcount," . substr($query, 6);
        $data = $this->Db->getRow($query);
        return $data['rowcount'];
    }

    public function escape($string)
    {
        return '"%' . pSQL($string) . '%"';
    }
}
