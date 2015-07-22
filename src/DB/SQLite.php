<?php namespace Ozdemir\Datatables\DB;

use SQLite3;

class SQLite implements DatabaseInterface {

    private $sqlite;
    private $config;

    function __construct($config)
    {
        $this->config = $config;
    }

    public function connect()
    {
        $dir = $this->config['dir'];
        $this->sqlite = new SQLite3($dir);

        return $this;
    }

    public function query($query)
    {
        $result = $this->sqlite->query($query);
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
        {
            $rows[] = $row;
        }

        return $rows;
    }

    public function count($query)
    {
        $result = $this->sqlite->query($query);
        $numrows = 0;
        while ($result->fetchArray(SQLITE3_ASSOC))
        {
            $numrows ++;
        }

        return $numrows;
    }

    public function escape($string)
    {
        return $this->sqlite->escapeString($string);
    }

}