<?php namespace Ozdemir\Datatables\DB;

use Db;

class PSAdapter implements DatabaseInterface {

    protected $Db;
    protected $config;
    protected $escape = [];

    function __construct($config)
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
        return $this->Db->getValue($query);
    }

    public function escape($value)
    {
        return pSQL($value);
    }

}